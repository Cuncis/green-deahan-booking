<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BlogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_merender_halaman_react_dengan_meta(): void
    {
        $response = $this->get('http://greendeahan.com/blog');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Blog')
            ->where('meta.title', 'Blog - Green Deahan Sport')
        );
        $response->assertSee('<title inertia>Blog - Green Deahan Sport</title>', false);
    }

    public function test_blog_bisa_diakses_lewat_www(): void
    {
        $this->get('http://www.greendeahan.com/blog')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Blog'));
    }

    public function test_blog_hanya_menampilkan_artikel_yang_terbit_terbaru_dulu(): void
    {
        Artikel::factory()->create(['judul' => 'Artikel Lama', 'status_aktif' => true, 'tanggal_terbit' => '2026-01-01']);
        Artikel::factory()->create(['judul' => 'Artikel Baru', 'status_aktif' => true, 'tanggal_terbit' => '2026-05-01']);
        Artikel::factory()->create(['judul' => 'Artikel Draft', 'status_aktif' => false]);

        $this->get('http://greendeahan.com/blog')
            ->assertInertia(fn (Assert $page) => $page
                ->has('artikel', 2)
                ->where('artikel.0.judul', 'Artikel Baru')
                ->where('artikel.1.judul', 'Artikel Lama')
            );
    }

    public function test_menit_baca_dihitung_dari_jumlah_kata_minimal_satu(): void
    {
        Artikel::factory()->create(['slug' => 'pendek', 'konten' => '<p>Sedikit kata saja.</p>', 'tanggal_terbit' => '2026-05-02']);
        Artikel::factory()->create(['slug' => 'panjang', 'konten' => '<p>'.str_repeat('kata ', 600).'</p>', 'tanggal_terbit' => '2026-05-01']);

        $this->get('http://greendeahan.com/blog')
            ->assertInertia(fn (Assert $page) => $page
                ->where('artikel.0.menitBaca', 1)
                ->where('artikel.1.menitBaca', 3)
            );
    }

    public function test_detail_artikel_bisa_diakses_lewat_slug_dengan_meta_artikel(): void
    {
        $artikel = Artikel::factory()->create([
            'judul' => 'Cara Merawat Lapangan Futsal',
            'slug' => 'cara-merawat-lapangan-futsal',
            'ringkasan' => 'Ringkasan artikel perawatan.',
            'konten' => '<p>Isi artikel lengkap di sini.</p>',
            'foto_url' => 'https://cdn.example.com/foto.jpg',
            'tanggal_terbit' => '2026-05-03',
        ]);

        $response = $this->get('http://greendeahan.com/blog/'.$artikel->slug);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('BlogShow')
            ->where('artikel.judul', 'Cara Merawat Lapangan Futsal')
            ->where('artikel.konten', '<p>Isi artikel lengkap di sini.</p>')
            ->where('meta.type', 'article')
            ->where('artikel.tanggal', '3 Mei 2026')
        );
        $response->assertSee('<title inertia>Cara Merawat Lapangan Futsal - Green Deahan Sport</title>', false);
        $response->assertSee('<meta name="description" content="Ringkasan artikel perawatan.">', false);
        $response->assertSee('<meta property="og:type" content="article">', false);
        $response->assertSee('<meta property="og:image" content="https://cdn.example.com/foto.jpg">', false);
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

        $response->assertInertia(fn (Assert $page) => $page
            ->has('terkait', 1)
            ->where('terkait.0.id', $terkait->id)
        );
        $response->assertDontSee('Artikel Kategori Lain');
    }

    public function test_blog_blade_lama_pindah_ke_v2_dengan_noindex(): void
    {
        Artikel::factory()->create(['judul' => 'Artikel Terbit', 'slug' => 'artikel-terbit', 'status_aktif' => true]);
        Artikel::factory()->create(['judul' => 'Artikel Draft', 'status_aktif' => false]);

        $response = $this->get('http://greendeahan.com/v2/blog');

        $response->assertOk();
        $response->assertViewIs('pages.blog');
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertSee("'/v2/blog/' + post.slug", false);
        $response->assertViewHas('artikel', fn ($artikel) => collect($artikel)->pluck('judul')->contains('Artikel Terbit')
            && ! collect($artikel)->pluck('judul')->contains('Artikel Draft'));
    }

    public function test_detail_artikel_blade_lama_di_v2_dengan_noindex(): void
    {
        $artikel = Artikel::factory()->create([
            'kategori' => 'Futsal',
            'slug' => 'artikel-v2',
            'konten' => '<p>Isi artikel versi lama.</p>',
        ]);
        $terkait = Artikel::factory()->create(['kategori' => 'Futsal', 'slug' => 'terkait-v2']);

        $response = $this->get('http://greendeahan.com/v2/blog/'.$artikel->slug);

        $response->assertOk();
        $response->assertViewIs('pages.blog-show');
        $response->assertSee('Isi artikel versi lama.', false);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertSee('href="/v2/blog/'.$terkait->slug.'"', false);
        $response->assertSee('href="/v2/blog"', false);
    }

    public function test_detail_artikel_v2_draft_atau_tidak_ada_404(): void
    {
        $draft = Artikel::factory()->create(['status_aktif' => false, 'slug' => 'draft-v2']);

        $this->get('http://greendeahan.com/v2/blog/'.$draft->slug)->assertNotFound();
        $this->get('http://greendeahan.com/v2/blog/tidak-ada')->assertNotFound();
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
