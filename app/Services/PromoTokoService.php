<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\PromoTokoException;
use App\Models\Order;
use App\Models\PromoCode;

/**
 * Aturan kode promo toko, satu-satunya tempat potongan dihitung: pratinjau di
 * checkout dan pembuatan pesanan memakai method yang sama, jadi angka yang
 * tampil sama dengan yang ditagih. Potongan hanya atas subtotal barang
 * (tidak mengurangi ongkir).
 */
class PromoTokoService
{
    /** Subtotal setelah diskon tidak boleh kurang dari ini, supaya tagihan tidak nol. */
    public const SISA_MINIMAL = 1000;

    public const PESAN_TIDAK_BERLAKU = 'Kode promo tidak ditemukan atau sudah tidak berlaku.';

    /**
     * @return array{promo: PromoCode, diskon: int}
     *
     * @throws PromoTokoException
     */
    public function periksa(string $kode, int $subtotal): array
    {
        $promo = PromoCode::query()->where('code', PromoCode::normalisasi($kode))->first();

        return $this->terapkan($promo, $subtotal);
    }

    /**
     * Sama seperti periksa(), tapi mengunci baris promo supaya dua checkout
     * bersamaan tidak sama-sama mengambil kuota terakhir. Panggil di dalam transaksi.
     *
     * @return array{promo: PromoCode, diskon: int}
     *
     * @throws PromoTokoException
     */
    public function periksaDenganKunci(string $kode, int $subtotal): array
    {
        $promo = PromoCode::query()->where('code', PromoCode::normalisasi($kode))->lockForUpdate()->first();

        return $this->terapkan($promo, $subtotal);
    }

    /**
     * Berapa kali kode ini terpakai: pesanan yang masih hidup (belum batal atau kedaluwarsa).
     */
    public function terpakai(PromoCode $promo): int
    {
        return Order::query()
            ->where('promo_code', $promo->code)
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Expired])
            ->count();
    }

    /**
     * Jumlah pemakaian semua kode sekaligus (satu query), untuk daftar admin.
     *
     * @return array<string, int>
     */
    public function terpakaiSemua(): array
    {
        return Order::query()
            ->whereNotNull('promo_code')
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Expired])
            ->selectRaw('promo_code, count(*) as jumlah')
            ->groupBy('promo_code')
            ->pluck('jumlah', 'promo_code')
            ->map(fn ($jumlah) => (int) $jumlah)
            ->all();
    }

    /**
     * @return array{promo: PromoCode, diskon: int}
     */
    private function terapkan(?PromoCode $promo, int $subtotal): array
    {
        $hariIni = now()->startOfDay();

        // Satu pesan untuk semua sebab "kode ini tidak bisa dipakai sekarang", supaya
        // tebakan kode tidak membocorkan mana yang ada, kedaluwarsa, atau belum mulai.
        if ($promo === null
            || ! $promo->is_active
            || ($promo->starts_on !== null && $hariIni->lt($promo->starts_on))
            || ($promo->ends_on !== null && $hariIni->gt($promo->ends_on))) {
            throw new PromoTokoException(self::PESAN_TIDAK_BERLAKU);
        }

        if ($subtotal < $promo->min_subtotal) {
            throw new PromoTokoException('Kode promo ini berlaku untuk belanja minimal Rp'.number_format($promo->min_subtotal, 0, ',', '.').'.');
        }

        if ($promo->quota !== null && $this->terpakai($promo) >= $promo->quota) {
            throw new PromoTokoException('Kuota kode promo ini sudah habis.');
        }

        $diskon = min($promo->hitungPotongan($subtotal), max(0, $subtotal - self::SISA_MINIMAL));

        if ($diskon <= 0) {
            throw new PromoTokoException('Kode promo ini belum bisa dipakai untuk belanjaanmu.');
        }

        return ['promo' => $promo, 'diskon' => $diskon];
    }
}
