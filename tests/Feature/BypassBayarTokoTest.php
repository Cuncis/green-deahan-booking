<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Mail\PesananTokoDibayar;
use App\Mail\PesananTokoDiterima;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\BypassBayarToko;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * SEMENTARA: mode uji bayar toko (BypassBayarToko). Hapus bersama fiturnya
 * setelah sandbox Mayar bisa dipakai lagi.
 */
class BypassBayarTokoTest extends TestCase
{
    use RefreshDatabase;

    private const SITUS = 'http://greendeahan.com';

    protected function setUp(): void
    {
        parent::setUp();

        config(['mayar.env' => 'sandbox', 'mayar.base_url' => 'https://api.mayar.io', 'mayar.api_key' => 'kunci-uji', 'toko.ongkir_flat' => 15000]);
        Mail::fake();
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    private function isiKeranjangDanCheckout(): TestResponse
    {
        Wilayah::query()->create(['kode' => '31', 'nama' => 'DKI Jakarta', 'level' => Wilayah::levelDari('31'), 'induk' => null]);
        Wilayah::query()->create(['kode' => '31.74', 'nama' => 'Jakarta Selatan', 'level' => Wilayah::levelDari('31.74'), 'induk' => '31']);
        Wilayah::query()->create(['kode' => '31.74.01', 'nama' => 'Tebet', 'level' => Wilayah::levelDari('31.74.01'), 'induk' => '31.74']);
        Wilayah::query()->create(['kode' => '31.74.01.1001', 'nama' => 'Tebet Barat', 'level' => Wilayah::levelDari('31.74.01.1001'), 'induk' => '31.74.01', 'kodepos' => '12810']);

        $produk = Product::factory()->create(['sell_price' => 95000, 'cost_price' => 75000]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'qty' => 1]);

        return $this->post(self::SITUS.'/checkout', [
            'customer_name' => 'Budi Santoso',
            'setuju' => '1',
            'phone' => '0812-3456-7890',
            'email' => 'budi@contoh.com',
            'address' => 'Jl. Melati No. 10, RT 01/RW 02',
            'wilayah_kode' => '31.74.01.1001',
        ]);
    }

    private function pesananMenunggu(): Order
    {
        return Order::factory()->create(['status' => OrderStatus::PendingPayment, 'paid_at' => null]);
    }

    public function test_superadmin_menyalakan_dan_mematikan_mode_uji_dari_halaman_pesanan(): void
    {
        Http::fake();
        $admin = $this->superadmin();

        $this->actingAs($admin)->get('/superadmin/toko/pesanan')
            ->assertInertia(fn (Assert $page) => $page->where('bypassBayar.aktif', false));

        $this->actingAs($admin)->put('/superadmin/toko/bypass-bayar', ['aktif' => true])->assertSessionHas('success');
        $this->assertTrue(app(BypassBayarToko::class)->aktif());

        $this->actingAs($admin)->put('/superadmin/toko/bypass-bayar', ['aktif' => false]);
        $this->assertFalse(app(BypassBayarToko::class)->aktif());
    }

    public function test_bukan_superadmin_tidak_bisa_mengatur_mode_uji(): void
    {
        $this->actingAs(User::factory()->create())->put('/superadmin/toko/bypass-bayar', ['aktif' => true])->assertForbidden();
        $this->assertFalse(app(BypassBayarToko::class)->aktif());
    }

    public function test_mode_aktif_superadmin_checkout_tanpa_mayar_lalu_tandai_dibayar(): void
    {
        app(BypassBayarToko::class)->atur(true);
        Http::fake();
        $this->actingAs($this->superadmin());

        $this->isiKeranjangDanCheckout()->assertRedirect(self::SITUS.Order::sole()->pathStatus());

        $order = Order::sole();
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertNull($order->mayar_payment_id);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'mayar'));

        $this->get(self::SITUS.$order->pathStatus())
            ->assertInertia(fn (Assert $page) => $page->where('bypassBayarUrl', self::SITUS.$order->pathStatus().'/bypass-bayar'));

        $this->post(self::SITUS.$order->pathStatus().'/bypass-bayar')->assertSessionHas('success');

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertStringContainsString(BypassBayarToko::CATATAN, (string) $order->admin_notes);
        Mail::assertQueued(PesananTokoDibayar::class);
        Mail::assertQueued(PesananTokoDiterima::class, fn (PesananTokoDiterima $mail) => $mail->hasTo('budi@contoh.com'));

        $this->get(self::SITUS.$order->pathStatus())->assertInertia(fn (Assert $page) => $page->where('bypassBayarUrl', null));
        $this->post(self::SITUS.$order->pathStatus().'/bypass-bayar')->assertSessionHas('error');
    }

    public function test_pengunjung_biasa_tetap_lewat_mayar_walau_mode_aktif(): void
    {
        app(BypassBayarToko::class)->atur(true);
        Http::fake(['api.mayar.io/hl/v2/payments/create' => Http::response(['statusCode' => 200, 'data' => ['id' => 'pay-1', 'link' => 'https://mayar.test/pl/1']])]);

        $this->isiKeranjangDanCheckout()->assertRedirect('https://mayar.test/pl/1');

        $order = Order::sole();
        $this->get(self::SITUS.$order->pathStatus())->assertInertia(fn (Assert $page) => $page->where('bypassBayarUrl', null));
        $this->post(self::SITUS.$order->pathStatus().'/bypass-bayar')->assertRedirect();
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }

    public function test_redirect_dan_tombol_tetap_di_domain_yang_sedang_dibuka(): void
    {
        app(BypassBayarToko::class)->atur(true);
        Http::fake();
        $this->actingAs($this->superadmin());
        $order = $this->pesananMenunggu();

        $this->get('http://www.greendeahan.com'.$order->pathStatus())
            ->assertInertia(fn (Assert $page) => $page->where('bypassBayarUrl', 'http://www.greendeahan.com'.$order->pathStatus().'/bypass-bayar'));
    }

    public function test_superadmin_tidak_bisa_bypass_saat_mode_mati(): void
    {
        Http::fake();
        $order = $this->pesananMenunggu();

        $this->actingAs($this->superadmin())->post(self::SITUS.$order->pathStatus().'/bypass-bayar')->assertNotFound();
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        Mail::assertNothingQueued();
    }
}
