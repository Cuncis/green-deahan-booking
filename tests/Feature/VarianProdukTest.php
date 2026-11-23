<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Mail\PesananTokoDibayar;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\ImporProdukHtmlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Varian produk (warna, ukuran): pilihan di halaman produk, baris keranjang
 * per varian, harga varian di checkout, pengelolaan di admin, dan impor dari
 * kotak pilihan Shopee.
 */
class VarianProdukTest extends TestCase
{
    use RefreshDatabase;

    private const SITUS = 'http://greendeahan.com';

    protected function setUp(): void
    {
        parent::setUp();

        config(['mayar.api_key' => 'kunci-uji', 'mayar.env' => 'sandbox', 'biteship.api_key' => null]);
    }

    /**
     * Produk berharga 38.000 dengan varian HITAM (harga produk), PUTIH (harga sendiri), dan ABU (habis).
     *
     * @return array{0: Product, 1: ProductVariant, 2: ProductVariant, 3: ProductVariant}
     */
    private function produkBerVarian(): array
    {
        $produk = Product::factory()->create(['name' => 'Kaos Kaki Helios', 'sell_price' => 38000, 'cost_price' => 24000, 'variant_label' => 'Warna']);
        $hitam = ProductVariant::factory()->create(['product_id' => $produk->id, 'name' => 'HITAM', 'sort_order' => 0]);
        $putih = ProductVariant::factory()->create(['product_id' => $produk->id, 'name' => 'PUTIH', 'sell_price' => 42000, 'cost_price' => 27000, 'sort_order' => 1]);
        $abu = ProductVariant::factory()->habis()->create(['product_id' => $produk->id, 'name' => 'ABU', 'sort_order' => 2]);

        return [$produk, $hitam, $putih, $abu];
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    // --- Keranjang ---

    public function test_produk_berrvarian_wajib_memilih_varian_sebelum_masuk_keranjang(): void
    {
        [$produk] = $this->produkBerVarian();

        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id])->assertSessionHas('error', 'Pilih warna dulu.');
        $this->get(self::SITUS.'/keranjang/ringkas')->assertJsonPath('jumlahItem', 0);
    }

    public function test_varian_masuk_keranjang_dengan_kunci_dan_harga_varian(): void
    {
        [$produk, $hitam, $putih] = $this->produkBerVarian();

        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $hitam->id, 'qty' => 2])->assertSessionHas('success', 'Kaos Kaki Helios (HITAM) masuk keranjang.');
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $putih->id, 'qty' => 1]);

        $this->getJson(self::SITUS.'/keranjang/ringkas')
            ->assertJsonPath('jumlahItem', 3)
            ->assertJsonPath('subtotal', 2 * 38000 + 42000)
            ->assertJsonPath('items.0.kunci', "{$produk->id}:{$hitam->id}")
            ->assertJsonPath('items.0.varian', 'HITAM')
            ->assertJsonPath('items.0.price', 38000)
            ->assertJsonPath('items.1.kunci', "{$produk->id}:{$putih->id}")
            ->assertJsonPath('items.1.price', 42000)
            ->assertJsonPath('items.1.subtotal', 42000);
    }

    public function test_varian_yang_sama_menambah_jumlah_dan_varian_berbeda_jadi_baris_terpisah(): void
    {
        [$produk, $hitam, $putih] = $this->produkBerVarian();

        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $hitam->id, 'qty' => 1]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $hitam->id, 'qty' => 2]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $putih->id, 'qty' => 1]);

        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertJsonCount(2, 'items')->assertJsonPath('items.0.qty', 3);
    }

    public function test_varian_habis_milik_produk_lain_atau_tidak_ada_ditolak(): void
    {
        [$produk, , , $abu] = $this->produkBerVarian();
        $lain = ProductVariant::factory()->create();

        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $abu->id])->assertSessionHas('error', 'ABU sedang habis, pilih yang lain.');
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $lain->id])->assertSessionHas('error', 'Pilih warna dulu.');
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => 99999])->assertSessionHas('error', 'Pilih warna dulu.');

        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertJsonPath('jumlahItem', 0);
    }

    public function test_produk_tanpa_varian_menolak_variant_id(): void
    {
        $produk = Product::factory()->create();
        $varian = ProductVariant::factory()->create();

        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $varian->id])->assertSessionHas('error', 'Pilihan varian tidak berlaku untuk produk ini.');
        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertJsonPath('jumlahItem', 0);
    }

    public function test_ubah_dan_hapus_baris_varian_lewat_kunci(): void
    {
        [$produk, $hitam, $putih] = $this->produkBerVarian();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $hitam->id]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $putih->id]);

        $this->patch(self::SITUS."/keranjang/{$produk->id}:{$hitam->id}", ['qty' => 4])->assertRedirect();
        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertJsonPath('items.0.qty', 4)->assertJsonPath('items.1.qty', 1);

        $this->delete(self::SITUS."/keranjang/{$produk->id}:{$hitam->id}")->assertRedirect();
        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertJsonCount(1, 'items')->assertJsonPath('items.0.varian', 'PUTIH');

        $this->patch(self::SITUS.'/keranjang/abc:def', ['qty' => 2])->assertNotFound();
    }

    public function test_jumlah_di_kartu_produk_menjumlahkan_semua_varian(): void
    {
        [$produk, $hitam, $putih] = $this->produkBerVarian();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $hitam->id, 'qty' => 2]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $putih->id, 'qty' => 3]);

        $this->get(self::SITUS.'/toko')->assertInertia(fn (Assert $page) => $page->where("qtyKeranjang.{$produk->id}", 5));
    }

    public function test_baris_keranjang_yang_tidak_sah_lagi_dibuang(): void
    {
        [$produk, $hitam, $putih] = $this->produkBerVarian();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $hitam->id]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $putih->id]);

        $hitam->update(['is_available' => false]);
        $putih->delete();

        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertJsonPath('jumlahItem', 0)->assertJsonCount(0, 'items');
    }

    public function test_produk_yang_baru_punya_varian_membuang_baris_lama_tanpa_varian(): void
    {
        $produk = Product::factory()->create();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);
        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertJsonPath('jumlahItem', 1);

        ProductVariant::factory()->create(['product_id' => $produk->id]);

        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertJsonPath('jumlahItem', 0);
    }

    // --- Checkout ---

    public function test_checkout_menyimpan_varian_dengan_harga_dan_modal_varian(): void
    {
        foreach ([['31', 'DKI Jakarta'], ['31.74', 'Kota Administrasi Jakarta Selatan'], ['31.74.01', 'Tebet']] as [$kode, $nama]) {
            Wilayah::create(['kode' => $kode, 'nama' => $nama, 'level' => Wilayah::levelDari($kode), 'induk' => Wilayah::indukDari($kode)]);
        }
        Wilayah::create(['kode' => '31.74.01.1001', 'nama' => 'Tebet Barat', 'level' => 4, 'induk' => '31.74.01', 'kodepos' => '12810']);
        Http::fake(['api.mayar.io/hl/v2/payments/create' => Http::response(['statusCode' => 200, 'data' => ['id' => 'pay-1', 'transactionId' => 't-1', 'link' => 'https://testingmayar.myr.id/pl/abc']])]);
        [$produk, $hitam, $putih] = $this->produkBerVarian();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $hitam->id, 'qty' => 2]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'variant_id' => $putih->id, 'qty' => 1]);

        $this->post(self::SITUS.'/checkout', [
            'customer_name' => 'Budi Santoso', 'phone' => '081234567890', 'email' => 'budi@contoh.com',
            'address' => 'Jl. Melati No. 10, RT 01/RW 02', 'wilayah_kode' => '31.74.01.1001',
        ])->assertRedirect('https://testingmayar.myr.id/pl/abc');

        $order = Order::firstOrFail();
        $this->assertSame(2 * 38000 + 42000, $order->subtotal);
        $this->assertSame(2 * 38000 + 42000 + 15000, $order->total);

        $baris = $order->items()->orderBy('id')->get();
        $this->assertSame(['HITAM', $hitam->id, 38000, 24000, 2], [$baris[0]->variant_name, $baris[0]->variant_id, $baris[0]->unit_price, $baris[0]->unit_cost, $baris[0]->qty]);
        $this->assertSame(['PUTIH', $putih->id, 42000, 27000, 1], [$baris[1]->variant_name, $baris[1]->variant_id, $baris[1]->unit_price, $baris[1]->unit_cost, $baris[1]->qty]);
        $this->assertSame('Kaos Kaki Helios (HITAM)', $baris[0]->namaLengkap());
        $this->assertSame(2 * 38000 + 42000 - (2 * 24000 + 27000), $order->untung());

        Http::assertSent(fn ($request) => str_contains($request['description'] ?? '', '2x Kaos Kaki Helios (HITAM)') && str_contains($request['description'], '1x Kaos Kaki Helios (PUTIH)'));
    }

    // --- Halaman toko ---

    public function test_halaman_produk_dan_daftar_mengirim_varian_dengan_harga_dan_ketersediaan(): void
    {
        [$produk] = $this->produkBerVarian();

        $this->get(self::SITUS."/toko/{$produk->slug}")->assertInertia(fn (Assert $page) => $page
            ->where('produk.variantLabel', 'Warna')
            ->has('produk.variants', 3)
            ->where('produk.variants.0', fn ($varian) => $varian['name'] === 'HITAM' && $varian['price'] === 38000 && $varian['available'] === true)
            ->where('produk.variants.1.price', 42000)
            ->where('produk.variants.2.available', false)
        );

        $this->get(self::SITUS.'/toko')->assertInertia(fn (Assert $page) => $page->where('produk.data.0.variants.1.name', 'PUTIH'));
    }

    public function test_harga_modal_varian_tidak_bocor_ke_halaman_pelanggan(): void
    {
        [$produk] = $this->produkBerVarian();

        $respons = $this->get(self::SITUS."/toko/{$produk->slug}")->assertOk();

        $this->assertStringNotContainsString('cost_price', $respons->getContent());
        $this->assertStringNotContainsString('27000', $respons->getContent());
    }

    public function test_pesanan_menampilkan_varian_di_halaman_pelanggan_admin_dan_email(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Paid, 'paid_at' => now()]);
        $order->items()->create(['product_id' => null, 'product_name' => 'Kaos Kaki Helios', 'variant_name' => 'HITAM', 'unit_price' => 38000, 'unit_cost' => 24000, 'qty' => 2]);

        $this->get(self::SITUS."/pesanan/{$order->code}")->assertInertia(fn (Assert $page) => $page->where('pesanan.items.0.varian', 'HITAM'));
        $this->actingAs($this->superadmin())->get("/superadmin/toko/pesanan/{$order->id}")->assertInertia(fn (Assert $page) => $page->where('pesanan.items.0.varian', 'HITAM'));
        (new PesananTokoDibayar($order->fresh()))->assertSeeInHtml('Kaos Kaki Helios (HITAM)');
    }

    // --- Admin ---

    /**
     * @return array<string, mixed>
     */
    private function dasarProduk(): array
    {
        return ['name' => 'Kaos Kaki Helios', 'slug' => 'kaos-kaki-helios', 'category' => 'olahraga', 'unit' => 'pcs', 'sell_price' => 38000, 'cost_price' => 24000, 'weight_gram' => 120, 'is_active' => '1'];
    }

    public function test_admin_menyimpan_varian_baris_kosong_dibuang_dan_harga_kosong_jadi_null(): void
    {
        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', [...$this->dasarProduk(), 'variant_label' => 'Warna', 'variants' => [
            ['id' => '', 'name' => ' HITAM ', 'sell_price' => '', 'cost_price' => '', 'is_available' => '1'],
            ['id' => '', 'name' => '', 'sell_price' => '', 'cost_price' => '', 'is_available' => '1'],
            ['id' => '', 'name' => 'PUTIH', 'sell_price' => '42000', 'cost_price' => '27000', 'is_available' => '0'],
        ]])->assertSessionHasNoErrors();

        $produk = Product::sole();
        $this->assertSame('Warna', $produk->variant_label);

        $daftar = $produk->variants;
        $this->assertSame(['HITAM', null, null, true, 0], [$daftar[0]->name, $daftar[0]->sell_price, $daftar[0]->cost_price, $daftar[0]->is_available, $daftar[0]->sort_order]);
        $this->assertSame(['PUTIH', 42000, 27000, false, 1], [$daftar[1]->name, $daftar[1]->sell_price, $daftar[1]->cost_price, $daftar[1]->is_available, $daftar[1]->sort_order]);
    }

    public function test_varian_tanpa_judul_memakai_judul_bawaan_dan_tanpa_varian_judul_dikosongkan(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->post('/superadmin/toko/produk', [...$this->dasarProduk(), 'variants' => [['name' => 'HITAM', 'is_available' => '1']]])->assertSessionHasNoErrors();
        $produk = Product::sole();
        $this->assertSame('Varian', $produk->variant_label);

        $this->actingAs($admin)->put("/superadmin/toko/produk/{$produk->id}", [...$this->dasarProduk(), 'variant_label' => 'Warna'])->assertSessionHasNoErrors();
        $this->assertNull($produk->fresh()->variant_label);
        $this->assertSame(0, $produk->variants()->count());
    }

    public function test_update_varian_mempertahankan_id_menambah_dan_menghapus(): void
    {
        [$produk, $hitam, $putih] = $this->produkBerVarian();
        $abu = $produk->variants()->where('name', 'ABU')->first();

        $this->actingAs($this->superadmin())->put("/superadmin/toko/produk/{$produk->id}", [
            ...$this->dasarProduk(), 'slug' => $produk->slug, 'variant_label' => 'Warna',
            'variants' => [
                ['id' => (string) $putih->id, 'name' => 'PUTIH SUSU', 'sell_price' => '43000', 'cost_price' => '', 'is_available' => '1'],
                ['id' => (string) $hitam->id, 'name' => 'HITAM', 'sell_price' => '', 'cost_price' => '', 'is_available' => '0'],
                ['id' => '', 'name' => 'MERAH', 'sell_price' => '', 'cost_price' => '', 'is_available' => '1'],
            ],
        ])->assertSessionHasNoErrors();

        $daftar = $produk->variants()->get();
        $this->assertSame(['PUTIH SUSU', 'HITAM', 'MERAH'], $daftar->pluck('name')->all());
        $this->assertSame([$putih->id, $hitam->id], $daftar->take(2)->pluck('id')->all());
        $this->assertSame(43000, $putih->fresh()->sell_price);
        $this->assertNull($putih->fresh()->cost_price);
        $this->assertFalse($hitam->fresh()->is_available);
        $this->assertNull(ProductVariant::find($abu->id));
    }

    public function test_validasi_varian(): void
    {
        $admin = $this->superadmin();
        $kirim = fn (array $variants) => $this->actingAs($admin)->post('/superadmin/toko/produk', [...$this->dasarProduk(), 'variants' => $variants]);

        $kirim([['name' => 'HITAM'], ['name' => 'hitam']])->assertSessionHasErrors('variants.0.name');
        $kirim([['name' => str_repeat('a', 81)]])->assertSessionHasErrors('variants.0.name');
        $kirim([['name' => 'HITAM', 'sell_price' => '500']])->assertSessionHasErrors('variants.0.sell_price');
        $kirim([['name' => 'HITAM', 'cost_price' => '-1']])->assertSessionHasErrors('variants.0.cost_price');
        $kirim([['name' => 'HITAM', 'sell_price' => 'abc']])->assertSessionHasErrors('variants.0.sell_price');
        $kirim(array_map(fn (int $nomor) => ['name' => "Varian {$nomor}"], range(1, 31)))->assertSessionHasErrors('variants');

        $this->assertSame(0, Product::count());
    }

    public function test_id_varian_milik_produk_lain_ditolak(): void
    {
        [$produk] = $this->produkBerVarian();
        $milikLain = ProductVariant::factory()->create(['name' => 'ASING']);

        $this->actingAs($this->superadmin())->put("/superadmin/toko/produk/{$produk->id}", [
            ...$this->dasarProduk(), 'slug' => $produk->slug, 'variant_label' => 'Warna',
            'variants' => [['id' => (string) $milikLain->id, 'name' => 'DIAMBIL', 'is_available' => '1']],
        ])->assertSessionHasErrors('variants');

        $this->assertSame('ASING', $milikLain->fresh()->name);
        $this->assertSame(3, $produk->variants()->count());
    }

    public function test_halaman_edit_admin_mengirim_varian(): void
    {
        [$produk] = $this->produkBerVarian();

        $this->actingAs($this->superadmin())->get("/superadmin/toko/produk/{$produk->id}/edit")->assertInertia(fn (Assert $page) => $page
            ->where('produk.variant_label', 'Warna')
            ->has('produk.variants', 3)
            ->where('produk.variants.1', fn ($varian) => $varian['name'] === 'PUTIH' && $varian['sell_price'] === 42000 && $varian['cost_price'] === 27000 && $varian['is_available'] === true)
            ->where('produk.variants.2.is_available', false)
        );
    }

    public function test_menghapus_produk_menghapus_variannya_dan_pesanan_lama_tetap_utuh(): void
    {
        [$produk, $hitam] = $this->produkBerVarian();
        $order = Order::factory()->create();
        $order->items()->create(['product_id' => $produk->id, 'variant_id' => $hitam->id, 'product_name' => 'Kaos Kaki Helios', 'variant_name' => 'HITAM', 'unit_price' => 38000, 'unit_cost' => 24000, 'qty' => 1]);

        $this->actingAs($this->superadmin())->delete("/superadmin/toko/produk/{$produk->id}")->assertRedirect();

        $this->assertSame(0, ProductVariant::count());
        $item = $order->items()->sole();
        $this->assertSame(['HITAM', null], [$item->variant_name, $item->variant_id]);
        $this->assertSame('Kaos Kaki Helios (HITAM)', $item->namaLengkap());
    }

    // --- Impor Shopee ---

    private function tombol(string $nama, bool $habis = false): string
    {
        return '<button class="lK5dVS selection-box-'.($habis ? 'disabled' : 'unselected').'" aria-label="'.$nama.'" aria-disabled="'.($habis ? 'true' : 'false').'"><span>'.$nama.'</span></button>';
    }

    public function test_impor_membaca_satu_kelompok_varian_dan_menandai_yang_habis(): void
    {
        $html = '<h1>Kaos Kaki</h1><section class="flex"><h2>Warna</h2><div><div>'.$this->tombol('BLACK').$this->tombol('WHITE').$this->tombol('GREY', true).'</div></div></section>'
            .'<section class="flex"><h2>Kuantitas</h2><div><button aria-label="Decrease"></button><input value="1"><button aria-label="Increase"></button></div></section>';

        $hasil = (new ImporProdukHtmlService)->baca($html);

        $this->assertSame('Warna', $hasil['varian']['label']);
        $this->assertSame([
            ['nama' => 'BLACK', 'tersedia' => true],
            ['nama' => 'WHITE', 'tersedia' => true],
            ['nama' => 'GREY', 'tersedia' => false],
        ], $hasil['varian']['opsi']);
    }

    public function test_impor_menggabungkan_dua_kelompok_varian_jadi_kombinasi(): void
    {
        $html = '<h1>Jersey</h1><section><h2>Warna</h2><div>'.$this->tombol('RED').$this->tombol('BLUE').'</div></section>'
            .'<section><h2>Ukuran</h2><div>'.$this->tombol('M').$this->tombol('L', true).'</div></section>';

        $hasil = (new ImporProdukHtmlService)->baca($html)['varian'];

        $this->assertSame('Warna / Ukuran', $hasil['label']);
        $this->assertSame([
            ['nama' => 'RED / M', 'tersedia' => true],
            ['nama' => 'RED / L', 'tersedia' => false],
            ['nama' => 'BLUE / M', 'tersedia' => true],
            ['nama' => 'BLUE / L', 'tersedia' => false],
        ], $hasil['opsi']);
    }

    public function test_impor_tanpa_kotak_pilihan_tidak_punya_varian_dan_jumlahnya_dibatasi(): void
    {
        $this->assertSame(['label' => null, 'opsi' => []], (new ImporProdukHtmlService)->baca('<h1>Produk</h1>')['varian']);

        $banyak = '';
        foreach (range(1, 40) as $nomor) {
            $banyak .= $this->tombol("Ukuran {$nomor}");
        }

        $hasil = (new ImporProdukHtmlService)->baca("<h1>Produk</h1><section><h2>Ukuran</h2><div>{$banyak}</div></section>")['varian'];

        $this->assertCount(ProductVariant::MAKS, $hasil['opsi']);
    }
}
