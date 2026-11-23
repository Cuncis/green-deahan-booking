<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Dropdown alamat checkout toko: endpoint /wilayah, perintah wilayah:impor,
 * dan alamat pesanan yang selalu disusun dari tabel wilayah.
 */
class WilayahCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const SITUS = 'http://greendeahan.com';

    protected function setUp(): void
    {
        parent::setUp();

        config(['mayar.api_key' => 'kunci-uji', 'mayar.env' => 'sandbox']);

        foreach ([
            ['31', 'DKI Jakarta', null],
            ['32', 'Jawa Barat', null],
            ['31.74', 'Kota Administrasi Jakarta Selatan', null],
            ['31.71', 'Kota Administrasi Jakarta Pusat', null],
            ['31.74.01', 'Tebet', null],
            ['31.74.02', 'Setiabudi', null],
            ['31.74.01.1001', 'Tebet Barat', '12810'],
            ['31.74.01.1002', 'Tebet Timur', null],
        ] as [$kode, $nama, $kodepos]) {
            Wilayah::create(['kode' => $kode, 'nama' => $nama, 'level' => Wilayah::levelDari($kode), 'induk' => Wilayah::indukDari($kode), 'kodepos' => $kodepos]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function data(array $override = []): array
    {
        return array_merge([
            'customer_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi@contoh.com',
            'address' => 'Jl. Melati No. 10, RT 01/RW 02',
            'wilayah_kode' => '31.74.01.1001',
        ], $override);
    }

    private function isiKeranjang(): void
    {
        Http::fake(['api.mayar.io/hl/v2/payments/create' => Http::response(['statusCode' => 200, 'data' => ['id' => 'pay-1', 'transactionId' => 't-1', 'link' => 'https://testingmayar.myr.id/pl/abc']])]);
        $produk = Product::factory()->create(['sell_price' => 95000]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);
    }

    public function test_daftar_provinsi_tanpa_parameter(): void
    {
        $this->getJson(self::SITUS.'/wilayah')
            ->assertOk()
            ->assertExactJson([['kode' => '31', 'nama' => 'DKI Jakarta'], ['kode' => '32', 'nama' => 'Jawa Barat']])
            ->assertHeader('Cache-Control', 'max-age=86400, public');
    }

    public function test_daftar_satu_tingkat_di_bawah_induk_terurut_nama(): void
    {
        $this->getJson(self::SITUS.'/wilayah?induk=31')->assertExactJson([['kode' => '31.71', 'nama' => 'Kota Administrasi Jakarta Pusat'], ['kode' => '31.74', 'nama' => 'Kota Administrasi Jakarta Selatan']]);
        $this->getJson(self::SITUS.'/wilayah?induk=31.74')->assertExactJson([['kode' => '31.74.02', 'nama' => 'Setiabudi'], ['kode' => '31.74.01', 'nama' => 'Tebet']]);
    }

    public function test_kelurahan_membawa_kode_pos_bila_ada(): void
    {
        $this->getJson(self::SITUS.'/wilayah?induk=31.74.01')->assertExactJson([
            ['kode' => '31.74.01.1001', 'nama' => 'Tebet Barat', 'kodepos' => '12810'],
            ['kode' => '31.74.01.1002', 'nama' => 'Tebet Timur'],
        ]);
    }

    public function test_kode_induk_tidak_valid_ditolak(): void
    {
        foreach (['abc', '31.74.01.1001', "31' OR 1=1", '3', '31..74'] as $induk) {
            $this->getJson(self::SITUS.'/wilayah?'.http_build_query(['induk' => $induk]))->assertStatus(422);
        }

        $this->getJson(self::SITUS.'/wilayah?induk[]=31')->assertStatus(422);
    }

    public function test_halaman_checkout_hanya_di_situs_korporat(): void
    {
        $this->getJson('http://localhost/wilayah')->assertNotFound();
    }

    public function test_alamat_pesanan_disusun_dari_tabel_wilayah_bukan_dari_browser(): void
    {
        $this->isiKeranjang();

        $this->post(self::SITUS.'/checkout', $this->data([
            'province' => 'Provinsi Palsu', 'city' => 'Kota Palsu', 'district' => 'Kecamatan Palsu', 'village' => 'Kelurahan Palsu', 'postal_code' => '99999',
        ]))->assertRedirect('https://testingmayar.myr.id/pl/abc');

        $order = Order::sole();
        $this->assertSame(
            ['DKI Jakarta', 'Kota Administrasi Jakarta Selatan', 'Tebet', 'Tebet Barat', '12810', '31.74.01.1001'],
            [$order->province, $order->city, $order->district, $order->village, $order->postal_code, $order->wilayah_kode],
        );
        $this->assertSame('Jl. Melati No. 10, RT 01/RW 02, Kel. Tebet Barat, Kec. Tebet, Kota Administrasi Jakarta Selatan, DKI Jakarta 12810', $order->alamatPengiriman());
    }

    public function test_kelurahan_tanpa_kode_pos_meminta_kode_pos_manual(): void
    {
        $this->isiKeranjang();

        $this->post(self::SITUS.'/checkout', $this->data(['wilayah_kode' => '31.74.01.1002']))->assertSessionHasErrors('postal_code');
        $this->assertSame(0, Order::count());

        $this->post(self::SITUS.'/checkout', $this->data(['wilayah_kode' => '31.74.01.1002', 'postal_code' => '12820']))->assertRedirect('https://testingmayar.myr.id/pl/abc');
        $this->assertSame('12820', Order::sole()->postal_code);
    }

    public function test_hanya_kode_kelurahan_yang_diterima(): void
    {
        $this->isiKeranjang();

        foreach (['31', '31.74', '31.74.01', '99.99.99.9999', ''] as $kode) {
            $this->post(self::SITUS.'/checkout', $this->data(['wilayah_kode' => $kode]))->assertSessionHasErrors('wilayah_kode');
        }

        $this->assertSame(0, Order::count());
        Http::assertNothingSent();
    }

    public function test_halaman_checkout_memberi_tahu_apakah_data_wilayah_tersedia(): void
    {
        $this->isiKeranjang();

        $this->get(self::SITUS.'/checkout')->assertInertia(fn (Assert $page) => $page->where('wilayahTersedia', true)->where('wilayahUrl', url('/wilayah')));

        Wilayah::query()->delete();
        $this->get(self::SITUS.'/checkout')->assertInertia(fn (Assert $page) => $page->where('wilayahTersedia', false));
    }

    public function test_perintah_impor_mengisi_tabel_dan_aman_dijalankan_ulang(): void
    {
        Wilayah::query()->delete();
        $berkas = tempnam(sys_get_temp_dir(), 'wil').'.tsv.gz';
        file_put_contents($berkas, gzencode("11\tAceh\t\n11.01\tKabupaten Aceh Selatan\t\n11.01.01\tBakongan\t\n11.01.01.2001\tKeude Bakongan\t23773\nsampah\n"));

        $this->artisan('wilayah:impor', ['--berkas' => $berkas])->expectsOutput('4 wilayah diimpor.')->assertSuccessful();
        $this->artisan('wilayah:impor', ['--berkas' => $berkas])->assertSuccessful();

        $this->assertSame(4, Wilayah::count());
        $kelurahan = Wilayah::find('11.01.01.2001');
        $this->assertSame(['Keude Bakongan', 4, '11.01.01', '23773'], [$kelurahan->nama, $kelurahan->level, $kelurahan->induk, $kelurahan->kodepos]);
        $this->assertNull(Wilayah::find('11')->induk);
        unlink($berkas);
    }

    public function test_perintah_impor_gagal_bila_berkas_tidak_ada(): void
    {
        $this->artisan('wilayah:impor', ['--berkas' => '/tidak/ada.tsv.gz'])->assertFailed();
    }

    public function test_berkas_data_bawaan_lengkap_dan_konsisten(): void
    {
        Wilayah::query()->delete();
        $this->artisan('wilayah:impor')->assertSuccessful();

        $this->assertSame([38, 91599], [Wilayah::where('level', 1)->count(), Wilayah::count()]);
        $this->assertSame(0, Wilayah::where('level', 4)->whereNull('kodepos')->count());
        $this->assertSame(0, Wilayah::where('level', '>', 1)->whereNotIn('induk', Wilayah::select('kode'))->count());
        $this->assertTrue(Wilayah::where('nama', 'Daerah Khusus Ibukota Jakarta')->where('level', 1)->exists());
    }

    public function test_order_pdf_email_tidak_terpengaruh_pesanan_lama_tanpa_kelurahan(): void
    {
        $order = Order::factory()->create(['district' => null, 'village' => null, 'wilayah_kode' => null]);

        $this->assertSame("{$order->address}, {$order->city}, {$order->province} {$order->postal_code}", $order->alamatPengiriman());
        Http::assertNothingSent();
    }
}
