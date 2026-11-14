<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Layanan > Bangun Lapangan (/bangun-lapangan) di situs korporat.
 */
class BangunLapanganPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_bangun_lapangan_bisa_dibuka_dengan_meta_seo(): void
    {
        $this->get('http://greendeahan.com/bangun-lapangan')
            ->assertOk()
            ->assertSee('<title inertia>Jasa Bangun Lapangan Futsal &amp; Padel - Green Deahan Sport</title>', false)
            ->assertDontSee('<meta name="robots"', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('BangunLapangan')
                ->where('meta.description', fn (string $deskripsi) => str_contains($deskripsi, 'Minta penawaran') && ! str_contains($deskripsi, 'gratis'))
            );

        $this->get('http://www.greendeahan.com/bangun-lapangan')->assertOk();
    }

    public function test_konten_bangun_lapangan_tanpa_harga_dan_tanpa_klaim_gratis(): void
    {
        $berkas = [
            'resources/js/data/bangunLapangan.ts',
            'resources/js/components/fuzio/layanan/BangunIntro.tsx',
            'resources/js/components/fuzio/layanan/JenisLapanganSec.tsx',
            'resources/js/components/fuzio/layanan/ProsesBangunSec.tsx',
            'resources/js/components/fuzio/layanan/BandingLantaiSec.tsx',
        ];

        foreach ($berkas as $path) {
            $isi = file_get_contents(base_path($path));

            $this->assertDoesNotMatchRegularExpression('/\bRp\s?\d|\d\s?(jt|juta)\b/i', $isi, "$path memuat harga");
            $this->assertStringNotContainsStringIgnoringCase('gratis', $isi, "$path memuat kata gratis");
            $this->assertStringNotContainsStringIgnoringCase('sistem booking', $isi, "$path menyebut sistem booking");
        }
    }

    public function test_faq_luar_kota_menyebut_luar_jawa_bukan_luar_surabaya(): void
    {
        $isi = file_get_contents(base_path('resources/js/data/kontak.ts'));

        $this->assertStringContainsString('Apakah bisa membangun di luar Jawa?', $isi);
        $this->assertStringContainsString('termasuk di luar Pulau Jawa', $isi);
        $this->assertStringNotContainsString('luar Surabaya', $isi);
    }

    public function test_kalkulator_balik_modal_default_futsal(): void
    {
        $isi = file_get_contents(base_path('resources/js/data/kalkulator.ts'));

        $this->assertMatchesRegularExpression("/kalkulatorDefaults = \\{\\s*type: 'futsal'/", $isi);
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
