<?php

namespace App\Services;

use App\Exceptions\BiteshipException;
use App\Models\Product;
use App\Models\Wilayah;
use Illuminate\Support\Collection;

/**
 * Ongkir keranjang toko dari Biteship. Barang dikirim langsung oleh supplier,
 * jadi keranjang dibagi per kode pos asal supplier, tiap kelompok dihitung
 * sendiri, dan layanan kurir yang ditawarkan hanya yang tersedia di semua
 * kelompok (ongkirnya dijumlahkan). Harga tidak pernah diterima dari browser:
 * checkout memilih ulang layanan dari hasil hitung server.
 */
class OngkirTokoService
{
    /** Kode layanan "ongkir tetap" saat Biteship gagal; mengikuti format kurir:layanan. */
    public const KODE_DARURAT = 'darurat:tetap';

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
     * Tiap layanan memuat rincian paket (asal perkiraan, barang, dan ongkirnya)
     * yang jumlahnya sama dengan total ongkir.
     *
     * @return array<int, array{kode: string, courier_code: string, courier_name: string, service_code: string, service_name: string, label: string, duration: string, price: int, paket: array<int, array{asal: string, kodePos: string, barang: array<int, string>, price: int}>}>
     *
     * @throws BiteshipException
     */
    public function opsi(string $kodePosTujuan): array
    {
        $isi = $this->keranjang->isi();

        if ($isi->isEmpty()) {
            return [];
        }

        $kelompokTarif = [];

        foreach ($isi->groupBy(fn (array $baris) => $this->kodePosAsal($baris['product'])) as $kodePosAsal => $kelompok) {
            $tarif = $this->biteship->tarif((string) $kodePosAsal, $kodePosTujuan, $kelompok->map(fn (array $baris) => [
                'name' => $baris['variant'] ? "{$baris['product']->name} ({$baris['variant']->name})" : $baris['product']->name,
                'value' => (int) $baris['harga'],
                'weight' => $baris['product']->weight_gram > 0 ? (int) $baris['product']->weight_gram : (int) config('biteship.berat_bawaan'),
                'quantity' => (int) $baris['qty'],
            ])->values()->all());

            $kelompokTarif[] = [
                'asal' => $this->namaAsal((string) $kodePosAsal),
                'kodePos' => (string) $kodePosAsal,
                'barang' => $kelompok->map(fn (array $baris) => $baris['variant'] ? "{$baris['product']->name} ({$baris['variant']->name})" : $baris['product']->name)->values()->all(),
                'tarif' => collect($tarif)->keyBy(fn (array $layanan) => $layanan['courier_code'].':'.$layanan['service_code']),
            ];
        }

        $kodeBersama = collect($kelompokTarif)
            ->map(fn (array $kelompok) => $kelompok['tarif']->keys())
            ->reduce(fn (?Collection $bersama, Collection $kode) => $bersama === null ? $kode : $bersama->intersect($kode));

        return ($kodeBersama ?? collect())
            ->map(function (string $kode) use ($kelompokTarif) {
                $layanan = $kelompokTarif[0]['tarif'][$kode];
                $paket = array_map(fn (array $kelompok) => [
                    'asal' => $kelompok['asal'],
                    'kodePos' => $kelompok['kodePos'],
                    'barang' => $kelompok['barang'],
                    'price' => $this->denganCadangan((int) $kelompok['tarif'][$kode]['price']),
                ], $kelompokTarif);
                $semua = array_map(fn (array $kelompok) => $kelompok['tarif'][$kode], $kelompokTarif);

                return [
                    'kode' => $kode,
                    'courier_code' => $layanan['courier_code'],
                    'courier_name' => $layanan['courier_name'],
                    'service_code' => $layanan['service_code'],
                    'service_name' => $layanan['service_name'],
                    'label' => "{$layanan['courier_name']} {$layanan['service_name']}",
                    'duration' => $this->teksDurasiGabungan($semua),
                    'price' => array_sum(array_column($paket, 'price')),
                    'paket' => $paket,
                ];
            })
            ->sortBy('price')
            ->values()
            ->all();
    }

    /**
     * Cadangan kalau tarif Biteship tidak bisa diambil: satu pilihan ongkir
     * tetap (TOKO_ONGKIR_FLAT), kurirnya dipilih toko saat mengirim. Null kalau dimatikan.
     *
     * @return array<string, mixed>|null
     */
    public function opsiDarurat(): ?array
    {
        if (! config('biteship.ongkir_darurat')) {
            return null;
        }

        return [
            'kode' => self::KODE_DARURAT,
            'courier_code' => 'darurat',
            'courier_name' => 'Ongkir tetap',
            'service_code' => 'tetap',
            'service_name' => 'Ongkir tetap',
            'label' => 'Ongkir tetap (kurir dipilih toko)',
            'duration' => '',
            'price' => (int) config('toko.ongkir_flat'),
            'paket' => [],
        ];
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
        if ($kode === self::KODE_DARURAT) {
            return $this->pilihDarurat($kodePosTujuan);
        }

        return collect($this->opsi($kodePosTujuan))->firstWhere('kode', $kode);
    }

    /**
     * Ongkir tetap hanya sah selama tarif Biteship memang masih tidak bisa
     * diambil. Kalau tarif sudah normal, pembeli diminta memilih ulang supaya
     * ongkir tetap tidak dipakai untuk menghindari ongkir yang sebenarnya.
     *
     * @return array<string, mixed>|null
     */
    private function pilihDarurat(string $kodePosTujuan): ?array
    {
        try {
            $this->opsi($kodePosTujuan);
        } catch (BiteshipException) {
            return $this->opsiDarurat();
        }

        return null;
    }

    private function kodePosAsal(Product $product): string
    {
        $product->loadMissing('supplier');

        $kodePos = $product->origin_postal_code ?: $product->supplier?->origin_postal_code ?: config('biteship.origin_postal_code');

        if (! is_string($kodePos) || ! preg_match('/^\d{5}$/', $kodePos)) {
            throw new BiteshipException("Kode pos asal pengiriman untuk \"{$product->name}\" belum diatur.");
        }

        return $kodePos;
    }

    /**
     * Ongkir per paket ditambah cadangan persen, dibulatkan ke atas per Rp500.
     */
    private function denganCadangan(int $harga): int
    {
        $persen = max(0.0, (float) config('biteship.cadangan_persen'));

        return (int) (ceil($harga * (1 + $persen / 100) / 500) * 500);
    }

    /**
     * Kota atau kabupaten asal dari kode pos, untuk ditampilkan ke pelanggan.
     */
    private function namaAsal(string $kodePos): string
    {
        $kelurahan = Wilayah::query()->where('level', Wilayah::LEVEL_KELURAHAN)->where('kodepos', $kodePos)->first();
        $kota = $kelurahan ? Wilayah::find(Wilayah::indukDari((string) Wilayah::indukDari($kelurahan->kode))) : null;

        return $kota?->nama ?? "kode pos {$kodePos}";
    }

    /**
     * Rentang estimasi seluruh paket: dari paket tercepat sampai paket terlambat.
     *
     * @param  array<int, array<string, mixed>>  $layananPerPaket
     */
    private function teksDurasiGabungan(array $layananPerPaket): string
    {
        $min = array_column($layananPerPaket, 'duration_min');
        $max = array_column($layananPerPaket, 'duration_max');

        if (count($layananPerPaket) === 1 || in_array(null, $min, true) || in_array(null, $max, true)) {
            return $this->teksDurasi($layananPerPaket[0]);
        }

        return $this->teksDurasi([...$layananPerPaket[0], 'duration_min' => min($min), 'duration_max' => max($max)]);
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
