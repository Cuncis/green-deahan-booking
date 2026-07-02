<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_bisa_diakses_di_domain_utama(): void
    {
        $response = $this->get('http://greendeahan.com/');

        $response->assertOk();
        $response->assertViewIs('pages.home');
        $response->assertSee('Green Deahan Sport');
        $response->assertSee('Bangun Lapangan Olahraga dari', false);
    }

    public function test_homepage_bisa_diakses_lewat_www(): void
    {
        $response = $this->get('http://www.greendeahan.com/');

        $response->assertOk();
        $response->assertViewIs('pages.home');
    }

    public function test_homepage_tidak_mengandung_emoji_mentah(): void
    {
        $response = $this->get('http://greendeahan.com/');

        $response->assertDontSee('⚽');
        $response->assertDontSee('🏗');
        $response->assertDontSee('🛡');
        $response->assertDontSee('💰');
        $response->assertDontSee('⚡');
        $response->assertDontSee('🗺');
        $response->assertDontSee('⭐');
        $response->assertDontSee('📞');
        $response->assertDontSee('✉️');
        $response->assertDontSee('🕐');
    }

    /**
     * Regresi paling penting: domain greendeahan.com dikecualikan dari
     * IdentifikasiTenant supaya bisa tampilkan homepage platform, tapi
     * subdomain tenant (xxx.greendeahan.com) dan custom domain klien harus
     * TETAP menampilkan halaman booking seperti sebelumnya, bukan ikut
     * kena homepage ini.
     */
    public function test_subdomain_tenant_tetap_menampilkan_halaman_booking(): void
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->get('http://localhost/');

        $response->assertOk();
        $response->assertViewIs('pages.booking');
    }

    public function test_domain_tenant_greendeahan_subdomain_tetap_menampilkan_halaman_booking(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'arena-uji.greendeahan.com',
            'status_aktif' => true,
        ]);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->get('http://arena-uji.greendeahan.com/');

        $response->assertOk();
        $response->assertViewIs('pages.booking');
    }
}
