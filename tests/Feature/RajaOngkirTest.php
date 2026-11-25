<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Wilayah;
use App\Services\KeranjangService;
use App\Services\RajaOngkirService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Ongkir toko: RajaOngkir (gratis, batas harian) sebagai penyedia utama dan Biteship
 * sebagai cadangan saat batas habis atau RajaOngkir gagal.
 */
class RajaOngkirTest extends TestCase
{
    use RefreshDatabase;

    private const SITUS = 'http://greendeahan.com';

    private const URL_LOKASI = 'rajaongkir.komerce.id/api/v1/destination/domestic-destination';

    private const URL_TARIF = 'rajaongkir.komerce.id/api/v1/calculate/domestic-cost';

    private const URL_BITESHIP = 'api.biteship.com/v1/rates/couriers';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'rajaongkir.api_key' => 'kunci-rajaongkir-uji',
            'rajaongkir.batas_harian' => 100,
            'rajaongkir.sisa_aman' => 0,
            'biteship.api_key' => null,
            'biteship.env' => 'sandbox',
            'biteship.origin_postal_code' => '13110',
            'biteship.cadangan_persen' => 0,
            'biteship.ongkir_darurat' => false,
            'toko.ongkir_flat' => 15000,
            'mayar.api_key' => 'kunci-mayar-uji',
            'mayar.env' => 'sandbox',
        ]);

        foreach ([
            ['31', 'DKI Jakarta', null],
            ['31.74', 'Kota Administrasi Jakarta Selatan', null],
            ['31.74.01', 'Tebet', null],
            ['31.74.01.1001', 'Tebet Barat', '12810'],
        ] as [$kode, $nama, $kodepos]) {
            Wilayah::create(['kode' => $kode, 'nama' => $nama, 'level' => Wilayah::levelDari($kode), 'induk' => Wilayah::indukDari($kode), 'kodepos' => $kodepos]);
        }
    }

    /**
     * @param  array<string, int>  $lokasi  kode pos => id lokasi RajaOngkir
     * @param  array<int, array<string, mixed>>|null  $tarif
     */
    private function fakeRajaOngkir(array $lokasi = ['13110' => 101, '12810' => 202], ?array $tarif = null, int $statusTarif = 200): void
    {
        Http::fake([
            self::URL_LOKASI.'*' => function (HttpRequest $request) use ($lokasi) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
                $id = $lokasi[$query['search'] ?? ''] ?? null;

                return Http::response($id === null
                    ? ['meta' => ['message' => 'Domestic Destinations Data not found', 'code' => 404, 'status' => 'error'], 'data' => null]
                    : ['meta' => ['message' => 'Success', 'code' => 200, 'status' => 'success'], 'data' => [
                        ['id' => 999, 'label' => 'Lain', 'zip_code' => '99999'],
                        ['id' => $id, 'label' => 'Lokasi', 'province_name' => 'DKI Jakarta', 'city_name' => 'Jakarta', 'district_name' => 'X', 'subdistrict_name' => 'Y', 'zip_code' => $query['search']],
                    ]], $id === null ? 404 : 200);
            },
            self::URL_TARIF => Http::response($tarif === null ? ['meta' => ['message' => 'Success', 'code' => 200, 'status' => 'success'], 'data' => [
                ['name' => 'Jalur Nugraha Ekakurir (JNE)', 'code' => 'jne', 'service' => 'REG', 'description' => 'Layanan Reguler', 'cost' => 11000, 'etd' => '2-3 day'],
                ['name' => 'J&T Express', 'code' => 'jnt', 'service' => 'EZ', 'description' => 'Economy', 'cost' => 14000, 'etd' => '2-3 day'],
                ['name' => 'SiCepat Ekspres', 'code' => 'sicepat', 'service' => 'BEST', 'description' => 'Besok Sampai Tujuan', 'cost' => 32000, 'etd' => '1 day'],
            ]] : ['meta' => ['message' => 'Success', 'code' => 200, 'status' => 'success'], 'data' => $tarif], $statusTarif),
            'api.mayar.io/hl/v2/payments/create' => Http::response(['statusCode' => 200, 'data' => ['id' => 'pay-ro', 'transactionId' => 't-1', 'link' => 'https://testingmayar.myr.id/pl/abc']]),
        ]);
    }

    private function aktifkanBiteship(): void
    {
        config(['biteship.api_key' => 'biteship_test.kunci-uji']);
        Http::fake([
            self::URL_BITESHIP => Http::response(['success' => true, 'pricing' => [
                ['courier_code' => 'jne', 'courier_name' => 'JNE', 'courier_service_code' => 'reg', 'courier_service_name' => 'Reguler', 'price' => 19000, 'shipment_duration_range' => '2 - 3', 'shipment_duration_unit' => 'days', 'duration' => '2 - 3 days'],
            ]]),
        ]);
    }

    private function produk(int $berat = 300): Product
    {
        return Product::factory()->create(['sell_price' => 95000, 'weight_gram' => $berat, 'supplier_id' => Supplier::factory()->create(['origin_postal_code' => null])->id]);
    }

    private function isiKeranjang(Product $produk, int $qty = 1): void
    {
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'qty' => $qty]);
    }

    private function ongkir(): TestResponse
    {
        return $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001');
    }

    private function jumlahPermintaan(string $bagianUrl): int
    {
        return Http::recorded(fn (HttpRequest $request) => str_contains($request->url(), $bagianUrl))->count();
    }

    public function test_tarif_dari_rajaongkir_termurah_dulu_dengan_nama_dan_durasi_rapi(): void
    {
        $this->fakeRajaOngkir();
        $this->isiKeranjang($this->produk());

        $this->ongkir()
            ->assertOk()
            ->assertJsonPath('aktif', true)
            ->assertJsonPath('opsi.0.kode', 'jne:reg')
            ->assertJsonPath('opsi.0.label', 'JNE Layanan Reguler')
            ->assertJsonPath('opsi.0.duration', '2 - 3 hari')
            ->assertJsonPath('opsi.0.price', 11000)
            ->assertJsonPath('opsi.1.kode', 'jnt:ez')
            ->assertJsonPath('opsi.1.courier_name', 'J&T')
            ->assertJsonPath('opsi.2.kode', 'sicepat:best')
            ->assertJsonPath('opsi.2.duration', '1 hari');
    }

    public function test_layanan_kargo_dan_trucking_tidak_ditawarkan(): void
    {
        $this->fakeRajaOngkir(tarif: [
            ['name' => 'Jalur Nugraha Ekakurir (JNE)', 'code' => 'jne', 'service' => 'REG', 'description' => 'Layanan Reguler', 'cost' => 11000, 'etd' => '2-3 day'],
            ['name' => 'Jalur Nugraha Ekakurir (JNE)', 'code' => 'jne', 'service' => 'JTR', 'description' => 'JNE Trucking', 'cost' => 9000, 'etd' => '3-5 day'],
            ['name' => 'Jalur Nugraha Ekakurir (JNE)', 'code' => 'jne', 'service' => 'JTR>130', 'description' => 'Trucking Berat', 'cost' => 8000, 'etd' => '3-5 day'],
            ['name' => 'SiCepat Ekspres', 'code' => 'sicepat', 'service' => 'GOKIL', 'description' => 'Cargo', 'cost' => 7000, 'etd' => '3-5 day'],
            ['name' => 'J&T Express', 'code' => 'jnt', 'service' => 'CARGO', 'description' => 'J&T Cargo', 'cost' => 6000, 'etd' => '3-5 day'],
            ['name' => 'SiCepat Ekspres', 'code' => 'sicepat', 'service' => 'BEST', 'description' => 'Besok Sampai Tujuan', 'cost' => 32000, 'etd' => '1 day'],
        ]);
        $this->isiKeranjang($this->produk());

        $kode = collect($this->ongkir()->assertOk()->json('opsi'))->pluck('kode')->all();

        $this->assertSame(['jne:reg', 'sicepat:best'], $kode);
    }

    public function test_permintaan_ke_rajaongkir_memakai_header_key_id_lokasi_dan_berat_total(): void
    {
        $this->fakeRajaOngkir();
        $this->isiKeranjang($this->produk(250), 3);

        $this->ongkir()->assertOk();

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), self::URL_LOKASI) && $request->hasHeader('key', 'kunci-rajaongkir-uji') && str_contains($request->url(), 'search=13110'));
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), self::URL_LOKASI) && str_contains($request->url(), 'search=12810'));
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), self::URL_TARIF)
            && $request->hasHeader('key', 'kunci-rajaongkir-uji')
            && (int) $request['origin'] === 101
            && (int) $request['destination'] === 202
            && (int) $request['weight'] === 750
            && $request['courier'] === 'jne:jnt:sicepat:anteraja');
    }

    public function test_tanpa_biteship_rajaongkir_saja_sudah_mengaktifkan_ongkir_dinamis(): void
    {
        $this->fakeRajaOngkir();
        $this->isiKeranjang($this->produk());

        $this->assertTrue(app(KeranjangService::class)->ringkasan()['ongkirDinamis']);
        $this->assertNull(app(KeranjangService::class)->ringkasan()['ongkir']);
    }

    public function test_hasil_yang_sama_dan_id_lokasi_di_cache_tidak_memakai_kuota_lagi(): void
    {
        $this->fakeRajaOngkir();
        $this->isiKeranjang($this->produk());

        $this->ongkir()->assertOk();
        $this->assertSame(3, app(RajaOngkirService::class)->pemakaianHariIni());

        $this->ongkir()->assertOk();
        $this->assertSame(3, app(RajaOngkirService::class)->pemakaianHariIni());
        $this->assertSame(1, $this->jumlahPermintaan(self::URL_TARIF));
    }

    public function test_berat_berbeda_hanya_menambah_satu_permintaan_karena_id_lokasi_sudah_tersimpan(): void
    {
        $this->fakeRajaOngkir();
        $produk = $this->produk();
        $this->isiKeranjang($produk);
        $this->ongkir()->assertOk();

        $this->isiKeranjang($produk);
        $this->ongkir()->assertOk();

        $this->assertSame(4, app(RajaOngkirService::class)->pemakaianHariIni());
    }

    public function test_batas_harian_tercapai_beralih_ke_biteship_dan_hasil_cache_rajaongkir_tetap_dilayani(): void
    {
        config(['rajaongkir.batas_harian' => 3]);
        $this->fakeRajaOngkir();
        $this->aktifkanBiteship();
        $produk = $this->produk();
        $this->isiKeranjang($produk);

        $this->ongkir()->assertOk()->assertJsonPath('opsi.0.price', 11000);
        $this->assertTrue(app(RajaOngkirService::class)->kuotaHabis());

        // Isi keranjang sama: dari cache RajaOngkir, tanpa kuota dan tanpa Biteship.
        $this->ongkir()->assertOk()->assertJsonPath('opsi.0.price', 11000);
        $this->assertSame(0, $this->jumlahPermintaan(self::URL_BITESHIP));

        // Isi keranjang berubah: butuh tarif baru, kuota habis, jadi Biteship.
        $this->isiKeranjang($produk);
        $this->ongkir()->assertOk()->assertJsonPath('opsi.0.price', 19000);
        $this->assertSame(1, $this->jumlahPermintaan(self::URL_BITESHIP));
        $this->assertSame(1, $this->jumlahPermintaan(self::URL_TARIF));
    }

    public function test_sisa_aman_menahan_beberapa_permintaan_terakhir(): void
    {
        config(['rajaongkir.batas_harian' => 5, 'rajaongkir.sisa_aman' => 2]);
        $this->fakeRajaOngkir();
        $this->aktifkanBiteship();
        $this->isiKeranjang($this->produk());

        $this->ongkir()->assertOk();

        $this->assertTrue(app(RajaOngkirService::class)->kuotaHabis());
    }

    public function test_kuota_dihitung_per_hari_wib_dan_pulih_besok(): void
    {
        config(['rajaongkir.batas_harian' => 3]);
        $this->fakeRajaOngkir();
        $this->aktifkanBiteship();
        $produk = $this->produk();
        $this->isiKeranjang($produk);
        $this->ongkir()->assertOk();
        $this->assertTrue(app(RajaOngkirService::class)->kuotaHabis());

        $this->travel(1)->day();

        $this->assertFalse(app(RajaOngkirService::class)->kuotaHabis());
        $this->assertSame(0, app(RajaOngkirService::class)->pemakaianHariIni());
    }

    public function test_rajaongkir_menolak_dengan_429_beralih_ke_biteship_dan_tidak_dicoba_lagi_hari_itu(): void
    {
        Http::fake([
            self::URL_LOKASI.'*' => Http::response(['meta' => ['message' => 'Too Many Requests', 'code' => 429, 'status' => 'error'], 'data' => null], 429),
        ]);
        $this->aktifkanBiteship();
        $produk = $this->produk();
        $this->isiKeranjang($produk);

        $this->ongkir()->assertOk()->assertJsonPath('opsi.0.price', 19000);
        $this->assertTrue(app(RajaOngkirService::class)->kuotaHabis());

        $this->isiKeranjang($produk);
        $this->ongkir()->assertOk();

        $this->assertSame(1, $this->jumlahPermintaan(self::URL_LOKASI));
    }

    public function test_pesan_batas_dari_rajaongkir_juga_dianggap_kuota_habis(): void
    {
        Http::fake([self::URL_LOKASI.'*' => Http::response(['meta' => ['message' => 'Daily limit exceeded', 'code' => 400, 'status' => 'error'], 'data' => null], 400)]);
        $this->aktifkanBiteship();
        $this->isiKeranjang($this->produk());

        $this->ongkir()->assertOk();

        $this->assertTrue(app(RajaOngkirService::class)->kuotaHabis());
    }

    public function test_gangguan_rajaongkir_beralih_ke_biteship_untuk_permintaan_itu_saja(): void
    {
        Http::fake([self::URL_LOKASI.'*' => Http::response('galat', 500)]);
        $this->aktifkanBiteship();
        $this->isiKeranjang($this->produk());

        $this->ongkir()->assertOk()->assertJsonPath('opsi.0.price', 19000);

        $this->assertFalse(app(RajaOngkirService::class)->kuotaHabis());
    }

    public function test_rajaongkir_gagal_tanpa_biteship_menawarkan_ongkir_tetap(): void
    {
        config(['biteship.ongkir_darurat' => true]);
        Http::fake([self::URL_LOKASI.'*' => Http::response('galat', 500)]);
        $this->isiKeranjang($this->produk());

        $this->ongkir()->assertOk()->assertJsonPath('darurat', true)->assertJsonPath('opsi.0.kode', 'darurat:tetap')->assertJsonPath('opsi.0.price', 15000);
    }

    public function test_rajaongkir_gagal_tanpa_biteship_dan_tanpa_cadangan_memberi_502(): void
    {
        Http::fake([self::URL_LOKASI.'*' => Http::response('galat', 500)]);
        $this->isiKeranjang($this->produk());

        $this->ongkir()->assertStatus(502);
    }

    public function test_kode_pos_tidak_dikenal_rajaongkir_beralih_ke_biteship_dan_tidak_mengulang_pencarian(): void
    {
        $this->fakeRajaOngkir(['13110' => 101]);
        $this->aktifkanBiteship();
        $produk = $this->produk();
        $this->isiKeranjang($produk);

        $this->ongkir()->assertOk()->assertJsonPath('opsi.0.price', 19000);
        $this->isiKeranjang($produk);
        $this->ongkir()->assertOk();

        $this->assertSame(1, $this->jumlahPermintaan('search=12810'));
        $this->assertSame(0, $this->jumlahPermintaan(self::URL_TARIF));
    }

    public function test_rute_tanpa_kurir_bukan_galat_dan_tidak_memanggil_biteship(): void
    {
        Http::fake([
            self::URL_LOKASI.'*' => fn (HttpRequest $request) => Http::response(['meta' => ['code' => 200], 'data' => [['id' => 5, 'zip_code' => str_contains($request->url(), '13110') ? '13110' : '12810']]]),
            self::URL_TARIF => Http::response(['meta' => ['message' => 'Calculate Domestic Shipping Cost not found', 'code' => 400, 'status' => 'error'], 'data' => null], 400),
        ]);
        $this->aktifkanBiteship();
        $this->isiKeranjang($this->produk());

        $this->ongkir()->assertOk()->assertJsonPath('opsi', []);

        $this->assertSame(0, $this->jumlahPermintaan(self::URL_BITESHIP));
    }

    public function test_kode_layanan_dengan_simbol_dinormalkan_ke_format_kurir_layanan(): void
    {
        $this->fakeRajaOngkir(tarif: [
            ['name' => 'J&T', 'code' => 'jnt', 'service' => 'SDS>1', 'description' => '', 'cost' => 50000, 'etd' => '3-5 day'],
            ['name' => 'JNE', 'code' => 'JNE', 'service' => 'YES', 'description' => 'Yakin Esok Sampai', 'cost' => 0, 'etd' => '1 day'],
        ]);
        $this->isiKeranjang($this->produk());

        $this->ongkir()->assertOk()->assertJsonCount(1, 'opsi')->assertJsonPath('opsi.0.kode', 'jnt:sds_1')->assertJsonPath('opsi.0.service_name', 'SDS>1');
    }

    public function test_checkout_memakai_layanan_rajaongkir_dan_menghitung_ulang_di_server(): void
    {
        $this->fakeRajaOngkir();
        $this->isiKeranjang($this->produk());

        $this->post(self::SITUS.'/checkout', [
            'customer_name' => 'Budi Santoso', 'phone' => '081234567890', 'email' => 'budi@contoh.com',
            'address' => 'Jl. Melati No. 10, RT 01/RW 02', 'wilayah_kode' => '31.74.01.1001',
            'layanan' => 'jne:reg', 'shipping_cost' => 1,
        ])->assertRedirect('https://testingmayar.myr.id/pl/abc');

        $order = Order::firstOrFail();
        $this->assertSame(11000, $order->shipping_cost);
        $this->assertSame('jne', $order->courier_code);
        $this->assertSame('reg', $order->courier_service_code);
        $this->assertSame(95000 + 11000, $order->total);
    }

    public function test_checkout_dengan_layanan_yang_tidak_ada_ditolak(): void
    {
        $this->fakeRajaOngkir();
        $this->isiKeranjang($this->produk());

        $this->post(self::SITUS.'/checkout', [
            'customer_name' => 'Budi Santoso', 'phone' => '081234567890', 'email' => 'budi@contoh.com',
            'address' => 'Jl. Melati No. 10, RT 01/RW 02', 'wilayah_kode' => '31.74.01.1001', 'layanan' => 'jne:yes',
        ])->assertSessionHasErrors('layanan');

        $this->assertSame(0, Order::count());
    }

    public function test_api_key_rajaongkir_tidak_pernah_masuk_pesan_galat(): void
    {
        Http::fake([self::URL_LOKASI.'*' => Http::response(['meta' => ['message' => 'key kunci-rajaongkir-uji salah', 'code' => 401]], 401)]);
        config(['app.debug' => true, 'biteship.ongkir_darurat' => true]);
        $this->isiKeranjang($this->produk());

        $badan = $this->ongkir()->getContent();

        $this->assertStringNotContainsString('kunci-rajaongkir-uji', (string) preg_replace('/key kunci-rajaongkir-uji salah/', '', $badan));
    }

    public function test_tanpa_kedua_api_key_ongkir_tidak_aktif_dan_tidak_memanggil_api(): void
    {
        config(['rajaongkir.api_key' => null]);
        Http::fake();
        $this->isiKeranjang($this->produk());

        $this->ongkir()->assertOk()->assertJsonPath('aktif', false);

        Http::assertNothingSent();
        $this->assertFalse(app(KeranjangService::class)->ringkasan()['ongkirDinamis']);
    }

    public function test_tanpa_api_key_rajaongkir_langsung_memakai_biteship(): void
    {
        config(['rajaongkir.api_key' => null]);
        $this->aktifkanBiteship();
        $this->isiKeranjang($this->produk());

        $this->ongkir()->assertOk()->assertJsonPath('opsi.0.price', 19000);

        $this->assertSame(0, $this->jumlahPermintaan('rajaongkir.komerce.id'));
    }
}
