<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cabang;
use App\Models\Customer;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit test untuk PaymentService::createTransaction(), dengan
 * createSnapTransaction() di-partial-mock supaya tidak ada panggilan network
 * sungguhan ke sandbox Midtrans (SDK-nya pakai curl mentah, Http::fake()
 * tidak bisa mencegat itu).
 */
class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function buatBooking(array $bookingOverride = []): Booking
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
            'nama' => 'Lapangan Futsal A',
        ]);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'harga' => 100000,
        ]);
        $customer = Customer::factory()->create([
            'nama' => 'Budi Santoso',
            'no_telepon' => '081234567890',
            'email' => null,
        ]);

        return Booking::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
            'total_bayar' => 100000,
        ], $bookingOverride));
    }

    private function mockSnap(array $response): PaymentService
    {
        return $this->partialMock(PaymentService::class, function ($mock) use ($response) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('createSnapTransaction')
                ->once()
                ->andReturn($response);
        });
    }

    public function test_mengembalikan_redirect_url_dari_snap(): void
    {
        $booking = $this->buatBooking();

        $service = $this->mockSnap([
            'token' => 'snap-token-1',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/snap-token-1',
        ]);

        $hasil = $service->createTransaction($booking, 'https://tenant.test/?kode_booking='.$booking->kode_booking);

        $this->assertSame('https://app.sandbox.midtrans.com/snap/v4/redirection/snap-token-1', $hasil['redirect_url']);
    }

    /**
     * Beda dari integrasi Core API sebelumnya: metode pembayaran baru
     * diketahui setelah customer pilih sendiri di halaman Snap, jadi baris
     * pembayaran TIDAK dibuat di sini. Baris itu baru dibuat oleh
     * PembayaranController::webhook() begitu Midtrans kasih tahu hasilnya.
     */
    public function test_tidak_membuat_record_pembayaran_sebelum_webhook(): void
    {
        $booking = $this->buatBooking();

        $service = $this->mockSnap([
            'token' => 'snap-token-1',
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/snap-token-1',
        ]);

        $service->createTransaction($booking, 'https://tenant.test/');

        $this->assertDatabaseCount('pembayaran', 0);
    }

    public function test_customer_tanpa_email_pakai_email_default(): void
    {
        $booking = $this->buatBooking();

        $capturedParams = null;
        $service = $this->partialMock(PaymentService::class, function ($mock) use (&$capturedParams) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('createSnapTransaction')
                ->once()
                ->withArgs(function ($params) use (&$capturedParams) {
                    $capturedParams = $params;

                    return true;
                })
                ->andReturn(['token' => 'snap-token-1', 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/snap-token-1']);
        });

        $service->createTransaction($booking, 'https://tenant.test/?kode_booking='.$booking->kode_booking);

        $this->assertSame('customer@greendeahan.com', $capturedParams['customer_details']['email']);
        $this->assertSame('Budi Santoso', $capturedParams['customer_details']['first_name']);
        $this->assertSame($booking->kode_booking, $capturedParams['transaction_details']['order_id']);
        $this->assertSame(100000, $capturedParams['transaction_details']['gross_amount']);
        $this->assertSame('https://tenant.test/?kode_booking='.$booking->kode_booking, $capturedParams['callbacks']['finish']);
        $this->assertArrayNotHasKey('payment_type', $capturedParams);
    }
}
