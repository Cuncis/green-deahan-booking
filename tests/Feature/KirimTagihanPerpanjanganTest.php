<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantTagihanPerpanjangan;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class KirimTagihanPerpanjanganTest extends TestCase
{
    use RefreshDatabase;

    /**
     * createInvoice() di-mock supaya tidak ada panggilan network sungguhan
     * ke sandbox Mayar, sama seperti pola di BookingControllerTest.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('createInvoice')->andReturn([
                    'data' => [
                        'id' => 'fake-perpanjangan-invoice-id',
                        'link' => 'https://sandbox.mayar.club/invoice/fake-perpanjangan-invoice-id',
                    ],
                ]);
        });
    }

    private function buatTenantAktif(array $override = []): Tenant
    {
        return Tenant::factory()->create(array_merge([
            'paket' => 'pro',
            'status_aktif' => true,
            'email_admin' => 'owner@arenabaru.test',
        ], $override));
    }

    public function test_tenant_14_hari_dari_jatuh_tempo_dapat_tagihan(): void
    {
        $tenant = $this->buatTenantAktif(['tanggal_berakhir' => today()->addDays(14)]);

        $this->artisan('tenant:kirim-tagihan-perpanjangan')->assertSuccessful();

        $this->assertDatabaseHas('tenant_tagihan_perpanjangan', [
            'tenant_id' => $tenant->id,
            'status' => 'menunggu',
            'jumlah' => Tenant::hitungHargaPerpanjangan('pro'),
        ]);
    }

    public function test_tenant_di_dalam_rentang_14_hari_dapat_tagihan(): void
    {
        $tenant = $this->buatTenantAktif(['tanggal_berakhir' => today()->addDays(5)]);

        $this->artisan('tenant:kirim-tagihan-perpanjangan')->assertSuccessful();

        $this->assertDatabaseHas('tenant_tagihan_perpanjangan', ['tenant_id' => $tenant->id]);
    }

    public function test_tenant_masih_jauh_dari_jatuh_tempo_tidak_dapat_tagihan(): void
    {
        $tenant = $this->buatTenantAktif(['tanggal_berakhir' => today()->addDays(20)]);

        $this->artisan('tenant:kirim-tagihan-perpanjangan')->assertSuccessful();

        $this->assertDatabaseMissing('tenant_tagihan_perpanjangan', ['tenant_id' => $tenant->id]);
    }

    public function test_tenant_sudah_punya_tagihan_menunggu_tidak_dapat_duplikat(): void
    {
        $tenant = $this->buatTenantAktif(['tanggal_berakhir' => today()->addDays(3)]);

        TenantTagihanPerpanjangan::create([
            'tenant_id' => $tenant->id,
            'kode' => TenantTagihanPerpanjangan::generateKode(),
            'jumlah' => Tenant::hitungHargaPerpanjangan('pro'),
            'status' => 'menunggu',
            'dikirim_pada' => now(),
        ]);

        $this->artisan('tenant:kirim-tagihan-perpanjangan')->assertSuccessful();

        $this->assertDatabaseCount('tenant_tagihan_perpanjangan', 1);
    }

    public function test_tenant_nonaktif_tidak_dapat_tagihan(): void
    {
        $tenant = $this->buatTenantAktif([
            'status_aktif' => false,
            'tanggal_berakhir' => today()->addDays(5),
        ]);

        $this->artisan('tenant:kirim-tagihan-perpanjangan')->assertSuccessful();

        $this->assertDatabaseMissing('tenant_tagihan_perpanjangan', ['tenant_id' => $tenant->id]);
    }

    public function test_jumlah_tagihan_70_persen_dari_harga_tahun_pertama(): void
    {
        $tenant = $this->buatTenantAktif(['paket' => 'premium', 'tanggal_berakhir' => today()->addDays(1)]);

        $this->artisan('tenant:kirim-tagihan-perpanjangan')->assertSuccessful();

        $this->assertDatabaseHas('tenant_tagihan_perpanjangan', [
            'tenant_id' => $tenant->id,
            'jumlah' => 3_150_000,
        ]);
    }
}
