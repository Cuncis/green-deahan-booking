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
 * Fokus khusus ke verifikasi token webhook pembayaran
 * (PembayaranController::verifyMayarToken()). Pengujian logika bisnis
 * webhook (konfirmasi booking, reminder, dst) ada di PembayaranControllerTest.
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

    private function payloadMayar(Booking $booking): array
    {
        return [
            'event' => 'payment.received',
            'data' => [
                'id' => 'invoice-'.$booking->id,
                'transactionId' => 'TRX-'.$booking->id,
                'status' => 'PAID',
                'amount' => $booking->total_bayar,
                'paymentMethod' => 'QRIS',
                'extraData' => ['noCustomer' => $booking->kode_booking],
            ],
        ];
    }

    public function test_webhook_ditolak_tanpa_token_valid(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson('/api/webhook/pembayaran', $this->payloadMayar($booking));

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
        $this->assertSame('menunggu', $booking->fresh()->status_booking);
        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_webhook_ditolak_kalau_token_salah(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson('/api/webhook/pembayaran?token=token-salah', $this->payloadMayar($booking));

        $response->assertStatus(401);
        $this->assertSame('menunggu', $booking->fresh()->status_booking);
    }

    public function test_webhook_diterima_dengan_token_valid(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson(
            '/api/webhook/pembayaran?token=test-mayar-webhook-token',
            $this->payloadMayar($booking),
        );

        $response->assertOk();
        $this->assertSame('dikonfirmasi', $booking->fresh()->status_booking);
        $this->assertDatabaseHas('pembayaran', ['booking_id' => $booking->id, 'status' => 'sukses']);
    }
}
