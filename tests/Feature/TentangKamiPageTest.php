<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TentangKamiPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tentang_kami_merender_halaman_react_dengan_meta(): void
    {
        $response = $this->get('http://greendeahan.com/tentang-kami');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('TentangKami')
            ->where('meta.title', 'Tentang Kami - Green Deahan Sport')
            ->has('meta.description')
        );
        $response->assertSee('<title inertia>Tentang Kami - Green Deahan Sport</title>', false);
        $response->assertSee('<meta name="description" content="Green Deahan Sport, jasa pembuatan lapangan', false);
        $response->assertSee('<link rel="canonical" href="http://greendeahan.com/tentang-kami">', false);
        $response->assertDontSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_tentang_kami_bisa_diakses_lewat_www(): void
    {
        $this->get('http://www.greendeahan.com/tentang-kami')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('TentangKami'));
    }

    public function test_halaman_konsep_lama_dialihkan_permanen_ke_tentang_kami(): void
    {
        $this->get('http://greendeahan.com/konsep')
            ->assertStatus(301)
            ->assertRedirect('/tentang-kami');
    }

    public function test_navigasi_blade_menautkan_tentang_kami_bukan_konsep(): void
    {
        $response = $this->get('http://greendeahan.com/galeri');

        $response->assertOk();
        $response->assertSee('href="/tentang-kami"', false);
        $response->assertDontSee('href="/konsep"', false);
    }

    public function test_subdomain_tenant_tidak_terpengaruh_rute_tentang_kami(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'arena-uji-tentang.greendeahan.com',
            'status_aktif' => true,
        ]);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->get('http://arena-uji-tentang.greendeahan.com/');

        $response->assertOk();
        $response->assertViewIs('pages.booking');
    }
}
