<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
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

        config(['mayar.env' => 'sandbox', 'mayar.base_url' => 'https://api.mayar.club', 'mayar.api_key' => 'kunci-uji', 'toko.ongkir_flat' => 15000]);
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
            'api.mayar.club/hl/v2/payments/create' => Http::response([
                'statusCode' => 200,
                'messages' => 'success',
                'data' => ['id' => 'pay-uuid-123', 'transactionId' => 'trx-1', 'link' => 'https://testingmayar.myr.id/pl/abc123'],
            ]),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function dataPengiriman(array $override = []): array
    {
        return array_merge([
            'customer_name' => 'Budi Santoso',
            'phone' => '0812-3456-7890',
            'email' => 'Budi@Contoh.com',
            'address' => 'Jl. Melati No. 10, RT 01/RW 02, Kebayoran',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'postal_code' => '12345',
        ], $override);
    }

    public function test_halaman_toko_menampilkan_produk_aktif_tanpa_data_privat(): void
    {
        $produk = $this->produk(['name' => 'Bola Padel', 'category' => 'padel']);
        $this->produk(['name' => 'Produk Disembunyikan', 'is_active' => false]);

        $this->get(self::SITUS.'/toko')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('toko/Index')
                ->has('produk', 1)
                ->where('produk.0.name', 'Bola Padel')
                ->where('produk.0.price', 95000)
                ->missing('produk.0.cost_price')
                ->missing('produk.0.supplier_product_url')
                ->missing('produk.0.supplier_id')
                ->has('kategori', 4)
            )
            ->assertDontSee('rahasia-supplier')
            ->assertDontSee('75000');

        $this->get(self::SITUS.'/toko/'.$produk->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('toko/Produk')->where('produk.slug', $produk->slug)->missing('produk.cost_price'))
            ->assertDontSee('rahasia-supplier');
    }

    public function test_filter_kategori_dan_produk_nonaktif_404(): void
    {
        $this->produk(['category' => 'padel']);
        $this->produk(['category' => 'futsal']);
        $nonaktif = $this->produk(['is_active' => false]);

        $this->get(self::SITUS.'/toko?kategori=futsal')->assertInertia(fn (Assert $page) => $page->where('kategoriAktif', 'futsal')->has('produk', 1));
        $this->get(self::SITUS.'/toko?kategori=tidak-ada')->assertInertia(fn (Assert $page) => $page->where('kategoriAktif', null)->has('produk', 2));
        $this->get(self::SITUS.'/toko/'.$nonaktif->slug)->assertNotFound();
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
        $this->assertMatchesRegularExpression('/^GD-\d{8}-[A-Z2-9]{6}$/', $order->code);
        $this->assertSame(['Bola Padel', 95000, 75000, 2], [$order->items[0]->product_name, $order->items[0]->unit_price, $order->items[0]->unit_cost, $order->items[0]->qty]);

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.mayar.club/hl/v2/payments/create'
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
        Http::fake(['api.mayar.club/*' => Http::response(['statusCode' => 400, 'messages' => 'invalid amount'], 400)]);
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

        $this->post(self::SITUS.'/checkout', $this->dataPengiriman(['phone' => '123', 'email' => 'bukan-email', 'postal_code' => '12', 'address' => 'pendek']))
            ->assertSessionHasErrors(['phone', 'email', 'postal_code', 'address']);

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
        $order = Order::factory()->create(['status' => OrderStatus::Shipped, 'courier' => 'JNE', 'tracking_number' => 'JNE123', 'total' => 110000]);
        $order->items()->create(['product_id' => null, 'product_name' => 'Bola Futsal', 'unit_price' => 95000, 'unit_cost' => 70000, 'qty' => 1]);

        $this->get(self::SITUS.'/pesanan/'.$order->code)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('toko/Pesanan')
                ->where('pesanan.status', 'shipped')
                ->where('pesanan.resi', 'JNE123')
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
