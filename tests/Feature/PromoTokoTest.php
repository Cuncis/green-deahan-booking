<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kode promo toko: pratinjau dan pembuatan pesanan di checkout, aturan
 * (aktif, tanggal, minimal belanja, kuota), dan pengelolaannya di superadmin.
 */
class PromoTokoTest extends TestCase
{
    use RefreshDatabase;

    private const SITUS = 'http://greendeahan.com';

    private int $urutanPembayaran = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mayar.api_key' => 'kunci-uji', 'mayar.env' => 'sandbox', 'toko.ongkir_flat' => 15000]);

        foreach ([['31', 'DKI Jakarta', null], ['31.74', 'Kota Administrasi Jakarta Selatan', null], ['31.74.01', 'Tebet', null], ['31.74.01.1001', 'Tebet Barat', '12810']] as [$kode, $nama, $kodepos]) {
            Wilayah::create(['kode' => $kode, 'nama' => $nama, 'level' => Wilayah::levelDari($kode), 'induk' => Wilayah::indukDari($kode), 'kodepos' => $kodepos]);
        }
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    /**
     * Keranjang berisi 2 x Rp100.000 = subtotal Rp200.000.
     */
    private function isiKeranjang(int $harga = 100000, int $qty = 2): void
    {
        Http::fake(['api.mayar.io/hl/v2/payments/create' => fn () => Http::response(['statusCode' => 200, 'data' => ['id' => 'pay-'.++$this->urutanPembayaran, 'transactionId' => 't-1', 'link' => 'https://testingmayar.myr.id/pl/abc']])]);
        $produk = Product::factory()->create(['sell_price' => $harga]);
        $this->post(self::SITUS.'/keranjang', ['product_id' => $produk->id, 'qty' => $qty]);
    }

    /**
     * @return array<string, string>
     */
    private function data(array $override = []): array
    {
        return array_merge([
            'customer_name' => 'Budi Santoso',
            'setuju' => '1',
            'phone' => '081234567890',
            'email' => 'budi@contoh.com',
            'address' => 'Jl. Melati No. 10, RT 01/RW 02',
            'wilayah_kode' => '31.74.01.1001',
        ], $override);
    }

    public function test_pratinjau_menghitung_diskon_persen_dengan_batas_maksimal(): void
    {
        PromoCode::factory()->create(['code' => 'HEMAT10', 'value' => 10, 'max_discount' => 15000]);
        $this->isiKeranjang();

        $this->getJson(self::SITUS.'/checkout/promo?kode=hemat10')
            ->assertOk()
            ->assertJsonPath('kode', 'HEMAT10')
            ->assertJsonPath('diskon', 15000)
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_pratinjau_diskon_nominal_dan_kode_diabaikan_huruf_dan_spasinya(): void
    {
        PromoCode::factory()->nominal(25000)->create(['code' => 'POTONG25']);
        $this->isiKeranjang();

        $this->getJson(self::SITUS.'/checkout/promo?kode='.rawurlencode(' potong 25 '))->assertOk()->assertJsonPath('diskon', 25000);
    }

    public function test_diskon_tidak_menyisakan_tagihan_nol(): void
    {
        PromoCode::factory()->nominal(900000)->create(['code' => 'BESAR']);
        $this->isiKeranjang();

        $this->getJson(self::SITUS.'/checkout/promo?kode=BESAR')->assertOk()->assertJsonPath('diskon', 199000);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function promoTidakValid(): array
    {
        return [
            'nonaktif' => [['is_active' => false], 'tidak ditemukan atau sudah tidak berlaku'],
            'belum mulai' => [['starts_on' => '2099-01-01'], 'tidak ditemukan atau sudah tidak berlaku'],
            'sudah berakhir' => [['ends_on' => '2020-01-01'], 'tidak ditemukan atau sudah tidak berlaku'],
            'di bawah minimal belanja' => [['min_subtotal' => 500000], 'minimal Rp500.000'],
        ];
    }

    #[DataProvider('promoTidakValid')]
    public function test_pratinjau_menolak_promo_yang_tidak_memenuhi_syarat(array $atribut, string $pesan): void
    {
        PromoCode::factory()->create(['code' => 'UJI'] + $atribut);
        $this->isiKeranjang();

        $this->getJson(self::SITUS.'/checkout/promo?kode=UJI')->assertStatus(422)->assertJsonPath('message', fn (string $teks) => str_contains($teks, $pesan));
    }

    public function test_kode_tidak_dikenal_dan_kode_kosong_ditolak(): void
    {
        $this->isiKeranjang();

        $this->getJson(self::SITUS.'/checkout/promo?kode=NGAWUR')->assertStatus(422);
        $this->getJson(self::SITUS.'/checkout/promo')->assertStatus(422)->assertJsonPath('message', 'Isi kode promo dulu.');
    }

    public function test_checkout_memotong_subtotal_dan_menagih_total_baru_ke_mayar(): void
    {
        PromoCode::factory()->create(['code' => 'HEMAT10', 'value' => 10]);
        $this->isiKeranjang();

        $this->post(self::SITUS.'/checkout', $this->data(['promo_code' => 'hemat10']))->assertRedirect('https://testingmayar.myr.id/pl/abc');

        $order = Order::firstOrFail();
        $this->assertSame(200000, $order->subtotal);
        $this->assertSame(20000, $order->discount);
        $this->assertSame('HEMAT10', $order->promo_code);
        $this->assertSame(15000, $order->shipping_cost);
        $this->assertSame(195000, $order->total);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/payments/create') && $request['amount'] === 195000);
    }

    public function test_diskon_dihitung_ulang_di_server_bukan_dari_browser(): void
    {
        PromoCode::factory()->create(['code' => 'HEMAT10', 'value' => 10]);
        $this->isiKeranjang();

        $this->post(self::SITUS.'/checkout', $this->data(['promo_code' => 'HEMAT10', 'discount' => 150000, 'total' => 1000]));

        $this->assertSame(20000, Order::firstOrFail()->discount);
        $this->assertSame(195000, Order::firstOrFail()->total);
    }

    public function test_checkout_tanpa_kode_promo_tidak_berubah(): void
    {
        $this->isiKeranjang();

        $this->post(self::SITUS.'/checkout', $this->data());

        $order = Order::firstOrFail();
        $this->assertSame(0, $order->discount);
        $this->assertNull($order->promo_code);
        $this->assertSame(215000, $order->total);
    }

    public function test_checkout_dengan_kode_tidak_valid_menolak_dan_tidak_membuat_pesanan(): void
    {
        PromoCode::factory()->nonaktif()->create(['code' => 'MATI']);
        $this->isiKeranjang();

        $this->post(self::SITUS.'/checkout', $this->data(['promo_code' => 'MATI']))->assertSessionHasErrors('promo_code');

        $this->assertSame(0, Order::count());
    }

    public function test_kuota_dihitung_dari_pesanan_yang_masih_hidup(): void
    {
        $promo = PromoCode::factory()->create(['code' => 'TERBATAS', 'quota' => 1]);
        $this->isiKeranjang();

        $this->post(self::SITUS.'/checkout', $this->data(['promo_code' => 'TERBATAS']))->assertRedirect();
        $this->assertSame(1, Order::where('promo_code', 'TERBATAS')->count());

        // Checkout yang berhasil mengosongkan keranjang, jadi isi lagi untuk pembeli berikutnya.
        $this->isiKeranjang();
        $this->post(self::SITUS.'/checkout', $this->data(['promo_code' => 'TERBATAS']))->assertSessionHasErrors('promo_code');
        $this->assertSame(1, Order::count());

        Order::firstOrFail()->update(['status' => OrderStatus::Cancelled]);
        $this->post(self::SITUS.'/checkout', $this->data(['promo_code' => 'TERBATAS']))->assertRedirect();
        $this->assertSame(2, Order::count());
        $this->assertSame($promo->code, Order::latest('id')->firstOrFail()->promo_code);
    }

    public function test_halaman_checkout_dan_pesanan_menampilkan_data_promo(): void
    {
        PromoCode::factory()->create(['code' => 'HEMAT10', 'value' => 10]);
        $this->isiKeranjang();
        $this->get(self::SITUS.'/checkout')->assertInertia(fn (Assert $page) => $page->where('promoUrl', url('/checkout/promo')));

        $this->post(self::SITUS.'/checkout', $this->data(['promo_code' => 'HEMAT10']));
        $order = Order::firstOrFail();

        $this->get(self::SITUS.'/pesanan/'.$order->code)->assertInertia(fn (Assert $page) => $page->where('pesanan.diskon', 20000)->where('pesanan.kodePromo', 'HEMAT10'));
    }

    public function test_untung_pesanan_memakai_total_setelah_diskon(): void
    {
        $order = Order::factory()->create(['subtotal' => 200000, 'discount' => 20000, 'promo_code' => 'HEMAT10', 'shipping_cost' => 15000, 'total' => 195000]);
        $order->items()->create(['product_id' => null, 'product_name' => 'Bola', 'unit_price' => 100000, 'unit_cost' => 75000, 'qty' => 2]);

        $this->assertSame(195000 - 15000 - 150000, $order->untung());
    }

    public function test_superadmin_melihat_dan_mengelola_kode_promo(): void
    {
        $admin = $this->superadmin();
        PromoCode::factory()->create(['code' => 'ADA']);

        $this->actingAs($admin)->get('/superadmin/toko/promo')->assertOk()->assertInertia(fn (Assert $page) => $page->component('superadmin/Toko/Promo')->has('promo', 1)->where('promo.0.code', 'ADA'));

        $this->actingAs($admin)->post('/superadmin/toko/promo', [
            'code' => ' hemat 15 ', 'type' => 'percent', 'value' => '15', 'max_discount' => '40000', 'min_subtotal' => '100000', 'quota' => '20',
            'starts_on' => '2026-12-01', 'ends_on' => '2026-12-31', 'is_active' => true,
        ])->assertSessionHasNoErrors();

        $promo = PromoCode::where('code', 'HEMAT15')->firstOrFail();
        $this->assertSame(40000, $promo->max_discount);
        $this->assertSame(20, $promo->quota);

        $this->actingAs($admin)->put("/superadmin/toko/promo/{$promo->id}", ['code' => 'HEMAT15', 'type' => 'fixed', 'value' => '30000', 'max_discount' => '40000', 'is_active' => false])->assertSessionHasNoErrors();
        $promo->refresh();
        $this->assertSame('fixed', $promo->type);
        $this->assertNull($promo->max_discount);
        $this->assertFalse($promo->is_active);

        $this->actingAs($admin)->delete("/superadmin/toko/promo/{$promo->id}")->assertRedirect();
        $this->assertDatabaseMissing('promo_codes', ['id' => $promo->id]);
    }

    public function test_validasi_form_promo(): void
    {
        $admin = $this->superadmin();
        PromoCode::factory()->create(['code' => 'KEMBAR']);

        $this->actingAs($admin)->post('/superadmin/toko/promo', ['code' => 'KEMBAR', 'type' => 'percent', 'value' => 150, 'is_active' => true])->assertSessionHasErrors(['code', 'value']);
        $this->actingAs($admin)->post('/superadmin/toko/promo', ['code' => 'AB', 'type' => 'percent', 'value' => 10, 'is_active' => true])->assertSessionHasErrors('code');
        $this->actingAs($admin)->post('/superadmin/toko/promo', ['code' => 'BAGUS', 'type' => 'percent', 'value' => 10, 'starts_on' => '2026-12-31', 'ends_on' => '2026-12-01', 'is_active' => true])->assertSessionHasErrors('ends_on');
    }

    public function test_bukan_superadmin_tidak_bisa_mengakses_halaman_promo(): void
    {
        $this->get('/superadmin/toko/promo')->assertRedirect();
        $this->actingAs(User::factory()->create())->get('/superadmin/toko/promo')->assertForbidden();
        $this->actingAs(User::factory()->create())->post('/superadmin/toko/promo', ['code' => 'X', 'type' => 'percent', 'value' => 10, 'is_active' => true])->assertForbidden();
        $this->assertSame(0, PromoCode::count());
    }

    public function test_promo_yang_berakhir_hari_ini_masih_berlaku_dan_pesan_kode_tidak_membedakan_sebabnya(): void
    {
        PromoCode::factory()->create(['code' => 'HARIINI', 'ends_on' => now()->toDateString()]);
        PromoCode::factory()->create(['code' => 'KEMARIN', 'ends_on' => now()->subDay()->toDateString()]);
        $this->isiKeranjang();

        $this->getJson(self::SITUS.'/checkout/promo?kode=HARIINI')->assertOk();

        $pesanKemarin = $this->getJson(self::SITUS.'/checkout/promo?kode=KEMARIN')->assertStatus(422)->json('message');
        $pesanAsing = $this->getJson(self::SITUS.'/checkout/promo?kode=TAKADA')->assertStatus(422)->json('message');
        $this->assertSame($pesanAsing, $pesanKemarin);
    }

    public function test_pratinjau_dengan_keranjang_kosong_ditolak(): void
    {
        PromoCode::factory()->create(['code' => 'ADA', 'min_subtotal' => 500000]);

        $this->getJson(self::SITUS.'/checkout/promo?kode=ADA')->assertStatus(422)->assertJsonPath('message', 'Keranjang kosong.');
    }

    public function test_pratinjau_dibatasi_sepuluh_percobaan_per_menit(): void
    {
        $this->isiKeranjang();

        foreach (range(1, 10) as $ke) {
            $this->getJson(self::SITUS.'/checkout/promo?kode=TAKADA')->assertStatus(422);
        }

        $this->getJson(self::SITUS.'/checkout/promo?kode=TAKADA')->assertStatus(429);
    }

    public function test_diskon_persen_dibulatkan_ke_bawah_dan_seratus_persen_menyisakan_batas_minimal(): void
    {
        PromoCode::factory()->create(['code' => 'TIGA', 'value' => 3]);
        PromoCode::factory()->create(['code' => 'PENUH', 'value' => 100]);
        $this->isiKeranjang(33333, 1);

        $this->getJson(self::SITUS.'/checkout/promo?kode=TIGA')->assertOk()->assertJsonPath('diskon', 999);
        $this->getJson(self::SITUS.'/checkout/promo?kode=PENUH')->assertOk()->assertJsonPath('diskon', 33333 - 1000);
    }

    public function test_kode_tidak_bisa_diganti_saat_edit_supaya_kuota_tidak_terisi_ulang(): void
    {
        $admin = $this->superadmin();
        $promo = PromoCode::factory()->create(['code' => 'ASLI', 'quota' => 5]);
        Order::factory()->create(['promo_code' => 'ASLI', 'discount' => 1000]);

        $this->actingAs($admin)->put("/superadmin/toko/promo/{$promo->id}", ['code' => 'GANTI', 'type' => 'percent', 'value' => 20, 'quota' => 5, 'is_active' => true])->assertSessionHasNoErrors();

        $promo->refresh();
        $this->assertSame('ASLI', $promo->code);
        $this->assertSame(20, $promo->value);
        $this->actingAs($admin)->get('/superadmin/toko/promo')->assertInertia(fn (Assert $page) => $page->where('promo.0.terpakai', 1));
    }

    public function test_daftar_admin_menghitung_pemakaian_hanya_dari_pesanan_yang_masih_hidup(): void
    {
        PromoCode::factory()->create(['code' => 'RAMAI']);
        Order::factory()->create(['promo_code' => 'RAMAI', 'status' => OrderStatus::Paid]);
        Order::factory()->create(['promo_code' => 'RAMAI', 'status' => OrderStatus::PendingPayment]);
        Order::factory()->create(['promo_code' => 'RAMAI', 'status' => OrderStatus::Cancelled]);
        Order::factory()->create(['promo_code' => 'RAMAI', 'status' => OrderStatus::Expired]);
        Order::factory()->create(['promo_code' => null]);

        $this->actingAs($this->superadmin())->get('/superadmin/toko/promo')->assertInertia(fn (Assert $page) => $page->where('promo.0.terpakai', 2));
    }

    public function test_bukan_superadmin_tidak_bisa_mengubah_atau_menghapus_promo(): void
    {
        $promo = PromoCode::factory()->create(['code' => 'AMAN']);
        $biasa = User::factory()->create();

        $this->actingAs($biasa)->put("/superadmin/toko/promo/{$promo->id}", ['type' => 'percent', 'value' => 99, 'is_active' => true])->assertForbidden();
        $this->actingAs($biasa)->delete("/superadmin/toko/promo/{$promo->id}")->assertForbidden();

        $this->assertSame(10, $promo->fresh()->value);
    }
}
