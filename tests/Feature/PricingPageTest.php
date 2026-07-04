<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_harga_bisa_diakses_di_domain_utama(): void
    {
        $response = $this->get('http://greendeahan.com/harga');

        $response->assertOk();
        $response->assertViewIs('pages.pricing');
        $response->assertSee('Paket Website Booking Lapangan');
    }

    public function test_harga_bisa_diakses_lewat_www(): void
    {
        $response = $this->get('http://www.greendeahan.com/harga');

        $response->assertOk();
        $response->assertViewIs('pages.pricing');
    }

    public function test_route_pricing_tetap_resolve_untuk_link_dari_halaman_daftar(): void
    {
        $response = $this->get(route('pricing'));

        $response->assertOk();
    }

    public function test_harga_menampilkan_ketiga_paket_dan_tabel_perbandingan(): void
    {
        $response = $this->get('http://greendeahan.com/harga');

        $response->assertSee('Basic');
        $response->assertSee('Pro');
        $response->assertSee('Premium');
        $response->assertSee('Pembayaran online (QRIS, VA, e-wallet)');
        $response->assertSee('Tambahan Opsional (Add-on)');
    }

    public function test_tombol_paket_mengarah_ke_halaman_daftar_dengan_paket_terisi(): void
    {
        $response = $this->get('http://greendeahan.com/harga');

        $response->assertSee('/daftar?paket=basic', false);
        $response->assertSee('/daftar?paket=pro', false);
        $response->assertSee('/daftar?paket=premium', false);
    }

    public function test_subdomain_tenant_tidak_terpengaruh_rute_harga(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'arena-uji-harga.greendeahan.com',
            'status_aktif' => true,
        ]);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->get('http://arena-uji-harga.greendeahan.com/');

        $response->assertOk();
        $response->assertViewIs('pages.booking');
    }
}
