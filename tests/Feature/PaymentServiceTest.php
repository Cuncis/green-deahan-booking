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
 * createInvoice() di-partial-mock supaya tidak ada panggilan network
 * sungguhan ke sandbox Mayar.
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

    private function mockMayar(array $response): PaymentService
    {
        return $this->partialMock(PaymentService::class, function ($mock) use ($response) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('createInvoice')
                ->once()
                ->andReturn($response);
        });
    }

    public function test_mengembalikan_redirect_url_dari_invoice(): void
    {
        $booking = $this->buatBooking();

        $service = $this->mockMayar([
            'statusCode' => 200,
            'data' => [
                'id' => 'invoice-1',
                'transactionId' => 'trx-1',
                'link' => 'https://sandbox.mayar.club/invoice/invoice-1',
            ],
        ]);

        $hasil = $service->createTransaction($booking, 'https://tenant.test/?kode_booking='.$booking->kode_booking);

        $this->assertSame('https://sandbox.mayar.club/invoice/invoice-1', $hasil['redirect_url']);
    }

    /**
     * Metode pembayaran baru diketahui setelah customer pilih sendiri di
     * halaman Mayar, jadi baris pembayaran TIDAK dibuat di sini. Baris itu
     * baru dibuat oleh PembayaranController::webhook() begitu Mayar kasih
     * tahu hasilnya.
     */
    public function test_tidak_membuat_record_pembayaran_sebelum_webhook(): void
    {
        $booking = $this->buatBooking();

        $service = $this->mockMayar([
            'data' => ['id' => 'invoice-1', 'link' => 'https://sandbox.mayar.club/invoice/invoice-1'],
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
                ->shouldReceive('createInvoice')
                ->once()
                ->withArgs(function ($params) use (&$capturedParams) {
                    $capturedParams = $params;

                    return true;
                })
                ->andReturn(['data' => ['id' => 'invoice-1', 'link' => 'https://sandbox.mayar.club/invoice/invoice-1']]);
        });

        $service->createTransaction($booking, 'https://tenant.test/?kode_booking='.$booking->kode_booking);

        $this->assertSame('customer@greendeahan.com', $capturedParams['email']);
        $this->assertSame('Budi Santoso', $capturedParams['name']);
        $this->assertSame('081234567890', $capturedParams['mobile']);
        $this->assertSame($booking->kode_booking, $capturedParams['extraData']['noCustomer']);
        $this->assertSame(100000, $capturedParams['items'][0]['rate']);
        $this->assertSame('https://tenant.test/?kode_booking='.$booking->kode_booking, $capturedParams['redirectUrl']);
    }
}
