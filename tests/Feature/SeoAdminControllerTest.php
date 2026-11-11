<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\PengaturanSitus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SeoAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function dataValid(array $override = []): array
    {
        return array_merge([
            'nama_situs' => 'Green Deahan Sport',
            'meta_deskripsi' => 'Deskripsi situs.',
            'og_image_url' => 'https://example.com/og.png',
            'ga4_measurement_id' => 'G-ABC123XYZ9',
            'gtm_id' => 'GTM-PM8JCFX6',
            'gsc_verifikasi' => 'abcdefghij0123456789_-KLMN',
            'indeks_mesin_pencari' => '1',
        ], $override);
    }

    public function test_non_superadmin_tidak_bisa_akses_pengaturan_seo(): void
    {
        $this->actingAs(User::factory()->create())->get(route('superadmin.seo'))->assertForbidden();
    }

    public function test_superadmin_bisa_buka_halaman_pengaturan_seo(): void
    {
        $this->actingAs($this->superadmin())->get(route('superadmin.seo'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('superadmin/Seo')
                ->where('pengaturan.nama_situs', 'Green Deahan Sport')
                ->where('pengaturan.indeks_mesin_pencari', true)
                ->where('pengaturan.ga4_measurement_id', '')
                ->where('simpanUrl', route('superadmin.seo.update'))
            );
    }

    public function test_superadmin_bisa_simpan_pengaturan(): void
    {
        $response = $this->actingAs($this->superadmin())->put(route('superadmin.seo.update'), $this->dataValid());

        $response->assertRedirect(route('superadmin.seo'));
        $response->assertSessionHas('success');
        $this->assertSame('G-ABC123XYZ9', PengaturanSitus::ambil('ga4_measurement_id'));
    }

    public function test_id_google_tag_berawalan_gt_diterima(): void
    {
        $this->actingAs($this->superadmin())->put(route('superadmin.seo.update'), $this->dataValid([
            'ga4_measurement_id' => 'GT-ABC1234',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('GT-ABC1234', PengaturanSitus::ambil('ga4_measurement_id'));
    }

    public function test_format_ga4_dan_gsc_divalidasi(): void
    {
        $response = $this->actingAs($this->superadmin())->put(route('superadmin.seo.update'), $this->dataValid([
            'ga4_measurement_id' => 'UA-12345',
            'gtm_id' => 'bukan-gtm',
            'gsc_verifikasi' => '"><script>alert(1)</script>',
        ]));

        $response->assertSessionHasErrors(['ga4_measurement_id', 'gtm_id', 'gsc_verifikasi']);
    }

    public function test_halaman_publik_memuat_ga4_dan_verifikasi_search_console(): void
    {
        PengaturanSitus::simpan($this->dataValid());

        $this->get('http://greendeahan.com/blog')
            ->assertOk()
            ->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ9', false)
            ->assertSee('gtm.js?id=GTM-PM8JCFX6', false)
            ->assertSee('ns.html?id=GTM-PM8JCFX6', false)
            ->assertSee('name="google-site-verification" content="abcdefghij0123456789_-KLMN"', false)
            ->assertSee('property="og:image" content="https://example.com/og.png"', false)
            ->assertDontSee('noindex');
    }

    public function test_tanpa_ga4_script_tidak_dirender_dan_noindex_bisa_diaktifkan(): void
    {
        PengaturanSitus::simpan($this->dataValid([
            'ga4_measurement_id' => '',
            'gtm_id' => '',
            'indeks_mesin_pencari' => '0',
        ]));

        $this->get('http://greendeahan.com/blog')
            ->assertOk()
            ->assertDontSee('googletagmanager', false)
            ->assertDontSee('gtm.js', false)
            ->assertSee('noindex, nofollow', false);
    }

    public function test_halaman_artikel_memakai_ringkasan_sebagai_deskripsi(): void
    {
        $artikel = Artikel::factory()->create(['status_aktif' => true, 'ringkasan' => 'Ringkasan khusus artikel ini.']);

        $this->get(route('blog.show', $artikel->slug))
            ->assertOk()
            ->assertSee('name="description" content="Ringkasan khusus artikel ini."', false)
            ->assertSee('content="article"', false);
    }

    public function test_halaman_booking_tenant_tidak_memuat_analytics_korporat(): void
    {
        PengaturanSitus::simpan(['ga4_measurement_id' => 'G-ABC123XYZ9', 'gtm_id' => 'GTM-PM8JCFX6']);

        $this->get('/')
            ->assertOk()
            ->assertViewIs('booking')
            ->assertDontSee('googletagmanager', false)
            ->assertDontSee('G-ABC123XYZ9', false)
            ->assertDontSee('GTM-PM8JCFX6', false);
    }
}
