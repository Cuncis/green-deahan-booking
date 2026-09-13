<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KonsepPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_konsep_bisa_diakses_di_domain_utama(): void
    {
        $response = $this->get('http://greendeahan.com/konsep');

        $response->assertOk();
        $response->assertViewIs('pages.konsep');
        $response->assertSee('Konsep Sarana Sport Center yang Ideal untuk Bisnis Anda di Masa Depan');
    }

    public function test_konsep_bisa_diakses_lewat_www(): void
    {
        $response = $this->get('http://www.greendeahan.com/konsep');

        $response->assertOk();
        $response->assertViewIs('pages.konsep');
    }

    public function test_konsep_menampilkan_ketiga_pilihan_konsep_dan_kebutuhan_lahan(): void
    {
        $response = $this->get('http://greendeahan.com/konsep');

        $response->assertSee('Fokus Satu Cabang Olahraga');
        $response->assertSee('Multi-Court Kompleks');
        $response->assertSee('Sport Center Lifestyle');
        $response->assertSee('Futsal');
        $response->assertSee('Mini Soccer');
        $response->assertSee('Padel');
        $response->assertSee('Badminton');
    }

    public function test_konsep_menampilkan_kalkulator_perencanaan(): void
    {
        $response = $this->get('http://greendeahan.com/konsep');

        $response->assertSee('Coba Kalkulator Perencanaan Lapangan');
        $response->assertSee('Seberapa besar lahan Anda?');
        $response->assertSee('Berapa estimasi budget Anda?', false);
        $response->assertSee('Apa yang ingin Anda bangun?');
        $response->assertSee('Apa lagi yang Anda inginkan di lokasi?');
        $response->assertSee('Dapatkan Konsultasi Gratis');
    }

    public function test_konsep_tidak_mengandung_emoji_mentah(): void
    {
        $response = $this->get('http://greendeahan.com/konsep');

        $response->assertDontSee('⚽');
        $response->assertDontSee('🏗');
        $response->assertDontSee('💰');
        $response->assertDontSee('⭐');
        $response->assertDontSee('🅿️');
        $response->assertDontSee('🚻');
        $response->assertDontSee('☕');
        $response->assertDontSee('🏸');
        $response->assertDontSee('🏀');
    }

    public function test_subdomain_tenant_tidak_terpengaruh_rute_konsep(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'arena-uji-konsep.greendeahan.com',
            'status_aktif' => true,
        ]);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->get('http://arena-uji-konsep.greendeahan.com/');

        $response->assertOk();
        $response->assertViewIs('pages.booking');
    }
}
