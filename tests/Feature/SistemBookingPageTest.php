<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Layanan > Sistem Booking (/sistem-booking) di situs korporat.
 */
class SistemBookingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_sistem_booking_menampilkan_paket_dengan_harga_dari_kode(): void
    {
        $this->get('http://greendeahan.com/sistem-booking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SistemBooking')
                ->has('paket', 3)
                ->where('paket.0.kunci', 'basic')
                ->where('paket.0.harga', Tenant::HARGA_PAKET['basic'])
                ->where('paket.0.batasLapangan', 1)
                ->where('paket.1.harga', Tenant::HARGA_PAKET['pro'])
                ->where('paket.1.batasLapangan', 3)
                ->where('paket.1.unggulan', true)
                ->where('paket.2.batasLapangan', null)
                ->where('paket.2.daftarUrl', '/daftar?paket=premium')
                ->where('diskonPerpanjanganPersen', Tenant::DISKON_PERPANJANGAN_PERSEN)
                ->where('meta.title', fn (string $judul) => str_contains($judul, 'Sistem Booking'))
            );

        $this->get('http://www.greendeahan.com/sistem-booking')->assertOk();
    }

    public function test_selama_belum_publik_halaman_tetap_bisa_dibuka_tapi_tersembunyi(): void
    {
        config(['situs.sistem_booking_publik' => false]);

        $this->get('http://greendeahan.com/sistem-booking')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertInertia(fn (Assert $page) => $page->where('meta.noindex', true)->where('sistemBookingPublik', false));

        $this->get('http://greendeahan.com/sitemap.xml')->assertOk()->assertDontSee('/sistem-booking')->assertDontSee('/daftar')->assertSee('/perawatan');
    }

    public function test_setelah_publik_halaman_diindeks_dan_masuk_sitemap(): void
    {
        config(['situs.sistem_booking_publik' => true]);

        $this->get('http://greendeahan.com/sistem-booking')
            ->assertOk()
            ->assertDontSee('<meta name="robots"', false)
            ->assertInertia(fn (Assert $page) => $page->where('meta.noindex', false)->where('sistemBookingPublik', true));

        $this->get('http://greendeahan.com/sitemap.xml')->assertOk()->assertSee('/sistem-booking')->assertSee('/daftar');
    }

    public function test_subdomain_tenant_tidak_terpengaruh_rute_sistem_booking(): void
    {
        $this->get('http://localhost/sistem-booking')->assertNotFound();
    }
}
