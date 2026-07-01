<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentifikasiTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_ke_domain_yang_tidak_terdaftar_mengembalikan_404(): void
    {
        $response = $this->get('http://tidak-terdaftar.test/');

        $response->assertNotFound();
    }

    public function test_request_ke_tenant_nonaktif_mengembalikan_404(): void
    {
        Tenant::factory()->create([
            'domain' => 'nonaktif.test',
            'status_aktif' => false,
        ]);

        $response = $this->get('http://nonaktif.test/');

        $response->assertNotFound();
    }

    public function test_request_ke_tenant_yang_masa_aktifnya_sudah_berakhir_mengembalikan_403(): void
    {
        Tenant::factory()->create([
            'domain' => 'kadaluarsa.test',
            'status_aktif' => true,
            'tanggal_berakhir' => now()->subDay(),
        ]);

        $response = $this->get('http://kadaluarsa.test/');

        $response->assertForbidden();
    }

    public function test_request_ke_tenant_aktif_berhasil_dan_tenant_terbind_ke_container(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'aktif.test',
            'status_aktif' => true,
            'tanggal_berakhir' => now()->addYear(),
        ]);

        $response = $this->get('http://aktif.test/');

        $response->assertOk();
        $this->assertTrue(app()->bound('tenant'));
        $this->assertTrue(app('tenant')->is($tenant));
        $this->assertTrue(app('tenant')->relationLoaded('fitur'));
    }

    public function test_tenant_tanpa_tanggal_berakhir_tidak_dianggap_kadaluarsa(): void
    {
        Tenant::factory()->create([
            'domain' => 'unlimited.test',
            'status_aktif' => true,
            'tanggal_berakhir' => null,
        ]);

        $response = $this->get('http://unlimited.test/');

        $response->assertOk();
    }

    public function test_tenant_bisa_diakses_lewat_custom_domain(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'klien.greendeahan.com',
            'custom_domain' => 'klien-sendiri.com',
            'status_aktif' => true,
            'tanggal_berakhir' => now()->addYear(),
        ]);

        $response = $this->get('http://klien-sendiri.com/');

        $response->assertOk();
        $this->assertTrue(app('tenant')->is($tenant));
    }

    public function test_tenant_masih_bisa_diakses_lewat_domain_default_walau_punya_custom_domain(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'klien-dua.greendeahan.com',
            'custom_domain' => 'klien-dua-sendiri.com',
            'status_aktif' => true,
            'tanggal_berakhir' => now()->addYear(),
        ]);

        $response = $this->get('http://klien-dua.greendeahan.com/');

        $response->assertOk();
        $this->assertTrue(app('tenant')->is($tenant));
    }

    public function test_tenant_nonaktif_tidak_bisa_diakses_lewat_custom_domain_juga(): void
    {
        Tenant::factory()->create([
            'domain' => 'klien-off.greendeahan.com',
            'custom_domain' => 'klien-off-sendiri.com',
            'status_aktif' => false,
        ]);

        $response = $this->get('http://klien-off-sendiri.com/');

        $response->assertNotFound();
    }
}
