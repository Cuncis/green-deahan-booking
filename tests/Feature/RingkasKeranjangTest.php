<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Popup ringkasan di ikon keranjang header: GET /keranjang/ringkas.
 */
class RingkasKeranjangTest extends TestCase
{
    use RefreshDatabase;

    private const SITUS = 'http://greendeahan.com';

    public function test_keranjang_kosong(): void
    {
        $this->getJson(self::SITUS.'/keranjang/ringkas')
            ->assertOk()
            ->assertExactJson(['items' => [], 'jumlahItem' => 0, 'subtotal' => 0]);
    }

    public function test_ringkasan_berisi_barang_jumlah_dan_subtotal_dari_harga_database(): void
    {
        $bola = Product::factory()->create(['name' => 'Bola Padel', 'slug' => 'bola-padel', 'unit' => 'tabung', 'sell_price' => 95000, 'cost_price' => 75000, 'image_path' => 'https://contoh.test/bola.jpg']);
        $net = Product::factory()->create(['name' => 'Net Padel', 'slug' => 'net-padel', 'unit' => 'set', 'sell_price' => 250000]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $bola->id, 'qty' => 2]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $net->id, 'qty' => 1]);

        $respons = $this->getJson(self::SITUS.'/keranjang/ringkas')->assertOk();

        $respons->assertJsonPath('jumlahItem', 3)->assertJsonPath('subtotal', 440000);
        $respons->assertJsonFragment([
            'id' => $bola->id, 'name' => 'Bola Padel', 'slug' => 'bola-padel', 'imageUrl' => 'https://contoh.test/bola.jpg',
            'unit' => 'tabung', 'qty' => 2, 'price' => 95000, 'subtotal' => 190000,
        ]);
    }

    public function test_hanya_kolom_untuk_popup_yang_dikirim_tanpa_data_privat(): void
    {
        $produk = Product::factory()->create(['cost_price' => 12345, 'supplier_product_url' => 'https://shopee.co.id/rahasia']);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);

        $respons = $this->getJson(self::SITUS.'/keranjang/ringkas')->assertOk();

        $this->assertSame(['id', 'imageUrl', 'name', 'price', 'qty', 'slug', 'subtotal', 'unit'], collect($respons->json('items.0'))->keys()->sort()->values()->all());
        $this->assertStringNotContainsString('12345', $respons->getContent());
        $this->assertStringNotContainsString('rahasia', $respons->getContent());
    }

    public function test_produk_yang_dinonaktifkan_hilang_dari_ringkasan(): void
    {
        $produk = Product::factory()->create();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);
        $produk->update(['is_active' => false]);

        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertExactJson(['items' => [], 'jumlahItem' => 0, 'subtotal' => 0]);
    }

    public function test_ringkasan_tidak_boleh_disimpan_cache_dan_tidak_bocor_antar_pengunjung(): void
    {
        $produk = Product::factory()->create();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);

        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertHeader('Cache-Control', 'no-store, private');

        $this->flushSession();
        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertJsonPath('jumlahItem', 0);
    }

    public function test_ringkasan_tidak_ada_di_domain_tenant(): void
    {
        $this->getJson('http://localhost/keranjang/ringkas')->assertNotFound();
    }

    public function test_ringkasan_tidak_bentrok_dengan_rute_ubah_dan_hapus_jumlah(): void
    {
        $produk = Product::factory()->create();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);

        $this->patch(self::SITUS."/keranjang/{$produk->id}", ['qty' => 4])->assertRedirect();
        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertJsonPath('jumlahItem', 4);
        $this->delete(self::SITUS."/keranjang/{$produk->id}")->assertRedirect();
        $this->getJson(self::SITUS.'/keranjang/ringkas')->assertJsonPath('jumlahItem', 0);
    }
}
