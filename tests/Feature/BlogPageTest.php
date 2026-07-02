<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_bisa_diakses_di_domain_utama(): void
    {
        $response = $this->get('http://greendeahan.com/blog');

        $response->assertOk();
        $response->assertViewIs('pages.blog');
    }

    public function test_blog_bisa_diakses_lewat_www(): void
    {
        $response = $this->get('http://www.greendeahan.com/blog');

        $response->assertOk();
        $response->assertViewIs('pages.blog');
    }

    public function test_blog_hanya_menampilkan_artikel_yang_terbit(): void
    {
        Artikel::factory()->create(['judul' => 'Artikel Terbit', 'status_aktif' => true]);
        Artikel::factory()->create(['judul' => 'Artikel Draft', 'status_aktif' => false]);

        $response = $this->get('http://greendeahan.com/blog');

        $response->assertViewHas('artikel', fn ($artikel) => collect($artikel)->pluck('judul')->contains('Artikel Terbit')
            && ! collect($artikel)->pluck('judul')->contains('Artikel Draft'));
    }

    public function test_detail_artikel_bisa_diakses_lewat_slug(): void
    {
        $artikel = Artikel::factory()->create([
            'judul' => 'Cara Merawat Lapangan Futsal',
            'slug' => 'cara-merawat-lapangan-futsal',
            'konten' => '<p>Isi artikel lengkap di sini.</p>',
        ]);

        $response = $this->get('http://greendeahan.com/blog/'.$artikel->slug);

        $response->assertOk();
        $response->assertViewIs('pages.blog-show');
        $response->assertSee('Cara Merawat Lapangan Futsal');
        $response->assertSee('Isi artikel lengkap di sini.', false);
    }

    public function test_detail_artikel_yang_tidak_ada_404(): void
    {
        $response = $this->get('http://greendeahan.com/blog/slug-tidak-ada');

        $response->assertNotFound();
    }

    public function test_detail_artikel_draft_404(): void
    {
        $artikel = Artikel::factory()->create(['status_aktif' => false, 'slug' => 'artikel-draft']);

        $response = $this->get('http://greendeahan.com/blog/'.$artikel->slug);

        $response->assertNotFound();
    }

    public function test_detail_artikel_menampilkan_artikel_terkait_kategori_sama(): void
    {
        $artikel = Artikel::factory()->create(['kategori' => 'Futsal', 'slug' => 'artikel-utama']);
        $terkait = Artikel::factory()->create(['kategori' => 'Futsal', 'judul' => 'Artikel Terkait Futsal']);
        Artikel::factory()->create(['kategori' => 'Padel', 'judul' => 'Artikel Kategori Lain']);

        $response = $this->get('http://greendeahan.com/blog/'.$artikel->slug);

        $response->assertViewHas('terkait', fn ($terkaitList) => $terkaitList->pluck('id')->contains($terkait->id)
            && ! $terkaitList->pluck('judul')->contains('Artikel Kategori Lain'));
    }

    public function test_subdomain_tenant_tidak_terpengaruh_rute_blog(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'arena-uji-blog.greendeahan.com',
            'status_aktif' => true,
        ]);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->get('http://arena-uji-blog.greendeahan.com/');

        $response->assertOk();
        $response->assertViewIs('pages.booking');
    }
}
