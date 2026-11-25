<?php

namespace Tests\Feature;

use App\Models\PengaturanSitus;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\BiteshipService;
use App\Services\RajaOngkirService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Superadmin > Toko Online > Pengaturan Ongkir: batas harian RajaOngkir (bisa diubah
 * dari dashboard), pemakaian hari ini, dan pemakaian Biteship sebagai cadangan.
 */
class PengaturanOngkirTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'rajaongkir.api_key' => 'kunci-rajaongkir-uji',
            'rajaongkir.batas_harian' => 100,
            'rajaongkir.sisa_aman' => 0,
            'biteship.api_key' => null,
            'biteship.origin_postal_code' => '13110',
            'biteship.cadangan_persen' => 0,
        ]);
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    private function pakaiKuota(int $jumlah): void
    {
        Cache::put('rajaongkir:pemakaian:'.now('Asia/Jakarta')->toDateString(), $jumlah, now()->addDay());
    }

    public function test_halaman_menampilkan_batas_pemakaian_dan_status(): void
    {
        $this->pakaiKuota(37);

        $this->actingAs($this->superadmin())->get('/superadmin/toko/ongkir')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/Toko/PengaturanOngkir')
            ->where('rajaongkir.aktif', true)
            ->where('rajaongkir.batas', 100)
            ->where('rajaongkir.terpakai', 37)
            ->where('rajaongkir.tersisa', 63)
            ->where('rajaongkir.alasanHabis', null)
            ->where('pengaturan.batas', 100)
            ->where('pengaturan.batasEnv', 100)
            ->where('biteship.aktif', false)
            ->where('kurir', 'jne:jnt:sicepat:anteraja'));
    }

    public function test_batas_dari_dashboard_menimpa_nilai_env_dan_dipakai_di_perhitungan_kuota(): void
    {
        $admin = $this->superadmin();
        $this->pakaiKuota(10);

        $this->actingAs($admin)->put('/superadmin/toko/ongkir', ['batas' => '10', 'sisa_aman' => '0'])->assertSessionHasNoErrors();

        $this->assertSame(10, app(RajaOngkirService::class)->batasHarian());
        $this->assertTrue(app(RajaOngkirService::class)->kuotaHabis());
        $this->assertSame('batas', app(RajaOngkirService::class)->alasanHabis());

        $this->actingAs($admin)->put('/superadmin/toko/ongkir', ['batas' => '50', 'sisa_aman' => '5'])->assertSessionHasNoErrors();

        $ringkasan = app(RajaOngkirService::class)->ringkasan();
        $this->assertSame(50, $ringkasan['batas']);
        $this->assertSame(5, $ringkasan['sisaAman']);
        $this->assertSame(35, $ringkasan['tersisa']);
        $this->assertFalse(app(RajaOngkirService::class)->kuotaHabis());
    }

    public function test_tanpa_pengaturan_tersimpan_memakai_nilai_env(): void
    {
        config(['rajaongkir.batas_harian' => 80, 'rajaongkir.sisa_aman' => 3]);

        $this->assertSame(80, app(RajaOngkirService::class)->batasHarian());
        $this->assertSame(3, app(RajaOngkirService::class)->sisaAman());
        $this->assertSame(77, app(RajaOngkirService::class)->ringkasan()['tersisa']);
    }

    public function test_kosongkan_pengaturan_kembali_ke_nilai_env(): void
    {
        app(RajaOngkirService::class)->simpanPengaturan(20, 1);
        $this->assertSame(20, app(RajaOngkirService::class)->batasHarian());

        app(RajaOngkirService::class)->simpanPengaturan(null, null);

        $this->assertSame(100, app(RajaOngkirService::class)->batasHarian());
        $this->assertSame(0, app(RajaOngkirService::class)->sisaAman());
    }

    public function test_validasi_batas_dan_sisa_aman(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->put('/superadmin/toko/ongkir', ['batas' => '', 'sisa_aman' => '0'])->assertSessionHasErrors('batas');
        $this->actingAs($admin)->put('/superadmin/toko/ongkir', ['batas' => '0', 'sisa_aman' => '0'])->assertSessionHasErrors('batas');
        $this->actingAs($admin)->put('/superadmin/toko/ongkir', ['batas' => '100001', 'sisa_aman' => '0'])->assertSessionHasErrors('batas');
        $this->actingAs($admin)->put('/superadmin/toko/ongkir', ['batas' => '100', 'sisa_aman' => '-1'])->assertSessionHasErrors('sisa_aman');
        $this->actingAs($admin)->put('/superadmin/toko/ongkir', ['batas' => '10', 'sisa_aman' => '10'])->assertSessionHasErrors('sisa_aman');
        $this->actingAs($admin)->put('/superadmin/toko/ongkir', ['batas' => 'abc', 'sisa_aman' => '0'])->assertSessionHasErrors('batas');
        $this->assertNull(PengaturanSitus::find(RajaOngkirService::KUNCI_BATAS));
    }

    public function test_coba_lagi_hanya_mencabut_status_ditolak_dan_hitungan_tetap(): void
    {
        $this->pakaiKuota(40);
        Cache::put('rajaongkir:habis:'.now('Asia/Jakarta')->toDateString(), true, now()->addDay());
        $this->assertSame('ditolak', app(RajaOngkirService::class)->alasanHabis());

        $this->actingAs($this->superadmin())->post('/superadmin/toko/ongkir/coba-lagi')->assertRedirect();

        $this->assertNull(app(RajaOngkirService::class)->alasanHabis());
        $this->assertSame(40, app(RajaOngkirService::class)->pemakaianHariIni());
    }

    public function test_setel_ulang_menolkan_hitungan_dan_status_habis(): void
    {
        $this->pakaiKuota(100);
        $this->assertSame('batas', app(RajaOngkirService::class)->alasanHabis());

        $this->actingAs($this->superadmin())->post('/superadmin/toko/ongkir/setel-ulang')->assertRedirect();

        $this->assertSame(0, app(RajaOngkirService::class)->pemakaianHariIni());
        $this->assertNull(app(RajaOngkirService::class)->alasanHabis());
    }

    public function test_dashboard_superadmin_memuat_pemakaian_ongkir(): void
    {
        $this->pakaiKuota(12);

        $this->actingAs($this->superadmin())->get('/superadmin')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('ongkir.rajaongkir.terpakai', 12)
            ->where('ongkir.rajaongkir.batas', 100)
            ->where('ongkir.biteship.aktif', false)
            ->where('ongkir.url', route('superadmin.toko.ongkir')));
    }

    public function test_pemakaian_biteship_dihitung_hanya_saat_memanggil_api_bukan_dari_cache(): void
    {
        config(['biteship.api_key' => 'biteship_test.kunci-uji', 'biteship.env' => 'sandbox']);
        Http::fake(['api.biteship.com/v1/rates/couriers' => Http::response(['success' => true, 'pricing' => [
            ['courier_code' => 'jne', 'courier_name' => 'JNE', 'courier_service_code' => 'reg', 'courier_service_name' => 'Reguler', 'price' => 19000, 'shipment_duration_range' => '2 - 3', 'shipment_duration_unit' => 'days', 'duration' => '2 - 3 days'],
        ]])]);
        $items = [['name' => 'Bola', 'value' => 95000, 'weight' => 300, 'quantity' => 1]];

        app(BiteshipService::class)->tarif('13110', '12810', $items);
        app(BiteshipService::class)->tarif('13110', '12810', $items);
        app(BiteshipService::class)->tarif('13110', '12810', [['name' => 'Bola', 'value' => 95000, 'weight' => 600, 'quantity' => 1]]);

        $pemakaian = app(BiteshipService::class)->pemakaian();
        $this->assertSame(2, $pemakaian['hariIni']);
        $this->assertSame(2, $pemakaian['bulanIni']);
        $this->assertSame(5, $pemakaian['tarifPerPermintaan']);
        $this->assertSame(10, $pemakaian['biayaBulanIni']);
        Http::assertSentCount(2);
    }

    public function test_beralih_ke_biteship_tercermin_di_pemakaian_biteship(): void
    {
        config(['biteship.api_key' => 'biteship_test.kunci-uji', 'biteship.env' => 'sandbox', 'rajaongkir.batas_harian' => 5]);
        $this->pakaiKuota(5);
        Http::fake(['api.biteship.com/v1/rates/couriers' => Http::response(['success' => true, 'pricing' => [
            ['courier_code' => 'jne', 'courier_name' => 'JNE', 'courier_service_code' => 'reg', 'courier_service_name' => 'Reguler', 'price' => 19000, 'shipment_duration_range' => '2 - 3', 'shipment_duration_unit' => 'days', 'duration' => '2 - 3 days'],
        ]])]);
        foreach ([['31', 'DKI Jakarta', null], ['31.74', 'Jakarta Selatan', null], ['31.74.01', 'Tebet', null], ['31.74.01.1001', 'Tebet Barat', '12810']] as [$kode, $nama, $kodepos]) {
            Wilayah::create(['kode' => $kode, 'nama' => $nama, 'level' => Wilayah::levelDari($kode), 'induk' => Wilayah::indukDari($kode), 'kodepos' => $kodepos]);
        }
        $produk = Product::factory()->create(['sell_price' => 95000, 'weight_gram' => 300, 'supplier_id' => Supplier::factory()->create(['origin_postal_code' => null])->id]);
        $this->post('http://greendeahan.com/keranjang', ['product_id' => $produk->id]);

        $this->getJson('http://greendeahan.com/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertOk()->assertJsonPath('opsi.0.price', 19000);

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'biteship.com'));
        $this->actingAs($this->superadmin())->get('/superadmin/toko/ongkir')->assertInertia(fn (Assert $page) => $page
            ->where('biteship.hariIni', 1)
            ->where('biteship.biayaBulanIni', 5)
            ->where('rajaongkir.alasanHabis', 'batas'));
    }

    public function test_menu_pengaturan_ongkir_ada_di_grup_toko_online(): void
    {
        $this->actingAs($this->superadmin())->get('/superadmin/toko/ongkir')->assertInertia(function (Assert $page) {
            $grup = collect($page->toArray()['props']['navigasi'])->firstWhere('grup', 'Toko Online');

            $this->assertContains('Pengaturan Ongkir', collect($grup['item'])->pluck('label')->all());
        });
    }

    public function test_bukan_superadmin_tidak_bisa_mengakses_atau_mengubah(): void
    {
        $biasa = User::factory()->create();

        $this->get('/superadmin/toko/ongkir')->assertRedirect();
        $this->actingAs($biasa)->get('/superadmin/toko/ongkir')->assertForbidden();
        $this->actingAs($biasa)->put('/superadmin/toko/ongkir', ['batas' => '5', 'sisa_aman' => '0'])->assertForbidden();
        $this->actingAs($biasa)->post('/superadmin/toko/ongkir/coba-lagi')->assertForbidden();
        $this->actingAs($biasa)->post('/superadmin/toko/ongkir/uji')->assertForbidden();
        $this->actingAs($biasa)->post('/superadmin/toko/ongkir/setel-ulang')->assertForbidden();
        $this->assertSame(100, app(RajaOngkirService::class)->batasHarian());
    }

    public function test_uji_koneksi_berhasil_memakai_satu_permintaan_dan_tercatat_sebagai_terakhir(): void
    {
        Http::fake(['rajaongkir.komerce.id/*' => Http::response(['meta' => ['code' => 200, 'message' => 'Success'], 'data' => [['id' => 1, 'zip_code' => '10110']]])]);

        $this->actingAs($this->superadmin())->post('/superadmin/toko/ongkir/uji')->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, app(RajaOngkirService::class)->pemakaianHariIni());
        $terakhir = app(RajaOngkirService::class)->ringkasan()['terakhir'];
        $this->assertSame('uji koneksi', $terakhir['aksi']);
        $this->assertTrue($terakhir['berhasil']);
        Http::assertSent(fn (HttpRequest $request) => $request->hasHeader('key', 'kunci-rajaongkir-uji') && str_contains($request->url(), 'search=10110'));
    }

    public function test_uji_koneksi_gagal_menampilkan_alasan_dari_rajaongkir_tanpa_membocorkan_kunci(): void
    {
        Http::fake(['rajaongkir.komerce.id/*' => Http::response(['meta' => ['code' => 401, 'message' => 'Invalid API key'], 'data' => null], 401)]);

        $this->actingAs($this->superadmin())->post('/superadmin/toko/ongkir/uji')->assertRedirect()->assertSessionHas('error');

        $pesan = session('error');
        $this->assertStringContainsString('Invalid API key', $pesan);
        $this->assertStringNotContainsString('kunci-rajaongkir-uji', $pesan);
        $terakhir = app(RajaOngkirService::class)->ringkasan()['terakhir'];
        $this->assertFalse($terakhir['berhasil']);
        $this->assertSame('Invalid API key', $terakhir['pesan']);
    }

    public function test_uji_koneksi_tanpa_api_key_memberi_petunjuk_dan_tidak_memanggil_api(): void
    {
        config(['rajaongkir.api_key' => null]);
        Http::fake();

        $this->actingAs($this->superadmin())->post('/superadmin/toko/ongkir/uji')->assertRedirect()->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertStringContainsString('config:clear', session('error'));
    }

    public function test_tarif_asli_mencatat_permintaan_terakhir_dan_menaikkan_hitungan(): void
    {
        Http::fake([
            'rajaongkir.komerce.id/api/v1/destination/domestic-destination*' => fn (HttpRequest $request) => Http::response(['meta' => ['code' => 200], 'data' => [['id' => 7, 'zip_code' => str_contains($request->url(), '13110') ? '13110' : '12810']]]),
            'rajaongkir.komerce.id/api/v1/calculate/domestic-cost' => Http::response(['meta' => ['code' => 200, 'message' => 'Success'], 'data' => [['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 11000, 'etd' => '2-3 day']]]),
        ]);

        app(RajaOngkirService::class)->tarif('13110', '12810', [['name' => 'Bola', 'value' => 95000, 'weight' => 300, 'quantity' => 1]]);

        $ringkasan = app(RajaOngkirService::class)->ringkasan();
        $this->assertSame(3, $ringkasan['terpakai']);
        $this->assertSame('hitung tarif', $ringkasan['terakhir']['aksi']);
        $this->assertTrue($ringkasan['terakhir']['berhasil']);
    }

    public function test_kegagalan_tarif_tercatat_dengan_pesan_di_halaman_pengaturan(): void
    {
        Http::fake(['rajaongkir.komerce.id/*' => Http::response(['meta' => ['code' => 422, 'message' => 'Invalid Courier'], 'data' => null], 422)]);

        try {
            app(RajaOngkirService::class)->tarif('13110', '12810', [['name' => 'Bola', 'value' => 95000, 'weight' => 300, 'quantity' => 1]]);
        } catch (\Throwable) {
        }

        $this->actingAs($this->superadmin())->get('/superadmin/toko/ongkir')->assertInertia(fn (Assert $page) => $page
            ->where('rajaongkir.terakhir.berhasil', false)
            ->where('rajaongkir.terakhir.pesan', 'Invalid Courier'));
    }

    public function test_tanpa_permintaan_apa_pun_catatan_terakhir_kosong(): void
    {
        $this->assertNull(app(RajaOngkirService::class)->ringkasan()['terakhir']);
    }
}
