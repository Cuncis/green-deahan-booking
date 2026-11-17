<?php

namespace App\Services;

use App\Exceptions\BiteshipException;
use App\Models\Product;

/**
 * Ongkir keranjang toko dari Biteship. Barang dikirim langsung oleh supplier,
 * jadi keranjang dibagi per kode pos asal supplier, tiap kelompok dihitung
 * sendiri, dan layanan kurir yang ditawarkan hanya yang tersedia di semua
 * kelompok (ongkirnya dijumlahkan). Harga tidak pernah diterima dari browser:
 * checkout memilih ulang layanan dari hasil hitung server.
 */
class OngkirTokoService
{
    public function __construct(
        private readonly BiteshipService $biteship,
        private readonly KeranjangService $keranjang,
    ) {}

    public function aktif(): bool
    {
        return $this->biteship->aktif();
    }

    /**
     * Layanan kurir yang tersedia untuk isi keranjang saat ini, termurah dulu.
     *
     * @return array<int, array{kode: string, courier_code: string, courier_name: string, service_code: string, service_name: string, label: string, duration: string, price: int}>
     *
     * @throws BiteshipException
     */
    public function opsi(string $kodePosTujuan): array
    {
        $isi = $this->keranjang->isi();

        if ($isi->isEmpty()) {
            return [];
        }

        $gabungan = null;

        foreach ($isi->groupBy(fn (array $baris) => $this->kodePosAsal($baris['product'])) as $kodePosAsal => $kelompok) {
            $tarif = $this->biteship->tarif((string) $kodePosAsal, $kodePosTujuan, $kelompok->map(fn (array $baris) => [
                'name' => $baris['product']->name,
                'value' => (int) $baris['product']->sell_price,
                'weight' => $baris['product']->weight_gram > 0 ? (int) $baris['product']->weight_gram : (int) config('biteship.berat_bawaan'),
                'quantity' => (int) $baris['qty'],
            ])->values()->all());

            $perKode = collect($tarif)->keyBy(fn (array $layanan) => $layanan['courier_code'].':'.$layanan['service_code']);

            $gabungan = $gabungan === null
                ? $perKode
                : $gabungan->intersectByKeys($perKode)->map(function (array $layanan, string $kode) use ($perKode) {
                    $lain = $perKode[$kode];

                    return [
                        ...$layanan,
                        'price' => $layanan['price'] + $lain['price'],
                        'duration_min' => $this->terkecil($layanan['duration_min'], $lain['duration_min']),
                        'duration_max' => $this->terbesar($layanan['duration_max'], $lain['duration_max']),
                    ];
                });
        }

        return ($gabungan ?? collect())
            ->map(fn (array $layanan, string $kode) => [
                'kode' => $kode,
                'courier_code' => $layanan['courier_code'],
                'courier_name' => $layanan['courier_name'],
                'service_code' => $layanan['service_code'],
                'service_name' => $layanan['service_name'],
                'label' => "{$layanan['courier_name']} {$layanan['service_name']}",
                'duration' => $this->teksDurasi($layanan),
                'price' => $layanan['price'],
            ])
            ->sortBy('price')
            ->values()
            ->all();
    }

    /**
     * Satu layanan dari hasil hitung server, atau null kalau sudah tidak tersedia.
     *
     * @return array<string, mixed>|null
     *
     * @throws BiteshipException
     */
    public function pilih(string $kode, string $kodePosTujuan): ?array
    {
        return collect($this->opsi($kodePosTujuan))->firstWhere('kode', $kode);
    }

    private function kodePosAsal(Product $product): string
    {
        $product->loadMissing('supplier');

        $kodePos = $product->supplier?->origin_postal_code ?: config('biteship.origin_postal_code');

        if (! is_string($kodePos) || ! preg_match('/^\d{5}$/', $kodePos)) {
            throw new BiteshipException("Kode pos asal pengiriman untuk \"{$product->name}\" belum diatur.");
        }

        return $kodePos;
    }

    private function terkecil(?int $a, ?int $b): ?int
    {
        return $a === null || $b === null ? ($a ?? $b) : min($a, $b);
    }

    private function terbesar(?int $a, ?int $b): ?int
    {
        return $a === null || $b === null ? ($a ?? $b) : max($a, $b);
    }

    /**
     * @param  array<string, mixed>  $layanan
     */
    private function teksDurasi(array $layanan): string
    {
        $min = $layanan['duration_min'];
        $max = $layanan['duration_max'];

        if ($min === null || $max === null) {
            return str_replace('days', 'hari', (string) $layanan['duration']);
        }

        $satuan = ($layanan['duration_unit'] ?? 'days') === 'hours' ? 'jam' : 'hari';

        return $min === $max ? "{$min} {$satuan}" : "{$min} - {$max} {$satuan}";
    }
}
