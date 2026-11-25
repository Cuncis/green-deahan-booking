<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Keranjang toko di session: hanya menyimpan id produk (dan id varian) serta
 * jumlah. Harga SELALU dibaca ulang dari database, tidak pernah disimpan atau
 * diterima dari browser.
 */
class KeranjangService
{
    private const KUNCI = 'toko.keranjang';

    public function __construct(private readonly PenyediaOngkirToko $penyedia) {}

    /**
     * Kunci baris: "12" untuk produk tanpa varian, "12:5" untuk produk 12 varian 5.
     *
     * @return array<string, int> kunci baris => qty
     */
    private function mentah(): array
    {
        $isi = session(self::KUNCI, []);

        if (! is_array($isi)) {
            return [];
        }

        $hasil = [];

        foreach ($isi as $kunci => $qty) {
            $hasil[(string) $kunci] = (int) $qty;
        }

        return $hasil;
    }

    /**
     * @param  array<string, int>  $isi
     */
    private function simpan(array $isi): void
    {
        session([self::KUNCI => array_filter($isi, fn (int $qty) => $qty > 0)]);
    }

    private function batasQty(int $qty): int
    {
        return max(0, min($qty, (int) config('toko.maks_qty_per_produk')));
    }

    public static function kunciBaris(Product $product, ?ProductVariant $varian = null): string
    {
        return $varian === null ? (string) $product->id : "{$product->id}:{$varian->id}";
    }

    public function tambah(Product $product, int $qty, ?ProductVariant $varian = null): void
    {
        $kunci = self::kunciBaris($product, $varian);
        $isi = $this->mentah();
        $isi[$kunci] = $this->batasQty(($isi[$kunci] ?? 0) + $qty);
        $this->simpan($isi);
    }

    public function ubah(string $kunci, int $qty): void
    {
        $isi = $this->mentah();

        if (array_key_exists($kunci, $isi)) {
            $isi[$kunci] = $this->batasQty($qty);
            $this->simpan($isi);
        }
    }

    public function hapus(string $kunci): void
    {
        $isi = $this->mentah();
        unset($isi[$kunci]);
        $this->simpan($isi);
    }

    public function kosongkan(): void
    {
        session()->forget(self::KUNCI);
    }

    /**
     * Jumlah di keranjang per produk (semua variannya dijumlahkan), untuk
     * kartu produk yang berubah jadi penghitung jumlah setelah ditambahkan.
     *
     * @return array<int, int> product_id => qty
     */
    public function jumlahPerProduk(): array
    {
        $hasil = [];

        foreach ($this->mentah() as $kunci => $qty) {
            $idProduk = (int) explode(':', $kunci)[0];
            $hasil[$idProduk] = ($hasil[$idProduk] ?? 0) + $qty;
        }

        return $hasil;
    }

    public function jumlahItem(): int
    {
        return array_sum($this->mentah());
    }

    /**
     * Isi keranjang. Baris yang sudah tidak sah dibuang dari keranjang:
     * produk dihapus atau nonaktif, varian dihapus atau habis, produk yang
     * kini punya varian padahal barisnya tanpa varian, dan sebaliknya.
     *
     * @return Collection<int, array{kunci: string, product: Product, variant: ProductVariant|null, qty: int, harga: int, modal: int}>
     */
    public function isi(): Collection
    {
        $mentah = $this->mentah();

        if ($mentah === []) {
            return collect();
        }

        $idProduk = array_values(array_unique(array_map(fn (string $kunci) => (int) explode(':', $kunci)[0], array_keys($mentah))));
        $produk = Product::aktif()->with(['images', 'variants'])->whereIn('id', $idProduk)->get()->keyBy('id');

        $baris = collect();
        $sah = [];

        foreach ($mentah as $kunci => $qty) {
            [$idP, $idV] = array_pad(array_map('intval', explode(':', $kunci)), 2, null);
            $product = $produk->get($idP);

            if ($product === null) {
                continue;
            }

            $varian = $idV === null ? null : $product->variants->firstWhere('id', $idV);

            if ($product->variants->isNotEmpty() ? ($varian === null || ! $varian->is_available) : $idV !== null) {
                continue;
            }

            if ($varian !== null) {
                $varian->setRelation('product', $product);
            }

            $sah[$kunci] = $qty;
            $baris->push([
                'kunci' => $kunci,
                'product' => $product,
                'variant' => $varian,
                'qty' => $qty,
                'harga' => $varian?->hargaJual() ?? $product->sell_price,
                'modal' => $varian?->hargaModal() ?? $product->cost_price,
            ]);
        }

        if (count($sah) !== count($mentah)) {
            $this->simpan($sah);
        }

        return $baris;
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, jumlahItem: int, subtotal: int, ongkir: int|null, ongkirDinamis: bool, total: int}
     */
    public function ringkasan(): array
    {
        $isi = $this->isi();
        $subtotal = (int) $isi->sum(fn (array $baris) => $baris['harga'] * $baris['qty']);
        // Dengan penyedia ongkir aktif (RajaOngkir atau Biteship) ongkir baru diketahui setelah alamat dan kurir dipilih di checkout.
        $ongkirDinamis = $this->penyedia->aktif();
        $ongkir = $isi->isEmpty() ? 0 : ($ongkirDinamis ? null : (int) config('toko.ongkir_flat'));

        return [
            'items' => $isi->map(fn (array $baris) => [
                ...$baris['product']->dataPublik(),
                'kunci' => $baris['kunci'],
                'varian' => $baris['variant']?->name,
                'price' => $baris['harga'],
                'qty' => $baris['qty'],
                'subtotal' => $baris['harga'] * $baris['qty'],
            ])->all(),
            'jumlahItem' => (int) $isi->sum('qty'),
            'subtotal' => $subtotal,
            'ongkir' => $ongkir,
            'ongkirDinamis' => $ongkirDinamis,
            'total' => $subtotal + ($ongkir ?? 0),
        ];
    }
}
