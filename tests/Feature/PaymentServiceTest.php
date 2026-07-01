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
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Unit test untuk PaymentService::createTransaction(), dengan chargeMidtrans()
 * di-partial-mock supaya tidak ada panggilan network sungguhan ke sandbox
 * Midtrans (SDK-nya pakai curl mentah, Http::fake() tidak bisa mencegat itu).
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

    private function mockCharge(array $response): PaymentService
    {
        return $this->partialMock(PaymentService::class, function ($mock) use ($response) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('chargeMidtrans')
                ->once()
                ->andReturn($response);
        });
    }

    public function test_qris_mengembalikan_qr_url_dari_actions(): void
    {
        $booking = $this->buatBooking();

        $service = $this->mockCharge([
            'transaction_id' => 'trx-qris-1',
            'actions' => [
                ['name' => 'generate-qr-code', 'method' => 'GET', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/trx-qris-1/qr-code'],
            ],
        ]);

        $hasil = $service->createTransaction($booking, 'qris');

        $this->assertSame('qris', $hasil['metode']);
        $this->assertSame('trx-qris-1', $hasil['kode_transaksi_gateway']);
        $this->assertSame('qris', $hasil['instruksi']['tipe']);
        $this->assertSame('https://api.sandbox.midtrans.com/v2/qris/trx-qris-1/qr-code', $hasil['instruksi']['qr_url']);
    }

    public function test_va_mengembalikan_nomor_va_dan_bank(): void
    {
        $booking = $this->buatBooking();

        $service = $this->mockCharge([
            'transaction_id' => 'trx-va-1',
            'va_numbers' => [
                ['bank' => 'bca', 'va_number' => '9881234567890'],
            ],
        ]);

        $hasil = $service->createTransaction($booking, 'va');

        $this->assertSame('va', $hasil['instruksi']['tipe']);
        $this->assertSame('bca', $hasil['instruksi']['bank']);
        $this->assertSame('9881234567890', $hasil['instruksi']['nomor_va']);
    }

    public function test_ewallet_mengembalikan_redirect_url_dan_qr_url(): void
    {
        $booking = $this->buatBooking();

        $service = $this->mockCharge([
            'transaction_id' => 'trx-gopay-1',
            'actions' => [
                ['name' => 'generate-qr-code', 'url' => 'https://api.sandbox.midtrans.com/gopay/trx-gopay-1/qr-code'],
                ['name' => 'deeplink-redirect', 'url' => 'https://gojek.link/gopay/trx-gopay-1'],
            ],
        ]);

        $hasil = $service->createTransaction($booking, 'ewallet');

        $this->assertSame('ewallet', $hasil['instruksi']['tipe']);
        $this->assertSame('https://gojek.link/gopay/trx-gopay-1', $hasil['instruksi']['redirect_url']);
        $this->assertSame('https://api.sandbox.midtrans.com/gopay/trx-gopay-1/qr-code', $hasil['instruksi']['qr_url']);
    }

    public function test_menyimpan_record_pembayaran_dengan_status_pending(): void
    {
        $booking = $this->buatBooking(['total_bayar' => 150000]);

        $service = $this->mockCharge([
            'transaction_id' => 'trx-simpan-1',
            'actions' => [],
        ]);

        $service->createTransaction($booking, 'qris');

        $this->assertDatabaseHas('pembayaran', [
            'tenant_id' => $booking->tenant_id,
            'booking_id' => $booking->id,
            'metode' => 'qris',
            'jumlah' => 150000,
            'status' => 'pending',
            'kode_transaksi_gateway' => 'trx-simpan-1',
        ]);
    }

    public function test_metode_tidak_dikenal_melempar_exception(): void
    {
        $booking = $this->buatBooking();
        $service = new PaymentService;

        $this->expectException(InvalidArgumentException::class);

        $service->createTransaction($booking, 'kartu-kredit');
    }

    public function test_customer_tanpa_email_pakai_email_default(): void
    {
        $booking = $this->buatBooking();

        $capturedParams = null;
        $service = $this->partialMock(PaymentService::class, function ($mock) use (&$capturedParams) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('chargeMidtrans')
                ->once()
                ->withArgs(function ($params) use (&$capturedParams) {
                    $capturedParams = $params;

                    return true;
                })
                ->andReturn(['transaction_id' => 'trx-1', 'actions' => []]);
        });

        $service->createTransaction($booking, 'qris');

        $this->assertSame('customer@greendeahan.com', $capturedParams['customer_details']['email']);
        $this->assertSame('Budi Santoso', $capturedParams['customer_details']['first_name']);
        $this->assertSame($booking->kode_booking, $capturedParams['transaction_details']['order_id']);
        $this->assertSame(100000, $capturedParams['transaction_details']['gross_amount']);
        $this->assertSame('qris', $capturedParams['payment_type']);
    }
}
