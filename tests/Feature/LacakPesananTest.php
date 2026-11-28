<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Halaman lacak pesanan: kode pesanan + email atau nomor HP mengarah ke
 * halaman status (link token acak).
 */
class LacakPesananTest extends TestCase
{
    use RefreshDatabase;

    private const SITUS = 'http://greendeahan.com';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    private function pesanan(): Order
    {
        return Order::factory()->create(['code' => 'GD-20261127-K7M2QX', 'email' => 'Budi@Contoh.com', 'phone' => '0812-3456-7890']);
    }

    public function test_halaman_lacak_tampil(): void
    {
        $this->get(self::SITUS.'/lacak-pesanan')->assertOk()->assertInertia(fn (Assert $page) => $page->component('toko/LacakPesanan'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function isianCocok(): array
    {
        return [
            'email persis' => ['GD-20261127-K7M2QX', 'Budi@Contoh.com'],
            'email beda huruf besar kecil dan spasi' => ['  gd-20261127-k7m2qx ', ' budi@contoh.com '],
            'HP format 08' => ['GD-20261127-K7M2QX', '081234567890'],
            'HP format +62 dengan tanda' => ['GD-20261127-K7M2QX', '+62 812-3456-7890'],
            'kode tanpa awalan GD-' => ['20261127-K7M2QX', 'budi@contoh.com'],
        ];
    }

    #[DataProvider('isianCocok')]
    public function test_kode_dan_kontak_cocok_diarahkan_ke_halaman_status(string $kode, string $kontak): void
    {
        $order = $this->pesanan();

        $this->post(self::SITUS.'/lacak-pesanan', ['kode' => $kode, 'kontak' => $kontak])
            ->assertRedirect(self::SITUS.$order->pathStatus());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function isianSalah(): array
    {
        return [
            'email lain' => ['GD-20261127-K7M2QX', 'orang@lain.com'],
            'HP lain' => ['GD-20261127-K7M2QX', '081299999999'],
            'HP tidak valid' => ['GD-20261127-K7M2QX', '123'],
            'kode tidak ada' => ['GD-20260101-TIDAKA', 'budi@contoh.com'],
        ];
    }

    #[DataProvider('isianSalah')]
    public function test_kode_atau_kontak_salah_ditolak_dengan_pesan_sama(string $kode, string $kontak): void
    {
        $this->pesanan();

        $this->from(self::SITUS.'/lacak-pesanan')->post(self::SITUS.'/lacak-pesanan', ['kode' => $kode, 'kontak' => $kontak])
            ->assertRedirect(self::SITUS.'/lacak-pesanan')
            ->assertSessionHasErrors(['kode' => 'Pesanan tidak ditemukan. Periksa lagi kode pesanan dan email atau nomor HP yang dipakai saat checkout.'])
            ->assertSessionHasInput('kode')
            ->assertSessionMissing('_old_input.kontak');
    }

    public function test_isian_kosong_ditolak(): void
    {
        $this->post(self::SITUS.'/lacak-pesanan', [])->assertSessionHasErrors(['kode', 'kontak']);
    }

    public function test_dibatasi_10_kali_per_menit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(self::SITUS.'/lacak-pesanan', ['kode' => 'GD-X', 'kontak' => 'a@b.c']);
        }

        $this->post(self::SITUS.'/lacak-pesanan', ['kode' => 'GD-X', 'kontak' => 'a@b.c'])->assertStatus(429);
    }
}
