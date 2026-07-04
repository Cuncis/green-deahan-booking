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
 * Fokus khusus ke verifikasi signature webhook pembayaran
 * (PembayaranController::verifyMidtransSignature()/verifyXenditSignature()).
 * Pengujian logika bisnis webhook (konfirmasi booking, reminder, dst) ada
 * di PembayaranControllerTest.
 */
class BookingTest extends TestCase
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

    private function payloadInternal(Booking $booking): array
    {
        return [
            'kode_transaksi_gateway' => 'TRX-'.$booking->id,
            'kode_booking' => $booking->kode_booking,
            'status' => 'sukses',
            'metode' => 'qris',
            'jumlah' => $booking->total_bayar,
        ];
    }

    public function test_webhook_ditolak_tanpa_signature_valid(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson('/api/webhook/pembayaran', $this->payloadInternal($booking));

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
        $this->assertSame('menunggu', $booking->fresh()->status_booking);
        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_webhook_ditolak_kalau_xendit_callback_token_salah(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson(
            '/api/webhook/pembayaran',
            $this->payloadInternal($booking),
            ['x-callback-token' => 'token-salah'],
        );

        $response->assertStatus(401);
        $this->assertSame('menunggu', $booking->fresh()->status_booking);
    }

    public function test_webhook_ditolak_kalau_midtrans_signature_salah(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson('/api/webhook/pembayaran', [
            'order_id' => $booking->kode_booking,
            'status_code' => '200',
            'gross_amount' => (string) $booking->total_bayar,
            'signature_key' => 'signature-ngasal-bukan-hasil-hash',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'transaction_id' => 'TRX-'.$booking->id,
        ]);

        $response->assertStatus(401);
        $this->assertSame('menunggu', $booking->fresh()->status_booking);
    }

    public function test_webhook_diterima_dengan_signature_valid(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $orderId = $booking->kode_booking;
        $statusCode = '200';
        $grossAmount = (string) $booking->total_bayar;
        $signatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.config('services.midtrans.server_key'));

        $response = $this->postJson('/api/webhook/pembayaran', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'transaction_id' => 'TRX-'.$booking->id,
        ]);

        $response->assertOk();
        $this->assertSame('dikonfirmasi', $booking->fresh()->status_booking);
        $this->assertDatabaseHas('pembayaran', ['booking_id' => $booking->id, 'status' => 'sukses']);
    }

    public function test_webhook_diterima_dengan_xendit_callback_token_valid(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson(
            '/api/webhook/pembayaran',
            $this->payloadInternal($booking),
            ['x-callback-token' => 'test-xendit-callback-token'],
        );

        $response->assertOk();
        $this->assertSame('dikonfirmasi', $booking->fresh()->status_booking);
    }
}
