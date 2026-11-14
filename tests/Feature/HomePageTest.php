<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_merender_halaman_react_fuzio_di_domain_utama(): void
    {
        $response = $this->get('http://greendeahan.com/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Home'));
    }

    public function test_homepage_bisa_diakses_lewat_www(): void
    {
        $response = $this->get('http://www.greendeahan.com/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Home'));
    }

    public function test_homepage_bisa_diindeks_dan_memuat_seo_favicon(): void
    {
        $response = $this->get('http://greendeahan.com/');

        $response->assertDontSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertSee('<link rel="canonical" href="http://greendeahan.com">', false);
        $response->assertSee('<meta name="description" content="Jasa pembuatan lapangan futsal', false);
        $response->assertSee('rel="icon"', false);
        $response->assertSee('Jasa Pembuatan Lapangan Olahraga Se-Indonesia</title>', false);
    }

    public function test_homepage_blade_lama_pindah_ke_v2_dengan_noindex(): void
    {
        $response = $this->get('http://greendeahan.com/v2');

        $response->assertOk();
        $response->assertViewIs('pages.home');
        $response->assertSee('Bangun Lapangan Olahraga dari', false);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_halaman_v2_lama_lainnya_sudah_dihapus(): void
    {
        foreach (['/v2/konsep'] as $path) {
            $this->get('http://greendeahan.com'.$path)->assertNotFound();
        }
    }

    public function test_homepage_v2_menyebut_vinyl_pertama_untuk_lantai_badminton(): void
    {
        $this->get('http://greendeahan.com/v2')
            ->assertOk()
            ->assertSee('lantai <strong>vinyl</strong>, <strong>interlock</strong>, atau <strong>kayu keras</strong>', false)
            ->assertSee('Lantai vinyl, interlock, atau kayu keras')
            ->assertDontSee('utama)');
    }

    public function test_homepage_v2_tidak_mengandung_emoji_mentah(): void
    {
        $response = $this->get('http://greendeahan.com/v2');

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
        $response->assertViewIs('booking');
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
        $response->assertViewIs('booking');
    }
}
