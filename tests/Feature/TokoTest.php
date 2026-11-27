<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Halaman toko pelanggan, keranjang, dan checkout (Mayar dipalsukan dengan Http::fake()).
 */
class TokoTest extends TestCase
{
    use RefreshDatabase;

    private const SITUS = 'http://greendeahan.com';

    protected function setUp(): void
    {
        parent::setUp();

        config(['mayar.env' => 'sandbox', 'mayar.base_url' => 'https://api.mayar.io', 'mayar.api_key' => 'kunci-uji', 'toko.ongkir_flat' => 15000]);
    }

    private function produk(array $override = []): Product
    {
        return Product::factory()->create(array_merge([
            'sell_price' => 95000,
            'cost_price' => 75000,
            'supplier_product_url' => 'https://shopee.co.id/rahasia-supplier',
        ], $override));
    }

    private function fakeMayarBerhasil(): void
    {
        Http::fake([
            'api.mayar.io/hl/v2/payments/create' => Http::response([
                'statusCode' => 200,
                'messages' => 'success',
                'data' => ['id' => 'pay-uuid-123', 'transactionId' => 'trx-1', 'link' => 'https://testingmayar.myr.id/pl/abc123'],
            ]),
        ]);
    }

    /**
     * Wilayah contoh: DKI Jakarta, Jakarta Selatan, Tebet, dan dua kelurahan
     * (satu dengan kode pos, satu tanpa). Aman dipanggil berulang.
     */
    private function siapkanWilayah(): void
    {
        foreach ([
            ['31', 'DKI Jakarta', null],
            ['31.74', 'Kota Administrasi Jakarta Selatan', null],
            ['31.74.01', 'Tebet', null],
            ['31.74.01.1001', 'Tebet Barat', '12810'],
            ['31.74.01.1002', 'Tebet Timur', null],
        ] as [$kode, $nama, $kodepos]) {
            Wilayah::query()->updateOrCreate(['kode' => $kode], ['nama' => $nama, 'level' => Wilayah::levelDari($kode), 'induk' => Wilayah::indukDari($kode), 'kodepos' => $kodepos]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function dataPengiriman(array $override = []): array
    {
        $this->siapkanWilayah();

        return array_merge([
            'customer_name' => 'Budi Santoso',
            'setuju' => '1',
            'phone' => '0812-3456-7890',
            'email' => 'Budi@Contoh.com',
            'address' => 'Jl. Melati No. 10, RT 01/RW 02, Kebayoran',
            'wilayah_kode' => '31.74.01.1001',
        ], $override);
    }

    public function test_checkout_tanpa_centang_persetujuan_ditolak_dan_tidak_membuat_pesanan(): void
    {
        $this->post(self::SITUS.'/keranjang', ['product_id' => $this->produk()->id, 'qty' => 1]);

        $this->post(self::SITUS.'/checkout', $this->dataPengiriman(['setuju' => '']))->assertSessionHasErrors('setuju');
        $this->post(self::SITUS.'/checkout', array_diff_key($this->dataPengiriman(), ['setuju' => true]))->assertSessionHasErrors('setuju');

        $this->assertSame(0, Order::count());
    }

    public function test_halaman_toko_menampilkan_produk_aktif_tanpa_data_privat(): void
    {
        $produk = $this->produk(['name' => 'Bola Padel', 'category' => 'rank-product', 'unit' => 'tabung', 'badge' => 'Terlaris', 'compare_price' => 110000]);
        $this->produk(['name' => 'Produk Disembunyikan', 'is_active' => false]);

        $this->get(self::SITUS.'/toko')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('toko/Index')
                ->where('produk.total', 1)
                ->where('produk.data.0.name', 'Bola Padel')
                ->where('produk.data.0.price', 95000)
                ->where('produk.data.0.unit', 'tabung')
                ->where('produk.data.0.badge', 'Terlaris')
                ->where('produk.data.0.comparePrice', 110000)
                ->missing('produk.data.0.cost_price')
                ->missing('produk.data.0.supplier_product_url')
                ->missing('produk.data.0.supplier_id')
                ->has('kategori', 10)
                ->where('totalProduk', 1)
            )
            ->assertDontSee('rahasia-supplier')
            ->assertDontSee('75000');

        $this->get(self::SITUS.'/toko/'.$produk->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('toko/Produk')->where('produk.slug', $produk->slug)->missing('produk.cost_price'))
            ->assertDontSee('rahasia-supplier');
    }

    public function test_toko_tersembunyi_tapi_tetap_bisa_dibuka_selama_belum_publik(): void
    {
        config(['toko.publik' => false]);
        $produk = $this->produk();

        $this->get(self::SITUS.'/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('tokoPublik', false));
        $this->get(self::SITUS.'/toko')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertInertia(fn (Assert $page) => $page->where('tokoPublik', false)->where('meta.noindex', true));
        $this->get(self::SITUS.'/toko/'.$produk->slug)->assertOk()->assertSee('noindex, nofollow', false);
        $this->get(self::SITUS.'/sitemap.xml')->assertOk()->assertDontSee('/toko');
    }

    public function test_toko_publik_muncul_di_menu_dan_boleh_diindeks(): void
    {
        config(['toko.publik' => true]);

        $this->get(self::SITUS.'/')->assertInertia(fn (Assert $page) => $page->where('tokoPublik', true));
        $this->get(self::SITUS.'/toko')
            ->assertInertia(fn (Assert $page) => $page->where('meta.noindex', false))
            ->assertDontSee('<meta name="robots"', false);
        // Keranjang dan checkout tidak pernah diindeks.
        $this->get(self::SITUS.'/keranjang')->assertSee('noindex, nofollow', false);
    }

    public function test_filter_kategori_dan_produk_nonaktif_404(): void
    {
        $this->produk(['category' => 'ortuseight']);
        $this->produk(['category' => 'rank-product']);
        $nonaktif = $this->produk(['is_active' => false, 'category' => 'ortuseight']);

        $this->get(self::SITUS.'/toko?kategori=rank-product')->assertInertia(fn (Assert $page) => $page
            ->where('filter.kategori', 'rank-product')
            ->where('produk.total', 1)
            ->where('kategori', fn ($daftar) => collect($daftar)->firstWhere('nilai', 'ortuseight')['jumlah'] === 1)
        );
        $this->get(self::SITUS.'/toko?kategori=tidak-ada')->assertInertia(fn (Assert $page) => $page->where('filter.kategori', null)->where('produk.total', 2));
        $this->get(self::SITUS.'/toko/'.$nonaktif->slug)->assertNotFound();
    }

    public function test_cari_urutkan_dan_halaman_produk(): void
    {
        $this->produk(['name' => 'Rumput Sintetis Futsal', 'sell_price' => 185000, 'category' => 'ortuseight']);
        $this->produk(['name' => 'Rumput Sintetis Padel', 'sell_price' => 165000, 'category' => 'ortuseight']);
        $this->produk(['name' => 'Lampu LED', 'sell_price' => 1250000, 'category' => 'saucony']);

        $this->get(self::SITUS.'/toko?q=rumput&urut=murah')->assertInertia(fn (Assert $page) => $page
            ->where('filter.q', 'rumput')
            ->where('filter.urut', 'murah')
            ->where('produk.total', 2)
            ->where('produk.data.0.name', 'Rumput Sintetis Padel')
        );
        $this->get(self::SITUS.'/toko?urut=mahal')->assertInertia(fn (Assert $page) => $page->where('produk.data.0.name', 'Lampu LED'));
        $this->get(self::SITUS.'/toko?urut=ngawur')->assertInertia(fn (Assert $page) => $page->where('filter.urut', 'bawaan'));

        Product::factory()->count(8)->create(['category' => 'rank-product']);
        $this->get(self::SITUS.'/toko')->assertInertia(fn (Assert $page) => $page->where('produk.total', 11)->has('produk.data', 9)->where('produk.last_page', 2));
        $this->get(self::SITUS.'/toko?page=2')->assertInertia(fn (Assert $page) => $page->has('produk.data', 2));
    }

    public function test_harga_coret_hanya_tampil_kalau_lebih_mahal(): void
    {
        $produk = $this->produk(['sell_price' => 100000, 'compare_price' => 90000]);

        $this->get(self::SITUS.'/toko/'.$produk->slug)->assertInertia(fn (Assert $page) => $page->where('produk.comparePrice', null)->where('produk.kategoriUrl', '/toko?kategori='.$produk->category));
    }

    public function test_keranjang_tambah_ubah_hapus_dan_harga_dari_database(): void
    {
        $bola = $this->produk(['sell_price' => 95000]);
        $grip = $this->produk(['sell_price' => 45000]);

        $this->post(self::SITUS.'/keranjang', ['product_id' => $bola->id, 'qty' => 2])->assertSessionHas('success');
        $this->post(self::SITUS.'/keranjang', ['product_id' => $grip->id]);
        $this->patch(self::SITUS."/keranjang/{$grip->id}", ['qty' => 3]);

        $this->get(self::SITUS.'/keranjang')->assertInertia(fn (Assert $page) => $page
            ->component('toko/Keranjang')
            ->where('jumlahKeranjang', 5)
            ->where('keranjang.subtotal', 2 * 95000 + 3 * 45000)
            ->where('keranjang.ongkir', 15000)
            ->where('keranjang.total', 2 * 95000 + 3 * 45000 + 15000)
        );

        // Harga berubah di database: keranjang ikut harga terbaru, bukan harga saat ditambahkan.
        $bola->update(['sell_price' => 100000]);
        $this->delete(self::SITUS."/keranjang/{$grip->id}");

        $this->get(self::SITUS.'/keranjang')->assertInertia(fn (Assert $page) => $page->where('keranjang.subtotal', 200000)->has('keranjang.items', 1));
    }

    public function test_keranjang_menolak_produk_nonaktif_dan_qty_berlebihan(): void
    {
        $nonaktif = $this->produk(['is_active' => false]);
        $produk = $this->produk();

        $this->post(self::SITUS.'/keranjang', ['product_id' => $nonaktif->id])->assertNotFound();
        config(['toko.maks_qty_per_produk' => 20]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'qty' => 999])->assertSessionHasErrors('qty');

        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'qty' => 20]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'qty' => 5]);
        $this->get(self::SITUS.'/keranjang')->assertInertia(fn (Assert $page) => $page->where('keranjang.items.0.qty', 20));
    }

    public function test_produk_yang_dinonaktifkan_hilang_dari_keranjang(): void
    {
        $produk = $this->produk();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);
        $produk->update(['is_active' => false]);

        $this->get(self::SITUS.'/keranjang')->assertInertia(fn (Assert $page) => $page->has('keranjang.items', 0)->where('keranjang.total', 0));
    }

    public function test_checkout_berhasil_membuat_pesanan_dan_mengarahkan_ke_mayar(): void
    {
        $this->fakeMayarBerhasil();
        $produk = $this->produk(['sell_price' => 95000, 'cost_price' => 75000, 'name' => 'Bola Padel']);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'qty' => 2]);

        $this->get(self::SITUS.'/checkout')->assertInertia(fn (Assert $page) => $page->component('toko/Checkout')->where('sandbox', true)->where('keranjang.total', 205000));

        $response = $this->post(self::SITUS.'/checkout', $this->dataPengiriman());

        $response->assertRedirect('https://testingmayar.myr.id/pl/abc123');

        $order = Order::with('items')->sole();
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(190000, $order->subtotal);
        $this->assertSame(15000, $order->shipping_cost);
        $this->assertSame(205000, $order->total);
        $this->assertSame('pay-uuid-123', $order->mayar_payment_id);
        $this->assertSame('https://testingmayar.myr.id/pl/abc123', $order->mayar_payment_link);
        $this->assertSame('budi@contoh.com', $order->email);
        $this->assertSame('081234567890', $order->phone);
        $this->assertSame(
            ['DKI Jakarta', 'Kota Administrasi Jakarta Selatan', 'Tebet', 'Tebet Barat', '12810', '31.74.01.1001'],
            [$order->province, $order->city, $order->district, $order->village, $order->postal_code, $order->wilayah_kode],
        );
        $this->assertMatchesRegularExpression('/^GD-\d{8}-[A-Z2-9]{6}$/', $order->code);
        $this->assertSame(['Bola Padel', 95000, 75000, 2], [$order->items[0]->product_name, $order->items[0]->unit_price, $order->items[0]->unit_cost, $order->items[0]->qty]);

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.mayar.io/hl/v2/payments/create'
            && $request->hasHeader('Authorization', 'Bearer kunci-uji')
            && $request['amount'] === 205000
            && $request['email'] === 'budi@contoh.com'
            && $request['extraData'] === ['tipe' => 'toko', 'kode' => $order->code]
            && $request['redirectUrl'] === self::SITUS.'/pesanan/'.$order->code);

        $this->get(self::SITUS.'/keranjang')->assertInertia(fn (Assert $page) => $page->where('jumlahKeranjang', 0));
    }

    public function test_harga_dari_klien_diabaikan_saat_checkout(): void
    {
        $this->fakeMayarBerhasil();
        $produk = $this->produk(['sell_price' => 95000]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'qty' => 1, 'price' => 1, 'sell_price' => 1]);

        $this->post(self::SITUS.'/checkout', $this->dataPengiriman([
            'total' => 1000,
            'subtotal' => 1000,
            'shipping_cost' => 0,
            'items' => [['product_id' => $produk->id, 'qty' => 1, 'unit_price' => 1]],
        ]));

        $order = Order::with('items')->sole();
        $this->assertSame(110000, $order->total);
        $this->assertSame(95000, $order->items[0]->unit_price);
        Http::assertSent(fn (HttpRequest $request) => $request['amount'] === 110000);
    }

    public function test_checkout_gagal_di_mayar_membatalkan_pesanan_dan_keranjang_tetap(): void
    {
        Http::fake(['api.mayar.io/*' => Http::response(['statusCode' => 400, 'messages' => 'invalid amount'], 400)]);
        $produk = $this->produk();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);

        $this->from(self::SITUS.'/checkout')->post(self::SITUS.'/checkout', $this->dataPengiriman())
            ->assertRedirect(self::SITUS.'/checkout')
            ->assertSessionHas('error');

        $order = Order::sole();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertStringContainsString('invalid amount', $order->admin_notes);
        $this->assertStringNotContainsString('kunci-uji', $order->admin_notes);
        $this->get(self::SITUS.'/keranjang')->assertInertia(fn (Assert $page) => $page->where('jumlahKeranjang', 1));
    }

    public function test_checkout_validasi_dan_keranjang_kosong(): void
    {
        Http::fake();
        $this->get(self::SITUS.'/checkout')->assertRedirect(self::SITUS.'/keranjang');
        $this->post(self::SITUS.'/checkout', $this->dataPengiriman())->assertRedirect(self::SITUS.'/keranjang');

        $produk = $this->produk();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);

        $this->post(self::SITUS.'/checkout', $this->dataPengiriman(['phone' => '123', 'email' => 'bukan-email', 'wilayah_kode' => 'salah', 'address' => 'pendek']))
            ->assertSessionHasErrors(['phone', 'email', 'wilayah_kode', 'address']);

        $this->assertSame(0, Order::count());
        Http::assertNothingSent();
    }

    public function test_checkout_dibatasi_5_kali_per_menit(): void
    {
        Http::fake();
        $produk = $this->produk();
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(self::SITUS.'/checkout', ['customer_name' => '']);
        }

        $this->post(self::SITUS.'/checkout', ['customer_name' => ''])->assertStatus(429);
    }

    public function test_halaman_status_pesanan(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Shipped, 'total' => 110000]);
        $order->shipments()->create(['courier' => 'JNE', 'tracking_number' => 'JNE123']);
        $order->items()->create(['product_id' => null, 'product_name' => 'Bola Futsal', 'unit_price' => 95000, 'unit_cost' => 70000, 'qty' => 1]);

        $this->get(self::SITUS.'/pesanan/'.$order->code)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('toko/Pesanan')
                ->where('pesanan.status', 'shipped')
                ->where('pesanan.pengiriman.0.resi', 'JNE123')->where('pesanan.pengiriman.0.kurir', 'JNE')
                ->where('pesanan.linkBayar', null)
                ->missing('pesanan.items.0.modal')
                ->has('langkah', 5)
            )
            ->assertDontSee('70000');

        $this->get(self::SITUS.'/pesanan/GD-20260101-TIDAKA')->assertNotFound();
    }

    public function test_pesanan_kedaluwarsa_setelah_24_jam(): void
    {
        $lama = Order::factory()->create(['created_at' => now()->subHours(25)]);
        $baru = Order::factory()->create(['created_at' => now()->subHours(2)]);
        $dibayar = Order::factory()->create(['created_at' => now()->subHours(30), 'status' => OrderStatus::Paid]);

        $this->artisan('toko:kedaluwarsakan-pesanan')->assertSuccessful();

        $this->assertSame(OrderStatus::Expired, $lama->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $baru->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $dibayar->fresh()->status);
    }
}
