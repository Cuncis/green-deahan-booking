<?php

namespace Tests\Feature;

use App\Models\Artikel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_berisi_halaman_publik_dan_artikel_aktif_tanpa_v2(): void
    {
        $aktif = Artikel::factory()->create(['status_aktif' => true, 'slug' => 'artikel-aktif']);
        Artikel::factory()->create(['status_aktif' => false, 'slug' => 'artikel-draft']);

        $response = $this->get('http://greendeahan.com/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('<loc>http://greendeahan.com/tentang-kami</loc>', false);
        $response->assertSee('<loc>http://greendeahan.com/blog/'.$aktif->slug.'</loc>', false);
        $response->assertDontSee('artikel-draft');
        $response->assertDontSee('/v2');
    }

    public function test_sitemap_di_www_memakai_url_www(): void
    {
        $this->get('http://www.greendeahan.com/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>http://www.greendeahan.com/galeri</loc>', false);
    }

    public function test_halaman_v2_diberi_noindex(): void
    {
        $this->get('http://greendeahan.com/v2')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
