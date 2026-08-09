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
 * Pengujian verifikasi token webhook itu sendiri (query string
 * ?token=MAYAR_WEBHOOK_TOKEN, lihat
 * PembayaranController::verifyMayarToken()). Untuk pengujian logika bisnis
 * setelah webhook diterima (konfirmasi booking, slot jadi booked, dst),
 * lihat PembayaranControllerTest.
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

    public function test_webhook_ditolak_tanpa_token(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson('/api/webhook/pembayaran', $this->payloadDasar($booking));

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
        $this->assertDatabaseMissing('pembayaran', ['booking_id' => $booking->id]);
        $this->assertSame('menunggu', $booking->fresh()->status_booking);
    }

    public function test_webhook_ditolak_dengan_token_salah(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson('/api/webhook/pembayaran?token=token-yang-salah', $this->payloadDasar($booking));

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
        $this->assertDatabaseMissing('pembayaran', ['booking_id' => $booking->id]);
        $this->assertSame('menunggu', $booking->fresh()->status_booking);
    }

    public function test_webhook_diterima_dengan_token_benar(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $response = $this->postJson(
            '/api/webhook/pembayaran?token=test-mayar-webhook-token',
            $this->payloadDasar($booking),
        );

        $response->assertOk();
        $response->assertJson(['message' => 'Webhook diterima.']);
        $this->assertDatabaseHas('pembayaran', ['booking_id' => $booking->id, 'status' => 'sukses']);
        $this->assertSame('dikonfirmasi', $booking->fresh()->status_booking);
    }

    public function test_webhook_ditolak_kalau_status_belum_dikenal(): void
    {
        $booking = $this->buatBookingMenunggu($this->tenant());

        $payload = $this->payloadDasar($booking);
        $payload['data']['status'] = 'AUTHORIZED';

        $response = $this->postJson('/api/webhook/pembayaran?token=test-mayar-webhook-token', $payload);

        $response->assertStatus(401);
        $this->assertDatabaseMissing('pembayaran', ['booking_id' => $booking->id]);
        $this->assertSame('menunggu', $booking->fresh()->status_booking);
    }
}
