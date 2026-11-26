<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\BiteshipException;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\BiteshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Ongkir toko dari Biteship (tarif kurir): endpoint /checkout/ongkir dan
 * checkout dengan layanan kurir pilihan pelanggan.
 */
class BiteshipOngkirTest extends TestCase
{
    use RefreshDatabase;

    private const SITUS = 'http://greendeahan.com';

    private const KUNCI = 'biteship_test.kunci-rahasia-uji';

    private const URL_TARIF = 'api.biteship.com/v1/rates/couriers';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'biteship.api_key' => self::KUNCI,
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
            ['31.74.01.1002', 'Tebet Timur', null],
        ] as [$kode, $nama, $kodepos]) {
            Wilayah::create(['kode' => $kode, 'nama' => $nama, 'level' => Wilayah::levelDari($kode), 'induk' => Wilayah::indukDari($kode), 'kodepos' => $kodepos]);
        }
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string, 4: int, 5: string}>  $baris  kode kurir, nama kurir, kode layanan, nama layanan, harga, rentang hari
     * @return array<string, mixed>
     */
    private function tarif(array $baris): array
    {
        return [
            'success' => true,
            'object' => 'courier_pricing',
            'pricing' => array_map(fn (array $b) => [
                'courier_code' => $b[0], 'courier_name' => $b[1], 'courier_service_code' => $b[2], 'courier_service_name' => $b[3],
                'price' => $b[4], 'shipment_duration_range' => $b[5], 'shipment_duration_unit' => 'days', 'duration' => $b[5].' days',
            ], $baris),
        ];
    }

    private function fakeTarif(?array $isi = null): void
    {
        Http::fake([
            self::URL_TARIF => Http::response($isi ?? $this->tarif([
                ['sicepat', 'SiCepat', 'reg', 'Reguler', 32000, '1 - 2'],
                ['jne', 'JNE', 'reg', 'Reguler', 11000, '2 - 3'],
                ['jnt', 'J&T', 'ez', 'EZ', 14000, '2 - 3'],
            ])),
            'api.mayar.io/hl/v2/payments/create' => Http::response(['statusCode' => 200, 'data' => ['id' => 'pay-1', 'transactionId' => 't-1', 'link' => 'https://testingmayar.myr.id/pl/abc']]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function produk(array $atribut = []): Product
    {
        return Product::factory()->create(array_merge(['sell_price' => 95000, 'weight_gram' => 300, 'supplier_id' => Supplier::factory()->create(['origin_postal_code' => null])->id], $atribut));
    }

    private function isiKeranjang(Product $produk, int $qty = 1): void
    {
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'qty' => $qty]);
    }

    /**
     * @return array<string, string>
     */
    private function dataCheckout(array $override = []): array
    {
        return array_merge([
            'customer_name' => 'Budi Santoso',
            'setuju' => '1',
            'phone' => '081234567890',
            'email' => 'budi@contoh.com',
            'address' => 'Jl. Melati No. 10, RT 01/RW 02',
            'wilayah_kode' => '31.74.01.1001',
            'layanan' => 'jne:reg',
        ], $override);
    }

    public function test_ongkir_menampilkan_layanan_termurah_dulu_dengan_durasi_bahasa_indonesia(): void
    {
        $this->fakeTarif();
        $this->isiKeranjang($this->produk());

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('aktif', true)
            ->assertJsonPath('opsi.0', ['kode' => 'jne:reg', 'courier_code' => 'jne', 'courier_name' => 'JNE', 'service_code' => 'reg', 'service_name' => 'Reguler', 'label' => 'JNE Reguler', 'duration' => '2 - 3 hari', 'price' => 11000, 'paket' => [['asal' => 'kode pos 13110', 'kodePos' => '13110', 'barang' => [Product::first()->name], 'price' => 11000]]])
            ->assertJsonPath('opsi.1.kode', 'jnt:ez')
            ->assertJsonPath('opsi.2.kode', 'sicepat:reg');
    }

    public function test_permintaan_ke_biteship_memakai_kode_pos_berat_dan_nilai_dari_database(): void
    {
        $this->fakeTarif();
        $produk = $this->produk(['name' => 'Bola Padel', 'sell_price' => 95000, 'weight_gram' => 250]);
        $this->isiKeranjang($produk, 3);

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertOk();

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), self::URL_TARIF)
            && $request->hasHeader('Authorization', self::KUNCI)
            && $request['origin_postal_code'] === 13110
            && $request['destination_postal_code'] === 12810
            && $request['couriers'] === 'jne,jnt,sicepat,anteraja'
            && $request['items'] === [['name' => 'Bola Padel', 'value' => 95000, 'weight' => 250, 'quantity' => 3]]);
    }

    public function test_berat_kosong_memakai_berat_bawaan(): void
    {
        $this->fakeTarif();
        $this->isiKeranjang($this->produk(['weight_gram' => 0]));

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertOk();

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), self::URL_TARIF) && $request['items'][0]['weight'] === 500);
    }

    public function test_tarif_yang_sama_tidak_memanggil_api_dua_kali(): void
    {
        $this->fakeTarif();
        $this->isiKeranjang($this->produk());

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertOk();
        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertOk();

        Http::assertSentCount(1);
    }

    public function test_kelurahan_tanpa_kode_pos_memakai_kode_pos_isian_pelanggan(): void
    {
        $this->fakeTarif();
        $this->isiKeranjang($this->produk());

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1002')->assertStatus(422);
        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1002&postal_code=12820')->assertOk();

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), self::URL_TARIF) && $request['destination_postal_code'] === 12820);
    }

    public function test_kode_wilayah_selain_kelurahan_ditolak(): void
    {
        Http::fake();

        foreach (['31', '31.74', '31.74.01', 'salah', ''] as $kode) {
            $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode='.$kode)->assertStatus(422);
        }

        Http::assertSentCount(0);
    }

    public function test_tanpa_api_key_ongkir_tidak_aktif_dan_tidak_memanggil_api(): void
    {
        config(['biteship.api_key' => null]);
        Http::fake();
        $this->isiKeranjang($this->produk());

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertOk()->assertExactJson(['aktif' => false, 'opsi' => []]);
        Http::assertSentCount(0);
    }

    public function test_asal_pengiriman_per_supplier_dan_layanan_yang_tersedia_di_semua_asal_dijumlahkan(): void
    {
        $surabaya = Supplier::factory()->create(['origin_postal_code' => '60111']);
        $a = $this->produk(['name' => 'Barang A']);
        $b = $this->produk(['name' => 'Barang B', 'supplier_id' => $surabaya->id]);
        Http::fake(function (HttpRequest $request) {
            $asal = $request['origin_postal_code'];

            return Http::response($asal === 13110
                ? $this->tarif([['jne', 'JNE', 'reg', 'Reguler', 11000, '2 - 3'], ['jnt', 'J&T', 'ez', 'EZ', 14000, '2 - 3'], ['sicepat', 'SiCepat', 'reg', 'Reguler', 20000, '1 - 2']])
                : $this->tarif([['jne', 'JNE', 'reg', 'Reguler', 9000, '1 - 2'], ['sicepat', 'SiCepat', 'reg', 'Reguler', 18000, '3 - 4']]));
        });
        $this->isiKeranjang($a);
        $this->isiKeranjang($b);

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')
            ->assertOk()
            ->assertJsonCount(2, 'opsi')
            ->assertJsonPath('opsi.0.kode', 'jne:reg')
            ->assertJsonPath('opsi.0.price', 20000)
            ->assertJsonPath('opsi.0.duration', '1 - 3 hari')
            ->assertJsonPath('opsi.1.kode', 'sicepat:reg')
            ->assertJsonPath('opsi.1.price', 38000);

        Http::assertSentCount(2);
        Http::assertSent(fn (HttpRequest $request) => $request['origin_postal_code'] === 60111 && $request['items'][0]['name'] === 'Barang B');
    }

    public function test_kode_pos_asal_produk_menimpa_kode_pos_supplier_dan_nama_asal_dari_wilayah(): void
    {
        foreach ([['35', 'Jawa Timur'], ['35.78', 'Kota Surabaya'], ['35.78.01', 'Tegalsari']] as [$kode, $nama]) {
            Wilayah::create(['kode' => $kode, 'nama' => $nama, 'level' => Wilayah::levelDari($kode), 'induk' => Wilayah::indukDari($kode)]);
        }
        Wilayah::create(['kode' => '35.78.01.1001', 'nama' => 'Keputran', 'level' => 4, 'induk' => '35.78.01', 'kodepos' => '60265']);
        $supplier = Supplier::factory()->create(['origin_postal_code' => '13110']);
        $this->fakeTarif();
        $this->isiKeranjang($this->produk(['supplier_id' => $supplier->id, 'origin_postal_code' => '60265']));

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')
            ->assertOk()
            ->assertJsonPath('opsi.0.paket.0.asal', 'Kota Surabaya')
            ->assertJsonPath('opsi.0.paket.0.kodePos', '60265');

        Http::assertSent(fn (HttpRequest $request) => $request['origin_postal_code'] === 60265);
    }

    public function test_cadangan_persen_dibulatkan_ke_atas_per_paket_dan_rincian_paket_sama_dengan_total(): void
    {
        config(['biteship.cadangan_persen' => 10]);
        $surabaya = Supplier::factory()->create(['origin_postal_code' => '60111']);
        $a = $this->produk(['name' => 'Barang A']);
        $b = $this->produk(['name' => 'Barang B', 'supplier_id' => $surabaya->id]);
        Http::fake(function (HttpRequest $request) {
            return Http::response($request['origin_postal_code'] === 13110
                ? $this->tarif([['jne', 'JNE', 'reg', 'Reguler', 11000, '2 - 3']])
                : $this->tarif([['jne', 'JNE', 'reg', 'Reguler', 9000, '1 - 2']]));
        });
        $this->isiKeranjang($a);
        $this->isiKeranjang($b);

        $respons = $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertOk();

        // 11.000 * 1,1 = 12.100 jadi 12.500; 9.000 * 1,1 = 9.900 jadi 10.000.
        $respons->assertJsonCount(2, 'opsi.0.paket')
            ->assertJsonPath('opsi.0.paket.0.price', 12500)
            ->assertJsonPath('opsi.0.paket.0.barang', ['Barang A'])
            ->assertJsonPath('opsi.0.paket.1.price', 10000)
            ->assertJsonPath('opsi.0.paket.1.barang', ['Barang B'])
            ->assertJsonPath('opsi.0.price', 22500);
    }

    public function test_checkout_menyimpan_ongkir_dengan_cadangan(): void
    {
        config(['biteship.cadangan_persen' => 10]);
        $this->fakeTarif();
        $this->isiKeranjang($this->produk());

        $this->post(self::SITUS.'/checkout', $this->dataCheckout());

        $this->assertSame(12500, Order::firstOrFail()->shipping_cost);
    }

    public function test_asal_pengiriman_tidak_diketahui_memberi_galat_yang_aman(): void
    {
        config(['biteship.origin_postal_code' => null]);
        Http::fake();
        $this->isiKeranjang($this->produk());

        config(['app.debug' => false]);
        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')
            ->assertStatus(502)
            ->assertJsonPath('message', 'Ongkir belum bisa dihitung, coba lagi sebentar lagi.')
            ->assertJsonMissingPath('detail');

        config(['app.debug' => true]);
        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertStatus(502)->assertJsonPath('detail', fn (string $detail) => str_contains($detail, 'Kode pos asal pengiriman') && ! str_contains($detail, self::KUNCI));
        Http::assertSentCount(0);
    }

    public function test_biteship_gagal_menawarkan_ongkir_tetap_sebagai_cadangan(): void
    {
        config(['biteship.ongkir_darurat' => true, 'app.debug' => false]);
        Http::fake([self::URL_TARIF => Http::response(['success' => false, 'code' => 50000000, 'error' => 'Server error'], 500)]);
        $this->isiKeranjang($this->produk());

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')
            ->assertOk()
            ->assertJsonPath('aktif', true)
            ->assertJsonPath('darurat', true)
            ->assertJsonCount(1, 'opsi')
            ->assertJsonPath('opsi.0.kode', 'darurat:tetap')
            ->assertJsonPath('opsi.0.price', 15000)
            ->assertJsonPath('opsi.0.paket', [])
            ->assertJsonMissingPath('detail');
    }

    public function test_cadangan_dimatikan_biteship_gagal_tetap_502(): void
    {
        Http::fake([self::URL_TARIF => Http::response(['success' => false, 'code' => 50000000, 'error' => 'Server error'], 500)]);
        $this->isiKeranjang($this->produk());

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertStatus(502)->assertJsonMissingPath('darurat');
    }

    public function test_checkout_dengan_ongkir_tetap_diterima_selama_biteship_masih_gagal(): void
    {
        config(['biteship.ongkir_darurat' => true, 'toko.ongkir_flat' => 17000]);
        Http::fake([
            self::URL_TARIF => Http::response(['success' => false, 'code' => 50000000, 'error' => 'Server error'], 500),
            'api.mayar.io/hl/v2/payments/create' => Http::response(['statusCode' => 200, 'data' => ['id' => 'pay-1', 'transactionId' => 't-1', 'link' => 'https://testingmayar.myr.id/pl/abc']]),
        ]);
        $this->isiKeranjang($this->produk(['sell_price' => 95000]));

        $this->post(self::SITUS.'/checkout', $this->dataCheckout(['layanan' => 'darurat:tetap']))->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame([17000, 112000, 'darurat', 'Ongkir tetap (kurir dipilih toko)'], [$order->shipping_cost, $order->total, $order->courier_code, $order->courier_service_name]);
        $this->assertTrue($order->ongkirDarurat());
    }

    public function test_ongkir_tetap_ditolak_kalau_biteship_sudah_normal_atau_cadangan_dimatikan(): void
    {
        config(['biteship.ongkir_darurat' => true]);
        $this->fakeTarif();
        $this->isiKeranjang($this->produk());

        $this->post(self::SITUS.'/checkout', $this->dataCheckout(['layanan' => 'darurat:tetap']))->assertSessionHasErrors('layanan');
        $this->assertSame(0, Order::count());

        config(['biteship.ongkir_darurat' => false]);
        Http::fake([self::URL_TARIF => Http::response(['success' => false, 'code' => 50000000, 'error' => 'Server error'], 500)]);

        $this->post(self::SITUS.'/checkout', $this->dataCheckout(['layanan' => 'darurat:tetap']))->assertSessionHasErrors('layanan');
        $this->assertSame(0, Order::count());
    }

    public function test_kode_pos_asal_belum_diisi_juga_memakai_ongkir_tetap_dan_alasannya_tampil_saat_debug(): void
    {
        config(['biteship.ongkir_darurat' => true, 'biteship.origin_postal_code' => null, 'app.debug' => true]);
        Http::fake();
        $this->isiKeranjang($this->produk());

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')
            ->assertOk()
            ->assertJsonPath('darurat', true)
            ->assertJsonPath('detail', fn (string $detail) => str_contains($detail, 'Kode pos asal pengiriman'));
    }

    public function test_tidak_ada_kurir_untuk_rute_itu_bukan_galat(): void
    {
        Http::fake([self::URL_TARIF => Http::response(['success' => false, 'code' => 40001010, 'error' => 'No courier available'], 400)]);
        $this->isiKeranjang($this->produk());

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertOk()->assertJsonPath('opsi', []);
    }

    public function test_galat_api_biteship_tidak_membocorkan_api_key(): void
    {
        Http::fake([self::URL_TARIF => Http::response(['success' => false, 'code' => 40101001, 'error' => 'Authorization failed'], 401)]);
        $this->isiKeranjang($this->produk());

        $respons = $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertStatus(502);

        $this->assertStringNotContainsString(self::KUNCI, $respons->getContent());

        try {
            app(BiteshipService::class)->tarif('13110', '12810', [['name' => 'X', 'value' => 1000, 'weight' => 100, 'quantity' => 1]]);
            $this->fail('Seharusnya melempar BiteshipException.');
        } catch (BiteshipException $galat) {
            $this->assertStringNotContainsString(self::KUNCI, $galat->getMessage());
            $this->assertStringContainsString('401', $galat->getMessage());
        }
    }

    public function test_api_key_live_ditolak_selama_mode_sandbox(): void
    {
        config(['biteship.api_key' => 'biteship_live.punya-uang-asli']);
        Http::fake();
        $this->isiKeranjang($this->produk());

        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertStatus(502);
        Http::assertSentCount(0);

        config(['biteship.env' => 'production']);
        Http::fake([self::URL_TARIF => Http::response($this->tarif([['jne', 'JNE', 'reg', 'Reguler', 11000, '2 - 3']]))]);
        $this->getJson(self::SITUS.'/checkout/ongkir?wilayah_kode=31.74.01.1001')->assertOk();
    }

    public function test_ringkasan_keranjang_tidak_memakai_ongkir_flat_saat_biteship_aktif(): void
    {
        $this->isiKeranjang($this->produk(['sell_price' => 95000]), 2);

        $this->get(self::SITUS.'/checkout')->assertInertia(fn (Assert $page) => $page
            ->where('keranjang.ongkirDinamis', true)
            ->where('keranjang.ongkir', null)
            ->where('keranjang.total', 190000)
            ->where('ongkirUrl', url('/checkout/ongkir'))
        );
    }

    public function test_checkout_memakai_ongkir_layanan_pilihan_hasil_hitung_ulang_server(): void
    {
        $this->fakeTarif();
        $this->isiKeranjang($this->produk(['sell_price' => 95000]), 2);

        $this->post(self::SITUS.'/checkout', $this->dataCheckout(['shipping_cost' => 1, 'total' => 1, 'price' => 1]))
            ->assertRedirect('https://testingmayar.myr.id/pl/abc');

        $order = Order::sole();
        $this->assertSame([190000, 11000, 201000], [$order->subtotal, $order->shipping_cost, $order->total]);
        $this->assertSame(['jne', 'reg', 'JNE Reguler', '2 - 3 hari'], [$order->courier_code, $order->courier_service_code, $order->courier_service_name, $order->shipping_duration]);
        $this->assertSame('JNE Reguler (2 - 3 hari)', $order->layananPengiriman());
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'payments/create') && $request['amount'] === 201000);
    }

    public function test_checkout_tanpa_layanan_atau_dengan_layanan_tidak_dikenal_ditolak(): void
    {
        $this->fakeTarif();
        $this->isiKeranjang($this->produk());

        $this->post(self::SITUS.'/checkout', $this->dataCheckout(['layanan' => '']))->assertSessionHasErrors('layanan');
        $this->post(self::SITUS.'/checkout', $this->dataCheckout(['layanan' => 'gojek:instant']))->assertSessionHasErrors('layanan');
        $this->post(self::SITUS.'/checkout', $this->dataCheckout(['layanan' => 'bukan format']))->assertSessionHasErrors('layanan');

        $this->assertSame(0, Order::count());
        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), 'payments/create'));
    }

    public function test_checkout_gagal_dengan_pesan_aman_saat_biteship_bermasalah(): void
    {
        Http::fake([self::URL_TARIF => Http::response('', 500)]);
        $this->isiKeranjang($this->produk());

        $this->post(self::SITUS.'/checkout', $this->dataCheckout())->assertSessionHasErrors(['layanan' => 'Ongkir belum bisa dihitung, coba lagi sebentar lagi.']);
        $this->assertSame(0, Order::count());
    }

    public function test_checkout_dengan_keranjang_kosong_tetap_dialihkan_ke_keranjang(): void
    {
        Http::fake();

        $this->post(self::SITUS.'/checkout', $this->dataCheckout())->assertRedirect(self::SITUS.'/keranjang');
    }

    public function test_tanpa_biteship_checkout_tetap_memakai_ongkir_flat(): void
    {
        config(['biteship.api_key' => null]);
        Http::fake(['api.mayar.io/hl/v2/payments/create' => Http::response(['statusCode' => 200, 'data' => ['id' => 'pay-1', 'transactionId' => 't-1', 'link' => 'https://testingmayar.myr.id/pl/abc']])]);
        $this->isiKeranjang($this->produk(['sell_price' => 95000]));

        $this->post(self::SITUS.'/checkout', $this->dataCheckout(['layanan' => '']))->assertRedirect('https://testingmayar.myr.id/pl/abc');

        $order = Order::sole();
        $this->assertSame([15000, 110000, null], [$order->shipping_cost, $order->total, $order->layananPengiriman()]);
    }

    public function test_supplier_menyimpan_kode_pos_asal(): void
    {
        $admin = User::factory()->create();
        $admin->is_superadmin = true;
        $admin->save();

        $this->actingAs($admin)->post('/superadmin/toko/supplier', ['name' => 'RANK Sports Official', 'platform' => 'shopee', 'origin_postal_code' => '13330'])->assertRedirect();
        $this->assertSame('13330', Supplier::where('name', 'RANK Sports Official')->value('origin_postal_code'));

        $this->actingAs($admin)->post('/superadmin/toko/supplier', ['name' => 'Salah', 'platform' => 'shopee', 'origin_postal_code' => '123'])->assertSessionHasErrors('origin_postal_code');
        $this->actingAs($admin)->get('/superadmin/toko/supplier')->assertInertia(fn (Assert $page) => $page->where('supplier.0.origin_postal_code', '13330'));
    }

    public function test_halaman_pesanan_menampilkan_layanan_kurir_yang_dipilih(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Paid, 'courier_service_name' => 'JNE Reguler', 'shipping_duration' => '2 - 3 hari',
        ]);

        $this->get(self::SITUS.'/pesanan/'.$order->code)->assertInertia(fn (Assert $page) => $page->where('pesanan.layanan', 'JNE Reguler (2 - 3 hari)'));
    }
}
