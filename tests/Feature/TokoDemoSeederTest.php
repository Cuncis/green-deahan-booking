<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Supplier;
use Database\Seeders\TokoDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Data contoh toko untuk melihat tampilan: menghapus data toko lama dan
 * mengisi pesanan di setiap status.
 */
class TokoDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_menghapus_data_lama_dan_membuat_data_contoh_lengkap(): void
    {
        Storage::fake('public');
        $lama = Product::factory()->create(['name' => 'Produk Lama']);
        Order::factory()->create();

        $this->seed(TokoDemoSeeder::class);

        $this->assertDatabaseMissing('products', ['id' => $lama->id]);
        $this->assertSame(3, Supplier::count());
        $this->assertSame(13, Product::count());
        $this->assertSame(1, Product::where('is_active', false)->count());
        $this->assertSame(10, Order::count());
        $this->assertEqualsCanonicalizing(
            ['pending_payment', 'paid', 'ordered_to_supplier', 'shipped', 'completed', 'cancelled', 'expired'],
            Order::pluck('status')->map(fn (OrderStatus $status) => $status->value)->unique()->values()->all(),
        );
    }

    public function test_data_contoh_memuat_kasus_yang_perlu_dicek_tampilannya(): void
    {
        Storage::fake('public');

        $this->seed(TokoDemoSeeder::class);

        $this->assertSame(1, Order::menungguRefund()->count());
        $this->assertSame(1, Order::whereNotNull('refunded_at')->count());
        $this->assertSame(1, Order::where('courier_code', 'darurat')->count());
        $this->assertSame(2, Order::where('status', OrderStatus::Shipped)->count());
        $this->assertTrue(Order::whereHas('shipments', null, '>=', 2)->exists());
        $this->assertGreaterThan(0, Order::whereHas('messages')->count());

        foreach (Order::with('items')->get() as $order) {
            $this->assertSame($order->subtotal, (int) $order->items->sum(fn ($item) => $item->unit_price * $item->qty));
            $this->assertSame($order->subtotal + $order->shipping_cost, $order->total);
        }
    }

    public function test_produk_contoh_punya_foto_placeholder_yang_tersimpan_dan_cover(): void
    {
        Storage::fake('public');

        $this->seed(TokoDemoSeeder::class);

        $produk = Product::where('slug', 'bola-padel-premium-tabung-isi-3')->firstOrFail();
        $this->assertCount(3, $produk->images);
        $this->assertSame($produk->images->first()->path, $produk->image_path);
        $this->assertStringStartsWith('/storage/produk/demo-bola-padel-', $produk->image_path);
        Storage::disk('public')->assertExists('produk/demo-bola-padel-1.svg');
        $this->assertGreaterThanOrEqual(4, Product::whereNotNull('specs')->get()->filter(fn (Product $item) => $item->specs !== [])->count());
        $this->assertSame([], Product::where('is_active', true)->get()->filter(fn (Product $item) => $item->image_path === null)->all());
    }

    public function test_produk_contoh_punya_varian_dan_pesanan_memakai_harga_varian(): void
    {
        Storage::fake('public');

        $this->seed(TokoDemoSeeder::class);

        $deker = Product::where('slug', 'deker-futsal-pelindung-tulang-kering')->firstOrFail();
        $this->assertSame('Ukuran', $deker->variant_label);
        $this->assertSame(['S', 'M', 'L'], $deker->variants->pluck('name')->all());
        $this->assertSame(1, Product::firstWhere('slug', 'kaos-kaki-bola-anti-selip')->variants()->where('is_available', false)->count());

        $baris = OrderItem::where('product_name', $deker->name)->sole();
        $this->assertSame(['L', 79000, 52000], [$baris->variant_name, $baris->unit_price, $baris->unit_cost]);
        $this->assertSame($baris->variant_id, $deker->variants->firstWhere('name', 'L')->id);
    }

    public function test_seeder_menolak_jalan_di_production(): void
    {
        Storage::fake('public');
        $lama = Product::factory()->create();
        $this->app['env'] = 'production';

        (new TokoDemoSeeder)->run();

        $this->assertDatabaseHas('products', ['id' => $lama->id]);
        $this->assertSame(1, Product::count());
    }
}
