<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KontakPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_kontak_merender_halaman_react_dengan_meta(): void
    {
        $response = $this->get('http://greendeahan.com/kontak');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Kontak')
            ->where('meta.title', 'Kontak Kami - Green Deahan Sport')
        );
        $response->assertSee('<title inertia>Kontak Kami - Green Deahan Sport</title>', false);
        $response->assertSee('<meta name="description" content="Hubungi Green Deahan Sport', false);
        $response->assertDontSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_kontak_bisa_diakses_lewat_www(): void
    {
        $this->get('http://www.greendeahan.com/kontak')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Kontak'));
    }

    public function test_kontak_blade_lama_pindah_ke_v2_dengan_noindex(): void
    {
        $response = $this->get('http://greendeahan.com/v2/kontak');

        $response->assertOk();
        $response->assertViewIs('pages.kontak');
        $response->assertSee('Hubungi');
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    /**
     * Emoji hanya boleh muncul di dalam teks pesan WhatsApp yang dibangun
     * lewat JS (references/icon-rules.md, pengecualian pesan WA manual: 🙏
     * 👤 🏟️ ✅ 💬 😊 di dalam waText), bukan di elemen UI halaman itu
     * sendiri (badge, heading, pilihan select, dst).
     */
    public function test_kontak_tidak_mengandung_emoji_mentah_di_ui(): void
    {
        $response = $this->get('http://greendeahan.com/v2/kontak');

        $response->assertDontSee('⚽');
        $response->assertDontSee('🟢');
        $response->assertDontSee('🎾');
        $response->assertDontSee('🏸');
        $response->assertDontSee('📋');
        $response->assertDontSee('📍');
        $response->assertDontSee('❓');
        $response->assertDontSee('📞');
        $response->assertDontSee('✉️');
        $response->assertDontSee('🕐');
        $response->assertDontSee('🗺');
    }

    public function test_subdomain_tenant_tidak_terpengaruh_rute_kontak(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'arena-uji-kontak.greendeahan.com',
            'status_aktif' => true,
        ]);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->get('http://arena-uji-kontak.greendeahan.com/');

        $response->assertOk();
        $response->assertViewIs('pages.booking');
    }
}
