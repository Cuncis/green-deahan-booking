<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantTagihanPerpanjangan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NonaktifkanTenantKadaluarsaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function buatTenantTerlambat(int $hariTerlambat, array $override = []): Tenant
    {
        return Tenant::factory()->create(array_merge([
            'status_aktif' => true,
            'tanggal_berakhir' => today()->subDays($hariTerlambat),
            'email_admin' => 'owner@arenabaru.test',
        ], $override));
    }

    private function buatTagihanMenunggu(Tenant $tenant): TenantTagihanPerpanjangan
    {
        return TenantTagihanPerpanjangan::create([
            'tenant_id' => $tenant->id,
            'kode' => TenantTagihanPerpanjangan::generateKode(),
            'jumlah' => Tenant::hitungHargaPerpanjangan($tenant->paket),
            'link_pembayaran' => 'https://sandbox.mayar.club/invoice/fake',
            'status' => 'menunggu',
            'dikirim_pada' => now(),
        ]);
    }

    public function test_terlambat_3_hari_dapat_peringatan_terakhir_tetap_aktif(): void
    {
        $tenant = $this->buatTenantTerlambat(3);
        $tagihan = $this->buatTagihanMenunggu($tenant);

        $this->artisan('tenant:nonaktifkan-tenant-kadaluarsa')->assertSuccessful();

        $tenant->refresh();
        $this->assertTrue($tenant->status_aktif);
        $this->assertNotNull($tagihan->fresh()->peringatan_terakhir_terkirim_at);
    }

    public function test_peringatan_tidak_dikirim_dua_kali_di_hari_yang_sama(): void
    {
        $tenant = $this->buatTenantTerlambat(3);
        $tagihan = $this->buatTagihanMenunggu($tenant);

        $this->artisan('tenant:nonaktifkan-tenant-kadaluarsa')->assertSuccessful();
        $waktuPertama = $tagihan->fresh()->peringatan_terakhir_terkirim_at;

        $this->artisan('tenant:nonaktifkan-tenant-kadaluarsa')->assertSuccessful();
        $waktuKedua = $tagihan->fresh()->peringatan_terakhir_terkirim_at;

        $this->assertTrue($waktuPertama->equalTo($waktuKedua));
    }

    public function test_terlambat_8_hari_dinonaktifkan(): void
    {
        $tenant = $this->buatTenantTerlambat(8);
        $this->buatTagihanMenunggu($tenant);

        $this->artisan('tenant:nonaktifkan-tenant-kadaluarsa')->assertSuccessful();

        $this->assertFalse($tenant->fresh()->status_aktif);
    }

    public function test_terlambat_tepat_7_hari_dinonaktifkan(): void
    {
        $tenant = $this->buatTenantTerlambat(7);

        $this->artisan('tenant:nonaktifkan-tenant-kadaluarsa')->assertSuccessful();

        $this->assertFalse($tenant->fresh()->status_aktif);
    }

    public function test_terlambat_tanpa_tagihan_perpanjangan_tetap_dinonaktifkan(): void
    {
        $tenant = $this->buatTenantTerlambat(10);

        $this->artisan('tenant:nonaktifkan-tenant-kadaluarsa')->assertSuccessful();

        $this->assertFalse($tenant->fresh()->status_aktif);
    }

    public function test_tenant_belum_jatuh_tempo_tidak_disentuh(): void
    {
        $tenant = Tenant::factory()->create([
            'status_aktif' => true,
            'tanggal_berakhir' => today()->addDays(5),
        ]);

        $this->artisan('tenant:nonaktifkan-tenant-kadaluarsa')->assertSuccessful();

        $this->assertTrue($tenant->fresh()->status_aktif);
    }
}
