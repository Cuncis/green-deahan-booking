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

    private function payloadWebhook(Booking $booking, string $status): array
    {
        return [
            'kode_transaksi_gateway' => 'TRX-'.$booking->id,
            'kode_booking' => $booking->kode_booking,
            'status' => $status,
            'metode' => 'qris',
            'jumlah' => $booking->total_bayar,
        ];
    }

    /**
     * Lewat header x-callback-token Xendit, supaya test yang fokus ke
     * logika bisnis (bukan ke verifikasi signature-nya sendiri) tidak
     * perlu hitung hash SHA512 Midtrans satu-satu. Lihat WebhookSignatureTest
     * untuk pengujian verifikasi signature-nya sendiri.
     */
    private function postWebhook(string $url, array $payload)
    {
        return $this->postJson($url, $payload, [
            'x-callback-token' => 'test-xendit-callback-token',
        ]);
    }

    public function test_webhook_bisa_diakses_tanpa_tenant_terdaftar_di_domain(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postWebhook(
            'http://domain-tidak-terdaftar.test/api/webhook/pembayaran',
            $this->payloadWebhook($booking, 'sukses'),
        );

        $response->assertOk();
    }

    public function test_webhook_selalu_membuat_record_pembayaran(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'pending'));

        $this->assertDatabaseHas('pembayaran', [
            'booking_id' => $booking->id,
            'status' => 'pending',
        ]);
    }

    public function test_webhook_sukses_mengonfirmasi_booking_dan_slot_jadi_booked(): void
    {
        Queue::fake();

        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'sukses'));

        $response->assertOk();
        $this->assertSame('dikonfirmasi', $booking->fresh()->status_booking);
        $this->assertSame('booked', $booking->fresh()->slot->status);
        $this->assertNull($booking->fresh()->slot->hold_sampai);

        $this->assertDatabaseHas('pembayaran', [
            'booking_id' => $booking->id,
            'status' => 'sukses',
        ]);
    }

    public function test_webhook_sukses_dispatch_job_notifikasi_whatsapp(): void
    {
        Queue::fake();

        $booking = $this->buatBookingMenunggu($this->tenant());

        $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'sukses'));

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

        $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'sukses'));

        $this->assertDatabaseHas('reminder_log', [
            'booking_id' => $booking->id,
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

        $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'sukses'));

        $this->assertDatabaseCount('reminder_log', 0);
    }

    public function test_webhook_gagal_melepas_slot_dan_membatalkan_booking(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postWebhook('/api/webhook/pembayaran', $this->payloadWebhook($booking, 'gagal'));

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
            'kode_transaksi_gateway' => 'TRX-X',
            'kode_booking' => 'TIDAK-ADA',
            'status' => 'sukses',
            'metode' => 'qris',
            'jumlah' => 10000,
        ]);

        $response->assertNotFound();
    }
}
