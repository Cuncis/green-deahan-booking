<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian verifikasi signature webhook itu sendiri (Xendit lewat header
 * x-callback-token, Midtrans lewat signature_key di body). Untuk pengujian
 * logika bisnis setelah webhook diterima (konfirmasi booking, slot jadi
 * booked, dst), lihat PembayaranControllerTest.
 */
class WebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::where('domain', 'localhost')->firstOrFail();
    }

    private function buatBookingMenunggu(Tenant $tenant): Booking
    {
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
        ]);

        return Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'slot_id' => $slot->id,
            'status_booking' => 'menunggu',
        ]);
    }

    private function payloadDasar(Booking $booking): array
    {
        return [
            'kode_transaksi_gateway' => 'TRX-'.$booking->id,
            'kode_booking' => $booking->kode_booking,
            'status' => 'sukses',
            'metode' => 'qris',
            'jumlah' => $booking->total_bayar,
        ];
    }

    public function test_webhook_ditolak_tanpa_signature_header(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson('/api/webhook/pembayaran', $this->payloadDasar($booking));

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
        $this->assertDatabaseMissing('pembayaran', ['booking_id' => $booking->id]);
        $this->assertSame('menunggu', $booking->fresh()->status_booking);
    }

    public function test_webhook_ditolak_dengan_signature_salah(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson('/api/webhook/pembayaran', $this->payloadDasar($booking), [
            'x-callback-token' => 'token-yang-salah',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
        $this->assertDatabaseMissing('pembayaran', ['booking_id' => $booking->id]);
        $this->assertSame('menunggu', $booking->fresh()->status_booking);
    }

    public function test_webhook_diterima_dengan_signature_benar(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson('/api/webhook/pembayaran', $this->payloadDasar($booking), [
            'x-callback-token' => 'test-xendit-callback-token',
        ]);

        $response->assertOk();
        $response->assertJson(['message' => 'Webhook diterima.']);
        $this->assertDatabaseHas('pembayaran', ['booking_id' => $booking->id, 'status' => 'sukses']);
        $this->assertSame('dikonfirmasi', $booking->fresh()->status_booking);
    }

    /**
     * Payload di sini bentuknya notifikasi ASLI Midtrans (order_id,
     * transaction_status, payment_type, gross_amount, transaction_id), bukan
     * skema internal kode_booking/status/metode. Lihat
     * PembayaranController::parseMidtransPayload().
     */
    private function payloadMidtransAsli(Booking $booking, array $override = []): array
    {
        $orderId = $booking->kode_booking;
        $statusCode = '200';
        $grossAmount = (string) $booking->total_bayar;
        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.config('services.midtrans.server_key'));

        return array_merge([
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'transaction_id' => 'TRX-'.$booking->id,
        ], $override);
    }

    public function test_webhook_midtrans_ditolak_dengan_signature_key_salah(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $payload = $this->payloadMidtransAsli($booking, ['signature_key' => 'hash-ngasal-yang-salah']);

        $response = $this->postJson('/api/webhook/pembayaran', $payload);

        $response->assertStatus(401);
        $this->assertDatabaseMissing('pembayaran', ['booking_id' => $booking->id]);
    }

    public function test_webhook_midtrans_diterima_dengan_signature_key_benar(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson('/api/webhook/pembayaran', $this->payloadMidtransAsli($booking));

        $response->assertOk();
        $this->assertDatabaseHas('pembayaran', ['booking_id' => $booking->id, 'status' => 'sukses']);
        $this->assertSame('dikonfirmasi', $booking->fresh()->status_booking);
    }
}
