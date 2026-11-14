<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Layanan > Perawatan (/perawatan) di situs korporat.
 */
class PerawatanPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_perawatan_bisa_dibuka_dengan_meta_seo(): void
    {
        $this->get('http://greendeahan.com/perawatan')
            ->assertOk()
            ->assertSee('<title inertia>Perawatan &amp; Renovasi Lapangan Olahraga - Green Deahan Sport</title>', false)
            ->assertDontSee('<meta name="robots"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Perawatan')
                ->where('meta.description', fn (string $deskripsi) => str_contains($deskripsi, 'Pengecekan gratis'))
            );

        $this->get('http://www.greendeahan.com/perawatan')->assertOk();
    }

    public function test_perawatan_masuk_sitemap(): void
    {
        $this->get('http://greendeahan.com/sitemap.xml')->assertOk()->assertSee('/perawatan');
    }

    public function test_subdomain_tenant_tidak_terpengaruh_rute_perawatan(): void
    {
        $this->get('http://localhost/perawatan')->assertNotFound();
    }
}
