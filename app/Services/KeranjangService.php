<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Keranjang toko di session: hanya menyimpan id produk dan jumlah. Harga
 * SELALU dibaca ulang dari database, tidak pernah disimpan atau diterima
 * dari browser.
 */
class KeranjangService
{
    private const KUNCI = 'toko.keranjang';

    /**
     * @return array<int, int> product_id => qty
     */
    private function mentah(): array
    {
        $isi = session(self::KUNCI, []);

        return is_array($isi) ? $isi : [];
    }

    private function simpan(array $isi): void
    {
        session([self::KUNCI => array_filter($isi, fn (int $qty) => $qty > 0)]);
    }

    private function batasQty(int $qty): int
    {
        return max(0, min($qty, (int) config('toko.maks_qty_per_produk')));
    }

    public function tambah(Product $product, int $qty): void
    {
        $isi = $this->mentah();
        $isi[$product->id] = $this->batasQty(($isi[$product->id] ?? 0) + $qty);
        $this->simpan($isi);
    }

    public function ubah(int $productId, int $qty): void
    {
        $isi = $this->mentah();

        if (array_key_exists($productId, $isi)) {
            $isi[$productId] = $this->batasQty($qty);
            $this->simpan($isi);
        }
    }

    public function hapus(int $productId): void
    {
        $isi = $this->mentah();
        unset($isi[$productId]);
        $this->simpan($isi);
    }

    public function kosongkan(): void
    {
        session()->forget(self::KUNCI);
    }

    public function jumlahItem(): int
    {
        return array_sum($this->mentah());
    }

    /**
     * Isi keranjang dengan produk aktif saja; produk yang sudah dihapus atau
     * dinonaktifkan dibuang dari keranjang.
     *
     * @return Collection<int, array{product: Product, qty: int}>
     */
    public function isi(): Collection
    {
        $isi = $this->mentah();

        if ($isi === []) {
            return collect();
        }

        $produk = Product::aktif()->whereIn('id', array_keys($isi))->get()->keyBy('id');

        if ($produk->count() !== count($isi)) {
            $this->simpan(array_intersect_key($isi, $produk->all()));
        }

        return $produk->map(fn (Product $product) => ['product' => $product, 'qty' => (int) $isi[$product->id]])->values();
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, jumlahItem: int, subtotal: int, ongkir: int, total: int}
     */
    public function ringkasan(): array
    {
        $isi = $this->isi();
        $subtotal = (int) $isi->sum(fn (array $baris) => $baris['product']->sell_price * $baris['qty']);
        $ongkir = $isi->isEmpty() ? 0 : (int) config('toko.ongkir_flat');

        return [
            'items' => $isi->map(fn (array $baris) => [
                ...$baris['product']->dataPublik(),
                'qty' => $baris['qty'],
                'subtotal' => $baris['product']->sell_price * $baris['qty'],
            ])->all(),
            'jumlahItem' => (int) $isi->sum('qty'),
            'subtotal' => $subtotal,
            'ongkir' => $ongkir,
            'total' => $subtotal + $ongkir,
        ];
    }
}
