<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\GaleriItem;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GaleriPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_galeri_bisa_diakses_di_domain_utama(): void
    {
        $response = $this->get('http://greendeahan.com/galeri');

        $response->assertOk();
        $response->assertViewIs('pages.galeri');
        $response->assertSee('Galeri Proyek');
    }

    public function test_galeri_bisa_diakses_lewat_www(): void
    {
        $response = $this->get('http://www.greendeahan.com/galeri');

        $response->assertOk();
        $response->assertViewIs('pages.galeri');
    }

    public function test_galeri_menampilkan_semua_kategori_dan_item_dari_database(): void
    {
        GaleriItem::factory()->create(['kategori' => 'futsal', 'judul' => 'Futsal Indoor Jakarta Selatan']);
        GaleriItem::factory()->create(['kategori' => 'minisoccer', 'judul' => 'Mini Soccer Outdoor Bali']);
        GaleriItem::factory()->create(['kategori' => 'padel', 'judul' => 'Padel Premium Medan']);
        GaleriItem::factory()->create(['kategori' => 'badminton', 'judul' => 'Badminton 3 Court Bandung']);
        GaleriItem::factory()->create(['kategori' => 'proses', 'judul' => 'Survey Lokasi Sebelum Mulai']);

        $response = $this->get('http://greendeahan.com/galeri');

        $response->assertSee('Futsal');
        $response->assertSee('Mini Soccer');
        $response->assertSee('Padel');
        $response->assertSee('Badminton');
        $response->assertSee('Proses Konstruksi');
        $response->assertSee('Futsal Indoor Jakarta Selatan');
        $response->assertSee('Survey Lokasi Sebelum Mulai');
    }

    public function test_galeri_tidak_menampilkan_item_yang_nonaktif(): void
    {
        GaleriItem::factory()->create(['judul' => 'Item Tampil', 'status_aktif' => true]);
        GaleriItem::factory()->create(['judul' => 'Item Disembunyikan', 'status_aktif' => false]);

        $response = $this->get('http://greendeahan.com/galeri');

        $response->assertSee('Item Tampil');
        $response->assertDontSee('Item Disembunyikan');
    }

    public function test_galeri_tidak_mengandung_emoji_mentah(): void
    {
        GaleriItem::factory()->create();

        $response = $this->get('http://greendeahan.com/galeri');

        $response->assertDontSee('⚽');
        $response->assertDontSee('🟢');
        $response->assertDontSee('🎾');
        $response->assertDontSee('🏸');
        $response->assertDontSee('🏗');
        $response->assertDontSee('🏟');
        $response->assertDontSee('📸');
        $response->assertDontSee('📍');
        $response->assertDontSee('🧱');
        $response->assertDontSee('🔍');
    }

    /**
     * Regresi sama seperti homepage: subdomain tenant dan custom domain
     * klien harus tetap menampilkan halaman booking, bukan ikut kena
     * routing situs korporat.
     */
    public function test_subdomain_tenant_tidak_terpengaruh_rute_galeri(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'arena-uji-galeri.greendeahan.com',
            'status_aktif' => true,
        ]);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->get('http://arena-uji-galeri.greendeahan.com/');

        $response->assertOk();
        $response->assertViewIs('pages.booking');
    }
}
