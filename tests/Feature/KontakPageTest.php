<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KontakPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_kontak_bisa_diakses_di_domain_utama(): void
    {
        $response = $this->get('http://greendeahan.com/kontak');

        $response->assertOk();
        $response->assertViewIs('pages.kontak');
        $response->assertSee('Hubungi');
    }

    public function test_kontak_bisa_diakses_lewat_www(): void
    {
        $response = $this->get('http://www.greendeahan.com/kontak');

        $response->assertOk();
        $response->assertViewIs('pages.kontak');
    }

    /**
     * Emoji hanya boleh muncul di dalam teks pesan WhatsApp yang dibangun
     * lewat JS (references/icon-rules.md, pengecualian pesan WA manual: 🙏
     * 👤 🏟️ ✅ 💬 😊 di dalam waText), bukan di elemen UI halaman itu
     * sendiri (badge, heading, pilihan select, dst).
     */
    public function test_kontak_tidak_mengandung_emoji_mentah_di_ui(): void
    {
        $response = $this->get('http://greendeahan.com/kontak');

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
