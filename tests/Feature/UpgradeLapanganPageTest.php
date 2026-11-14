<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Layanan > Upgrade Lapangan (/upgrade-lapangan) di situs korporat.
 */
class UpgradeLapanganPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_upgrade_lapangan_bisa_dibuka_dengan_meta_seo(): void
    {
        $this->get('http://greendeahan.com/upgrade-lapangan')
            ->assertOk()
            ->assertSee('<title inertia>Upgrade Lapangan: Lampu LED, Atap, Jaring &amp; Ganti Rumput - Green Deahan Sport</title>', false)
            ->assertDontSee('<meta name="robots"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('UpgradeLapangan')
                ->where('meta.description', fn (string $deskripsi) => str_contains($deskripsi, 'Untuk semua lapangan'))
            );

        $this->get('http://www.greendeahan.com/upgrade-lapangan')->assertOk();
    }

    public function test_upgrade_lapangan_masuk_sitemap(): void
    {
        $this->get('http://greendeahan.com/sitemap.xml')->assertOk()->assertSee('/upgrade-lapangan');
    }

    public function test_subdomain_tenant_tidak_terpengaruh_rute_upgrade_lapangan(): void
    {
        $this->get('http://localhost/upgrade-lapangan')->assertNotFound();
    }
}
