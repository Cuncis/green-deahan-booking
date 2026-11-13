<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Produk & Layanan > Bangun Lapangan (/bangun-lapangan) di situs korporat.
 */
class BangunLapanganPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_bangun_lapangan_bisa_dibuka_dengan_meta_seo(): void
    {
        $this->get('http://greendeahan.com/bangun-lapangan')
            ->assertOk()
            ->assertSee('<title inertia>Jasa Bangun Lapangan Futsal, Mini Soccer, Padel &amp; Badminton - Green Deahan Sport</title>', false)
            ->assertDontSee('<meta name="robots"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('BangunLapangan')
                ->where('meta.description', fn (string $deskripsi) => str_contains($deskripsi, 'Konsultasi, survey & RAB gratis'))
            );

        $this->get('http://www.greendeahan.com/bangun-lapangan')->assertOk();
    }

    public function test_bangun_lapangan_masuk_sitemap(): void
    {
        $this->get('http://greendeahan.com/sitemap.xml')->assertOk()->assertSee('/bangun-lapangan');
    }

    public function test_subdomain_tenant_tidak_terpengaruh_rute_bangun_lapangan(): void
    {
        $this->get('http://localhost/bangun-lapangan')->assertNotFound();
    }
}
