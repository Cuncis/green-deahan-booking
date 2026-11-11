<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\GaleriItem;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GaleriPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_galeri_merender_halaman_react_dengan_meta(): void
    {
        $response = $this->get('http://greendeahan.com/galeri');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Galeri')
            ->where('meta.title', 'Galeri Proyek - Green Deahan Sport')
        );
        $response->assertSee('<title inertia>Galeri Proyek - Green Deahan Sport</title>', false);
        $response->assertDontSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_galeri_bisa_diakses_lewat_www(): void
    {
        $this->get('http://www.greendeahan.com/galeri')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Galeri'));
    }

    public function test_galeri_mengirim_semua_kategori_dan_item_dari_database(): void
    {
        GaleriItem::factory()->create(['kategori' => 'futsal', 'judul' => 'Futsal Indoor Jakarta Selatan', 'urutan' => 1, 'tampilan_besar' => true]);
        GaleriItem::factory()->create(['kategori' => 'proses', 'judul' => 'Survey Lokasi Sebelum Mulai', 'urutan' => 2]);

        $this->get('http://greendeahan.com/galeri')
            ->assertInertia(fn (Assert $page) => $page
                ->has('kategori', 6)
                ->where('kategori.0', ['id' => 'semua', 'label' => 'Semua'])
                ->where('kategori.5', ['id' => 'proses', 'label' => 'Proses Konstruksi'])
                ->has('items', 2)
                ->where('items.0.judul', 'Futsal Indoor Jakarta Selatan')
                ->where('items.0.besar', true)
                ->where('items.1.kategori', 'proses')
            );
    }

    public function test_galeri_tidak_menampilkan_item_nonaktif_atau_yang_masih_dikerjakan(): void
    {
        GaleriItem::factory()->create(['judul' => 'Item Tampil', 'status_aktif' => true, 'kota' => 'Surabaya']);
        GaleriItem::factory()->create(['judul' => 'Item Disembunyikan', 'status_aktif' => false]);
        GaleriItem::factory()->create(['judul' => 'Proyek Berjalan', 'status_aktif' => true, 'kota' => 'On Progress']);

        $this->get('http://greendeahan.com/galeri')
            ->assertInertia(fn (Assert $page) => $page
                ->has('items', 1)
                ->where('items.0.judul', 'Item Tampil')
            );
    }

    public function test_galeri_blade_lama_pindah_ke_v2_dengan_noindex(): void
    {
        GaleriItem::factory()->create(['judul' => 'Item Tampil', 'status_aktif' => true]);
        GaleriItem::factory()->create(['judul' => 'Item Disembunyikan', 'status_aktif' => false]);

        $response = $this->get('http://greendeahan.com/v2/galeri');

        $response->assertOk();
        $response->assertViewIs('pages.galeri');
        $response->assertSee('Galeri Proyek');
        $response->assertSee('Item Tampil');
        $response->assertDontSee('Item Disembunyikan');
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_galeri_tidak_mengandung_emoji_mentah(): void
    {
        GaleriItem::factory()->create();

        $response = $this->get('http://greendeahan.com/v2/galeri');

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
        $response->assertViewIs('booking');
    }
}
