<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\KeranjangService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Kartu produk di /toko: tombol "Tambah ke Keranjang" berubah jadi penghitung
 * jumlah, jadi halaman harus tahu jumlah tiap produk di keranjang.
 */
class KartuKeranjangTest extends TestCase
{
    use RefreshDatabase;

    private const SITUS = 'http://greendeahan.com';

    public function test_keranjang_kosong_tidak_punya_jumlah_per_produk(): void
    {
        Product::factory()->create();

        $this->get(self::SITUS.'/toko')->assertInertia(fn (Assert $page) => $page
            ->where('qtyKeranjang', [])
            ->where('maksQtyProduk', (int) config('toko.maks_qty_per_produk'))
        );
    }

    public function test_jumlah_per_produk_mengikuti_tambah_ubah_dan_hapus(): void
    {
        $satu = Product::factory()->create();
        $dua = Product::factory()->create();

        $this->post(self::SITUS.'/keranjang', ['product_id' => $satu->id, 'qty' => 1]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $dua->id, 'qty' => 2]);
        $this->get(self::SITUS.'/toko')->assertInertia(fn (Assert $page) => $page->where('qtyKeranjang', [(string) $satu->id => 1, (string) $dua->id => 2]));

        $this->patch(self::SITUS."/keranjang/{$satu->id}", ['qty' => 5])->assertRedirect();
        $this->get(self::SITUS.'/toko')->assertInertia(fn (Assert $page) => $page->where("qtyKeranjang.{$satu->id}", 5)->where("qtyKeranjang.{$dua->id}", 2));

        $this->delete(self::SITUS."/keranjang/{$satu->id}")->assertRedirect();
        $this->get(self::SITUS.'/toko')->assertInertia(fn (Assert $page) => $page->where('qtyKeranjang', [(string) $dua->id => 2]));
    }

    public function test_jumlah_tidak_bisa_melebihi_batas_per_produk(): void
    {
        $produk = Product::factory()->create();
        $maks = (int) config('toko.maks_qty_per_produk');
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);

        $this->patch(self::SITUS."/keranjang/{$produk->id}", ['qty' => $maks + 1])->assertSessionHasErrors('qty');
        $this->patch(self::SITUS."/keranjang/{$produk->id}", ['qty' => 0])->assertSessionHasErrors('qty');
        $this->get(self::SITUS.'/toko')->assertInertia(fn (Assert $page) => $page->where("qtyKeranjang.{$produk->id}", 1));
    }

    public function test_jumlah_per_produk_hanya_berisi_angka_bulat(): void
    {
        $produk = Product::factory()->create();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'qty' => 3]);

        $this->assertSame([$produk->id => 3], app(KeranjangService::class)->jumlahPerProduk());
    }

    public function test_jumlah_keranjang_satu_pengunjung_tidak_bocor_ke_pengunjung_lain(): void
    {
        $produk = Product::factory()->create();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);

        $this->flushSession();
        $this->get(self::SITUS.'/toko')->assertInertia(fn (Assert $page) => $page->where('qtyKeranjang', []));
    }
}
