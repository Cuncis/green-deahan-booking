<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman /harga lama sudah dihapus; paket sekarang ada di /sistem-booking.
 * Link lama (bookmark, hasil pencarian) tetap diarahkan permanen ke sana.
 */
class PricingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_harga_diarahkan_permanen_ke_paket_sistem_booking(): void
    {
        $this->get('http://greendeahan.com/harga')
            ->assertStatus(301)
            ->assertRedirect('http://greendeahan.com/sistem-booking#paket');
    }

    public function test_harga_lewat_www_juga_diarahkan(): void
    {
        $this->get('http://www.greendeahan.com/harga')->assertStatus(301);
    }

    public function test_harga_tidak_lagi_ada_di_sitemap(): void
    {
        $this->get('http://greendeahan.com/sitemap.xml')->assertOk()->assertDontSee('/harga');
    }

    public function test_subdomain_tenant_tidak_terpengaruh_rute_harga(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'arena-uji-harga.greendeahan.com',
            'status_aktif' => true,
        ]);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $this->get('http://arena-uji-harga.greendeahan.com/')->assertOk()->assertViewIs('booking');
        $this->get('http://arena-uji-harga.greendeahan.com/harga')->assertNotFound();
    }
}
