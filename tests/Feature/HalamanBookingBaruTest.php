<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use App\Models\TenantFitur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Halaman booking customer React di domain tenant (/).
 */
class HalamanBookingBaruTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Vite seolah dev server berjalan, supaya tag script berisi nama entry
     * (resources/js/...) di mesin manapun, ada atau tidak ada build.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $hotFile = storage_path('framework/testing/vite.hot');
        if (! is_dir(dirname($hotFile))) {
            mkdir(dirname($hotFile), 0755, true);
        }
        file_put_contents($hotFile, 'http://vite.test');
        Vite::useHotFile($hotFile);
    }

    private function tenant(string $paket = 'premium'): Tenant
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        TenantFitur::create(array_merge(['tenant_id' => $tenant->id], TenantFitur::presetUntukPaket($paket)));
        $tenant->update(['warna_utama' => '#1D4ED8', 'whatsapp_admin' => '+62 812-0000-1111']);

        return $tenant;
    }

    private function lapangan(Tenant $tenant, Cabang $cabang, array $override = []): Lapangan
    {
        return Lapangan::factory()->create(array_merge(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id, 'status_aktif' => true], $override));
    }

    public function test_halaman_booking_react_dengan_data_tenant(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id, 'jam_buka' => '07:00', 'jam_tutup' => '22:00']);
        $lapanganA = $this->lapangan($tenant, $cabang, ['nama' => 'A Futsal']);
        $this->lapangan($tenant, $cabang, ['nama' => 'B Padel']);
        $this->lapangan($tenant, $cabang, ['nama' => 'Z Nonaktif', 'status_aktif' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('noindex', false)
            ->assertSee('resources/js/booking/app.tsx', false)
            ->assertDontSee('livewire.js', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Booking')
                ->where('bisnis.nama', $tenant->nama_bisnis)
                ->where('bisnis.warnaUtama', '#1D4ED8')
                ->where('bisnis.whatsapp', '6281200001111')
                ->where('fitur.tipePembayaran', ['dp', 'lunas'])
                ->where('fitur.kodePromo', true)
                ->has('lapangan', 2)
                ->where('lapangan.0.nama', 'A Futsal')
                ->where('lapangan.0.jamBuka', '07:00')
                ->where('lapanganAwal', $lapanganA->id)
                ->has('tanggalPilihan', 7)
                ->where('tanggalPilihan.0.tanggal', now('Asia/Jakarta')->toDateString())
                ->where('menitHold', 10)
                ->where('persenDp', 50)
                ->where('kodeBookingKembali', null)
            );
    }

    public function test_memilih_lapangan_dari_query_dan_membawa_kode_booking_kembali(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $this->lapangan($tenant, $cabang, ['nama' => 'A']);
        $lapanganB = $this->lapangan($tenant, $cabang, ['nama' => 'B']);

        $this->get("/?lapangan={$lapanganB->id}&kode_booking=BK123")
            ->assertInertia(fn (Assert $page) => $page
                ->where('lapanganAwal', $lapanganB->id)
                ->where('kodeBookingKembali', 'BK123')
            );
    }

    public function test_fitur_paket_basic_hanya_lunas_tanpa_promo_dan_cabang(): void
    {
        $tenant = $this->tenant('basic');
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $this->lapangan($tenant, $cabang);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('fitur.tipePembayaran', ['lunas'])
            ->where('fitur.kodePromo', false)
            ->where('fitur.membership', false)
            ->where('fitur.multiCabang', false)
            ->where('cabang', [])
        );
    }

    public function test_daftar_cabang_hanya_yang_punya_lapangan_aktif(): void
    {
        $tenant = $this->tenant();
        $cabangA = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang A']);
        Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang Kosong']);
        $this->lapangan($tenant, $cabangA);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->has('cabang', 1)
            ->where('cabang.0.nama', 'Cabang A')
        );
    }

    public function test_query_ui_lama_tidak_mengubah_apa_apa(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $this->lapangan($tenant, $cabang);

        $this->get('/?ui=baru')->assertOk()->assertViewIs('booking')->assertInertia(fn (Assert $page) => $page->component('Booking'));
    }

    public function test_tidak_ada_lapangan_aktif(): void
    {
        $this->tenant();

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->has('lapangan', 0)->where('lapanganAwal', null));
    }
}
