<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\MayarException;
use App\Mail\PesananTokoDikirim;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\MayarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Admin toko di /superadmin/toko dan MayarService.
 */
class TokoAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    private function pesananDibayar(array $override = []): Order
    {
        $order = Order::factory()->create(array_merge(['status' => OrderStatus::Paid, 'paid_at' => now(), 'subtotal' => 190000, 'shipping_cost' => 15000, 'total' => 205000], $override));
        $order->items()->create(['product_id' => null, 'product_name' => 'Bola Padel', 'unit_price' => 95000, 'unit_cost' => 75000, 'qty' => 2]);

        return $order;
    }

    public function test_hanya_superadmin_yang_bisa_membuka_admin_toko(): void
    {
        $this->get('/superadmin/toko/pesanan')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/superadmin/toko/pesanan')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/superadmin/toko/produk')->assertForbidden();
        $this->actingAs(User::factory()->create())->post('/superadmin/toko/supplier', ['name' => 'X', 'platform' => 'direct'])->assertForbidden();
    }

    public function test_tambah_produk_dengan_foto_dan_data_privat_hanya_di_admin(): void
    {
        Storage::fake('public');
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', [
            'name' => 'Bola Padel Isi 3',
            'slug' => '',
            'category' => 'padel',
            'description' => 'Tabung isi 3.',
            'sell_price' => 95000,
            'cost_price' => 75000,
            'supplier_id' => $supplier->id,
            'supplier_product_url' => 'https://shopee.co.id/produk-supplier',
            'weight_gram' => 250,
            'is_active' => '1',
            'foto' => UploadedFile::fake()->image('bola.jpg'),
        ])->assertRedirect(route('superadmin.toko.produk'));

        $produk = Product::sole();
        $this->assertSame('bola-padel-isi-3', $produk->slug);
        $this->assertNotNull($produk->image_path);

        $this->actingAs($this->superadmin())->get('/superadmin/toko/produk')
            ->assertInertia(fn (Assert $page) => $page
                ->component('superadmin/Toko/Produk/Index')
                ->where('produk.0.hargaModal', 75000)
                ->where('produk.0.margin', 20000)
                ->where('produk.0.linkSupplier', 'https://shopee.co.id/produk-supplier')
            );
    }

    public function test_ubah_dan_hapus_produk_tidak_merusak_pesanan_lama(): void
    {
        $produk = Product::factory()->create(['name' => 'Lama', 'slug' => 'lama']);
        $order = Order::factory()->create();
        $order->items()->create(['product_id' => $produk->id, 'product_name' => 'Lama', 'unit_price' => 100000, 'unit_cost' => 70000, 'qty' => 1]);
        $admin = $this->superadmin();

        $this->actingAs($admin)->put("/superadmin/toko/produk/{$produk->id}", [
            'name' => 'Baru', 'slug' => 'lama', 'category' => 'futsal', 'sell_price' => 120000, 'cost_price' => 80000, 'weight_gram' => 0, 'is_active' => '0',
        ])->assertRedirect(route('superadmin.toko.produk'));
        $this->assertSame(['Baru', 120000, false], [$produk->fresh()->name, $produk->fresh()->sell_price, $produk->fresh()->is_active]);

        $this->actingAs($admin)->delete("/superadmin/toko/produk/{$produk->id}")->assertRedirect();
        $this->assertModelMissing($produk);
        $this->assertSame(['Lama', 100000, null], [$order->items()->first()->product_name, $order->items()->first()->unit_price, $order->items()->first()->product_id]);
    }

    public function test_crud_supplier(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->post('/superadmin/toko/supplier', ['name' => 'Toko Bola', 'platform' => 'shopee', 'shop_url' => 'https://shopee.co.id/tokobola'])->assertSessionHasNoErrors();
        $supplier = Supplier::sole();
        $this->actingAs($admin)->put("/superadmin/toko/supplier/{$supplier->id}", ['name' => 'Toko Bola Baru', 'platform' => 'tokopedia'])->assertSessionHasNoErrors();
        $this->assertSame('tokopedia', $supplier->fresh()->platform);
        $this->actingAs($admin)->post('/superadmin/toko/supplier', ['name' => 'X', 'platform' => 'lazada'])->assertSessionHasErrors('platform');
        $this->actingAs($admin)->delete("/superadmin/toko/supplier/{$supplier->id}");
        $this->assertModelMissing($supplier);
    }

    public function test_daftar_pesanan_default_menampilkan_yang_perlu_dibeli(): void
    {
        $dibayar = $this->pesananDibayar();
        Order::factory()->create(['status' => OrderStatus::PendingPayment]);
        Order::factory()->create(['status' => OrderStatus::Completed]);

        $this->actingAs($this->superadmin())->get('/superadmin/toko/pesanan')
            ->assertInertia(fn (Assert $page) => $page
                ->component('superadmin/Toko/Pesanan/Index')
                ->where('filterStatus', 'paid')
                ->where('pesanan.total', 1)
                ->where('pesanan.data.0.kode', $dibayar->code)
                ->where('pesanan.data.0.untung', 205000 - 15000 - 150000)
            );

        $this->actingAs($this->superadmin())->get('/superadmin/toko/pesanan?status=semua')
            ->assertInertia(fn (Assert $page) => $page->where('filterStatus', 'semua')->where('pesanan.total', 3));
    }

    public function test_alur_status_pesanan_sampai_selesai_dan_email_resi(): void
    {
        Mail::fake();
        $order = $this->pesananDibayar();
        $admin = $this->superadmin();
        $url = "/superadmin/toko/pesanan/{$order->id}/status";

        $this->actingAs($admin)->get("/superadmin/toko/pesanan/{$order->id}")->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/Toko/Pesanan/Show')
            ->where('pesanan.untung', 40000)
            ->where('pesanan.langkahBerikutnya.0.nilai', 'ordered_to_supplier')
        );

        $this->actingAs($admin)->post($url, ['status' => 'completed'])->assertSessionHasErrors('status');
        $this->actingAs($admin)->post($url, ['status' => 'ordered_to_supplier'])->assertSessionHasErrors('supplier_order_ref');
        $this->actingAs($admin)->post($url, ['status' => 'ordered_to_supplier', 'supplier_order_ref' => 'SHP-001'])->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::OrderedToSupplier, $order->fresh()->status);

        $this->actingAs($admin)->post($url, ['status' => 'shipped', 'courier' => 'JNE'])->assertSessionHasErrors('tracking_number');
        Mail::assertNothingQueued();
        $this->actingAs($admin)->post($url, ['status' => 'shipped', 'courier' => 'JNE', 'tracking_number' => 'JNE123'])->assertSessionHasNoErrors();
        Mail::assertQueued(PesananTokoDikirim::class, fn (PesananTokoDikirim $mail) => $mail->hasTo($order->email) && $mail->order->tracking_number === 'JNE123');

        $this->actingAs($admin)->post($url, ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->actingAs($admin)->post($url, ['status' => 'cancelled'])->assertSessionHasErrors('status');
    }

    public function test_dashboard_menampilkan_ringkasan_toko_harian(): void
    {
        $this->pesananDibayar();
        $this->pesananDibayar(['status' => OrderStatus::Shipped]);
        Order::factory()->create(['status' => OrderStatus::PendingPayment]);

        $this->actingAs($this->superadmin())->get('/superadmin')->assertInertia(fn (Assert $page) => $page
            ->where('toko.perluDiproses', 1)
            ->where('toko.hariIni.pesanan', 2)
            ->where('toko.hariIni.omzet', 410000)
            ->where('toko.hariIni.untung', 80000)
            ->has('toko.harian', 7)
        );
    }

    public function test_mayar_service_menolak_server_produksi_saat_mode_sandbox(): void
    {
        Http::fake();
        config(['mayar.env' => 'sandbox', 'mayar.base_url' => 'https://api.mayar.id', 'mayar.api_key' => 'kunci-uji']);

        try {
            app(MayarService::class)->getPayment('pay-1');
            $this->fail('Seharusnya ditolak.');
        } catch (MayarException $galat) {
            $this->assertStringContainsString('produksi', $galat->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_mayar_service_error_tidak_membocorkan_api_key(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 401, 'messages' => 'unauthorized'], 401)]);
        config(['mayar.env' => 'sandbox', 'mayar.base_url' => 'https://api.mayar.club', 'mayar.api_key' => 'kunci-sangat-rahasia']);

        try {
            app(MayarService::class)->getPayment('pay-1');
            $this->fail('Seharusnya gagal.');
        } catch (MayarException $galat) {
            $this->assertStringContainsString('401', $galat->getMessage());
            $this->assertStringNotContainsString('kunci-sangat-rahasia', $galat->getMessage());
        }

        config(['mayar.api_key' => '']);
        $this->expectException(MayarException::class);
        app(MayarService::class)->getPayment('pay-1');
    }
}
