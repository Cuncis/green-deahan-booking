<?php

namespace Tests\Feature;

use App\Jobs\KirimNotifikasiWhatsApp;
use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use App\Models\TenantFitur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PembayaranControllerTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::where('domain', 'localhost')->firstOrFail();
    }

    private function buatBookingMenunggu(Tenant $tenant, array $slotOverride = []): Booking
    {
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
        ]);
        $slot = JadwalSlot::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
        ], $slotOverride));

        return Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'slot_id' => $slot->id,
            'status_booking' => 'menunggu',
        ]);
    }

    /**
     * @param  'PAID'|'EXPIRED'|'UNPAID'  $status
     */
    private function payloadWebhook(Booking $booking, string $status): array
    {
        return [
            'event' => 'payment.received',
            'data' => [
                'id' => 'invoice-'.$booking->id,
                'transactionId' => 'TRX-'.$booking->id,
                'status' => $status,
                'amount' => $booking->total_bayar,
                'paymentMethod' => 'QRIS',
                'extraData' => ['noCustomer' => $booking->kode_booking],
            ],
        ];
    }

    /**
     * Lewat query string ?token=..., supaya test yang fokus ke logika
     * bisnis (bukan ke verifikasi token-nya sendiri) tidak perlu ulang
     * setup token tiap test. Lihat WebhookSignatureTest untuk pengujian
     * verifikasi token itu sendiri.
     */
    private function postWebhook(string $url, array $payload)
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $this->postJson($url.$separator.'token=test-mayar-webhook-token', $payload);
    }

    public function test_webhook_bisa_diakses_tanpa_tenant_terdaftar_di_domain(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postWebhook(
            'http://domain-tidak-terdaftar.test/api/webhook/pembayaran',
            $this->payloadWebhook($booking, 'PAID'),
        );

        $response->assertOk();
    }

    public function test_webhook_selalu_membuat_record_pembayaran(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'UNPAID'));

        $this->assertDatabaseHas('pembayaran', [
            'booking_id' => $booking->id,
            'status' => 'pending',
        ]);
    }

    public function test_webhook_sukses_mengonfirmasi_booking_dan_slot_jadi_booked(): void
    {
        Queue::fake();

        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'PAID'));

        $response->assertOk();
        $this->assertSame('dikonfirmasi', $booking->fresh()->status_booking);
        $this->assertSame('booked', $booking->fresh()->slot->status);
        $this->assertNull($booking->fresh()->slot->hold_sampai);

        $this->assertDatabaseHas('pembayaran', [
            'booking_id' => $booking->id,
            'status' => 'sukses',
            'metode' => 'qris',
            'kode_transaksi_gateway' => 'TRX-'.$booking->id,
        ]);
    }

    public function test_webhook_sukses_dispatch_job_notifikasi_whatsapp(): void
    {
        Queue::fake();

        $booking = $this->buatBookingMenunggu($this->tenant());

        $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'PAID'));

        Queue::assertPushed(KirimNotifikasiWhatsApp::class, function ($job) use ($booking) {
            return $job->booking->is($booking);
        });
    }

    public function test_webhook_sukses_menjadwalkan_reminder_kalau_fitur_reminder_otomatis_aktif(): void
    {
        Queue::fake();

        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $booking = $this->buatBookingMenunggu($tenant, [
            'tanggal' => now()->addDay()->toDateString(),
            'jam_mulai' => '18:00',
        ]);

        $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'PAID'));

        $this->assertDatabaseHas('reminder_log', [
            'booking_id' => $booking->id,
            'status' => 'menunggu',
        ]);
    }

    public function test_webhook_sukses_tidak_menjadwalkan_reminder_kalau_fitur_tidak_aktif(): void
    {
        Queue::fake();

        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));

        $booking = $this->buatBookingMenunggu($tenant);

        $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'PAID'));

        $this->assertDatabaseCount('reminder_log', 0);
    }

    public function test_webhook_gagal_melepas_slot_dan_membatalkan_booking(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'EXPIRED'));

        $response->assertOk();
        $this->assertSame('dibatalkan', $booking->fresh()->status_booking);
        $this->assertSame('kosong', $booking->fresh()->slot->status);
        $this->assertNull($booking->fresh()->slot->hold_sampai);

        $this->assertDatabaseHas('pembayaran', [
            'booking_id' => $booking->id,
            'status' => 'gagal',
        ]);
    }

    public function test_webhook_kode_booking_tidak_ditemukan_mengembalikan_404(): void
    {
        $response = $this->postWebhook('/api/webhook/pembayaran', [
            'event' => 'payment.received',
            'data' => [
                'id' => 'invoice-x',
                'transactionId' => 'TRX-X',
                'status' => 'PAID',
                'amount' => 10000,
                'paymentMethod' => 'QRIS',
                'extraData' => ['noCustomer' => 'TIDAK-ADA'],
            ],
        ]);

        $response->assertNotFound();
    }

    public function test_webhook_metode_va_dipetakan_dengan_benar(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $payload = $this->payloadWebhook($booking, 'PAID');
        $payload['data']['paymentMethod'] = 'BANK_TRANSFER';

        $response = $this->postWebhook('/api/webhook/pembayaran', $payload);

        $response->assertOk();
        $this->assertDatabaseHas('pembayaran', [
            'booking_id' => $booking->id,
            'status' => 'sukses',
            'metode' => 'va',
        ]);
    }

    public function test_webhook_metode_ewallet_dipetakan_dengan_benar(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $payload = $this->payloadWebhook($booking, 'PAID');
        $payload['data']['paymentMethod'] = 'GOPAY';

        $response = $this->postWebhook('/api/webhook/pembayaran', $payload);

        $response->assertOk();
        $this->assertDatabaseHas('pembayaran', [
            'booking_id' => $booking->id,
            'status' => 'sukses',
            'metode' => 'ewallet',
        ]);
    }

    public function test_webhook_ditolak_kalau_metode_pembayaran_belum_dikenal(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $payload = $this->payloadWebhook($booking, 'PAID');
        $payload['data']['paymentMethod'] = 'CREDIT_CARD';

        $response = $this->postWebhook('/api/webhook/pembayaran', $payload);

        $response->assertStatus(401);
        $this->assertSame('menunggu', $booking->fresh()->status_booking);
        $this->assertDatabaseMissing('pembayaran', ['booking_id' => $booking->id]);
    }
}
