<?php

namespace App\Services;

use App\Exceptions\RajaOngkirException;
use App\Models\PengaturanSitus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Pembungkus API RajaOngkir (Komerce) untuk ongkir toko: penyedia tarif utama
 * karena gratis sampai batas harian. Hasilnya berbentuk sama dengan BiteshipService
 * supaya OngkirTokoService tidak perlu tahu penyedianya.
 *
 * Dokumentasi: https://rajaongkir.com/docs
 *
 * Kuota: tiap permintaan HTTP (cari lokasi atau hitung tarif) dihitung per hari WIB.
 * Hasil yang sudah di cache tidak memakai kuota. Begitu batas habis, atau RajaOngkir
 * sendiri menolak karena batas, penyedia ini dianggap habis sampai besok dan pemanggil
 * beralih ke Biteship. API key hanya dibaca dari konfigurasi dan tidak pernah masuk
 * ke pesan galat.
 */
class RajaOngkirService
{
    private const NAMA_KURIR = [
        'jne' => 'JNE',
        'jnt' => 'J&T',
        'sicepat' => 'SiCepat',
        'anteraja' => 'AnterAja',
        'pos' => 'POS Indonesia',
        'tiki' => 'TIKI',
        'ninja' => 'Ninja Xpress',
        'lion' => 'Lion Parcel',
        'wahana' => 'Wahana',
        'ide' => 'ID Express',
        'sentral' => 'Sentral Cargo',
        'rex' => 'REX',
        'rpx' => 'RPX',
        'sap' => 'SAP Express',
        'ncs' => 'NCS',
    ];

    public function aktif(): bool
    {
        return is_string(config('rajaongkir.api_key')) && trim((string) config('rajaongkir.api_key')) !== '';
    }

    public const KUNCI_BATAS = 'rajaongkir_batas_harian';

    public const KUNCI_SISA_AMAN = 'rajaongkir_sisa_aman';

    private const CACHE_PENGATURAN = 'rajaongkir:pengaturan';

    private const CACHE_TERAKHIR = 'rajaongkir:terakhir';

    /**
     * Batas permintaan per hari: pengaturan di dashboard superadmin, kalau belum
     * diisi memakai RAJAONGKIR_BATAS_HARIAN dari .env.
     */
    public function batasHarian(): int
    {
        return $this->pengaturan(self::KUNCI_BATAS, (int) config('rajaongkir.batas_harian'), 1);
    }

    /**
     * Sisa permintaan yang sengaja tidak dipakai, supaya hitungan kita yang sedikit meleset
     * tidak membuat RajaOngkir menolak lebih dulu.
     */
    public function sisaAman(): int
    {
        return $this->pengaturan(self::KUNCI_SISA_AMAN, (int) config('rajaongkir.sisa_aman'), 0);
    }

    /**
     * Simpan batas dari dashboard (null = kembali ke nilai .env).
     */
    public function simpanPengaturan(?int $batas, ?int $sisaAman): void
    {
        PengaturanSitus::updateOrCreate(['kunci' => self::KUNCI_BATAS], ['nilai' => $batas === null ? '' : (string) $batas]);
        PengaturanSitus::updateOrCreate(['kunci' => self::KUNCI_SISA_AMAN], ['nilai' => $sisaAman === null ? '' : (string) $sisaAman]);
        Cache::forget(self::CACHE_PENGATURAN);
    }

    /**
     * Uji kunci dan koneksi dengan satu pencarian lokasi (memakai satu permintaan dari kuota).
     *
     * @return array{berhasil: bool, pesan: string}
     */
    public function uji(): array
    {
        if (! $this->aktif()) {
            return ['berhasil' => false, 'pesan' => 'RAJAONGKIR_API_KEY belum diisi, atau konfigurasi belum dimuat ulang (jalankan config:clear dan restart server).'];
        }

        try {
            $respons = $this->panggil('get', '/destination/domestic-destination', ['search' => '10110', 'limit' => 1], 'uji koneksi');
            $this->pastikanBerhasil($respons, 'menolak permintaan uji');
        } catch (RajaOngkirException $galat) {
            return ['berhasil' => false, 'pesan' => $galat->getMessage()];
        }

        return ['berhasil' => true, 'pesan' => 'Terhubung ke RajaOngkir, API key diterima.'];
    }

    /**
     * Setel ulang hitungan hari ini dan status "habis", misal setelah batas dinaikkan atau
     * kalau hitungan kita meleset dari catatan RajaOngkir.
     */
    public function setelUlangHariIni(): void
    {
        Cache::forget($this->kunciPemakaian());
        Cache::forget($this->kunciHabis());
    }

    /**
     * Cabut status "habis" saja (hitungan tetap), supaya RajaOngkir dicoba lagi.
     */
    public function cobaLagi(): void
    {
        Cache::forget($this->kunciHabis());
    }

    /**
     * Alasan RajaOngkir tidak dipakai sekarang karena kuota: "batas" (hitungan sendiri
     * mencapai batas), "ditolak" (RajaOngkir sendiri menolak), atau null kalau masih bisa.
     */
    public function alasanHabis(): ?string
    {
        if (Cache::get($this->kunciHabis())) {
            return 'ditolak';
        }

        return $this->pemakaianHariIni() >= $this->batasEfektif() ? 'batas' : null;
    }

    /**
     * @return array{aktif: bool, batas: int, sisaAman: int, terpakai: int, tersisa: int, alasanHabis: string|null, terakhir: array{waktu: string, aksi: string, berhasil: bool, pesan: string|null}|null}
     */
    public function ringkasan(): array
    {
        $terpakai = $this->pemakaianHariIni();

        return [
            'aktif' => $this->aktif(),
            'batas' => $this->batasHarian(),
            'sisaAman' => $this->sisaAman(),
            'terpakai' => $terpakai,
            'tersisa' => max(0, $this->batasEfektif() - $terpakai),
            'alasanHabis' => $this->alasanHabis(),
            'terakhir' => Cache::get(self::CACHE_TERAKHIR),
        ];
    }

    private function batasEfektif(): int
    {
        return max(0, $this->batasHarian() - $this->sisaAman());
    }

    private function pengaturan(string $kunci, int $bawaan, int $minimum): int
    {
        $tersimpan = Cache::rememberForever(self::CACHE_PENGATURAN, fn () => PengaturanSitus::query()->whereIn('kunci', [self::KUNCI_BATAS, self::KUNCI_SISA_AMAN])->pluck('nilai', 'kunci')->all());
        $nilai = $tersimpan[$kunci] ?? '';

        return is_numeric($nilai) ? max($minimum, (int) $nilai) : max($minimum, $bawaan);
    }

    /**
     * Jumlah permintaan ke RajaOngkir hari ini (WIB).
     */
    public function pemakaianHariIni(): int
    {
        return (int) Cache::get($this->kunciPemakaian(), 0);
    }

    /**
     * Batas harian sudah tercapai (hitungan sendiri) atau RajaOngkir sudah menolak karena batas.
     */
    public function kuotaHabis(): bool
    {
        return $this->alasanHabis() !== null;
    }

    /**
     * Tarif semua layanan kurir untuk satu pengiriman, termurah dulu.
     *
     * @param  array<int, array{name: string, value: int, weight: int, quantity: int}>  $items
     * @return array<int, array{courier_code: string, courier_name: string, service_code: string, service_name: string, duration: string, duration_min: int|null, duration_max: int|null, duration_unit: string, price: int}>
     *
     * @throws RajaOngkirException
     */
    public function tarif(string $kodePosAsal, string $kodePosTujuan, array $items): array
    {
        if (! $this->aktif()) {
            throw new RajaOngkirException('RajaOngkir belum dikonfigurasi.');
        }

        if (! preg_match('/^\d{5}$/', $kodePosAsal) || ! preg_match('/^\d{5}$/', $kodePosTujuan)) {
            throw new RajaOngkirException('Kode pos asal atau tujuan tidak valid.');
        }

        $berat = max(1, (int) array_sum(array_map(fn (array $item) => $item['weight'] * $item['quantity'], $items)));
        $kurir = (string) config('rajaongkir.couriers');

        // Yang sudah di cache dilayani tanpa kuota, bahkan setelah batas harian habis.
        return Cache::remember(
            'rajaongkir:tarif:'.sha1(json_encode([$kodePosAsal, $kodePosTujuan, $berat, $kurir])),
            now()->addMinutes(max(1, (int) config('rajaongkir.cache_menit'))),
            fn () => $this->ambilTarif($kodePosAsal, $kodePosTujuan, $berat, $kurir),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ambilTarif(string $kodePosAsal, string $kodePosTujuan, int $berat, string $kurir): array
    {
        $respons = $this->panggil('post', '/calculate/domestic-cost', [
            'origin' => $this->idLokasi($kodePosAsal),
            'destination' => $this->idLokasi($kodePosTujuan),
            'weight' => $berat,
            'courier' => $kurir,
        ]);

        // 400 "tidak ditemukan" = tidak ada kurir untuk rute ini, bukan kegagalan.
        if ($respons->status() === 400 && str_contains(strtolower((string) $respons->json('meta.message')), 'not found')) {
            return [];
        }

        $this->pastikanBerhasil($respons, 'menolak permintaan tarif');

        $hasil = [];

        foreach ((array) $respons->json('data', []) as $tarif) {
            $harga = (int) ($tarif['cost'] ?? 0);
            $kodeKurir = strtolower(trim((string) ($tarif['code'] ?? '')));
            $layanan = trim((string) ($tarif['service'] ?? ''));
            $kodeLayanan = $this->kodeLayanan($layanan);

            if ($harga <= 0 || $kodeKurir === '' || $kodeLayanan === '') {
                continue;
            }

            $etd = trim((string) ($tarif['etd'] ?? ''));
            $deskripsi = trim((string) ($tarif['description'] ?? ''));

            $hasil[] = [
                'courier_code' => $kodeKurir,
                'courier_name' => self::NAMA_KURIR[$kodeKurir] ?? (string) ($tarif['name'] ?? strtoupper($kodeKurir)),
                'service_code' => $kodeLayanan,
                'service_name' => $deskripsi !== '' && mb_strlen($deskripsi) <= 30 ? $deskripsi : $layanan,
                'duration' => $etd,
                'duration_min' => $this->angkaDurasi($etd, 0),
                'duration_max' => $this->angkaDurasi($etd, 1),
                'duration_unit' => preg_match('/hour|jam/i', $etd) ? 'hours' : 'days',
                'price' => $harga,
            ];
        }

        usort($hasil, fn (array $a, array $b) => $a['price'] <=> $b['price']);

        return $hasil;
    }

    /**
     * Id lokasi RajaOngkir dari kode pos (disimpan lama; kode pos yang tidak dikenal
     * diingat sebentar supaya tidak memakai kuota berulang).
     *
     * @throws RajaOngkirException
     */
    private function idLokasi(string $kodePos): int
    {
        if (Cache::has('rajaongkir:lokasi-tidak-ada:'.$kodePos)) {
            throw new RajaOngkirException("Kode pos {$kodePos} tidak dikenal RajaOngkir.");
        }

        return (int) Cache::remember(
            'rajaongkir:lokasi:'.$kodePos,
            now()->addDays(max(1, (int) config('rajaongkir.cache_lokasi_hari'))),
            function () use ($kodePos) {
                $respons = $this->panggil('get', '/destination/domestic-destination', ['search' => $kodePos, 'limit' => 20]);

                if ($respons->status() !== 404) {
                    $this->pastikanBerhasil($respons, 'menolak pencarian lokasi');
                }

                $cocok = collect((array) $respons->json('data', []))->first(fn ($lokasi) => (string) ($lokasi['zip_code'] ?? '') === $kodePos && (int) ($lokasi['id'] ?? 0) > 0);

                if ($cocok === null) {
                    Cache::put('rajaongkir:lokasi-tidak-ada:'.$kodePos, true, now()->addHour());

                    throw new RajaOngkirException("Kode pos {$kodePos} tidak dikenal RajaOngkir.");
                }

                return (int) $cocok['id'];
            },
        );
    }

    /**
     * Satu permintaan HTTP, dihitung ke kuota harian. Galat jaringan dan balasan gagal
     * dijadikan RajaOngkirException tanpa membocorkan API key.
     *
     * @param  'get'|'post'  $metode
     * @param  array<string, mixed>  $data
     *
     * @throws RajaOngkirException
     */
    private function panggil(string $metode, string $path, array $data, string $aksi = ''): Response
    {
        if ($this->kuotaHabis()) {
            throw new RajaOngkirException('Batas harian RajaOngkir sudah habis.', kuotaHabis: true);
        }

        $kunci = $this->kunciPemakaian();
        Cache::add($kunci, 0, now('Asia/Jakarta')->endOfDay()->addHour());
        Cache::increment($kunci);

        try {
            $http = Http::baseUrl((string) config('rajaongkir.base_url'))
                ->withHeaders(['key' => (string) config('rajaongkir.api_key')])
                ->acceptJson()
                ->timeout((int) config('rajaongkir.timeout'));

            $respons = $metode === 'get' ? $http->get($path, $data) : $http->asForm()->post($path, $data);
        } catch (ConnectionException) {
            $this->catatTerakhir($aksi ?: $this->namaAksi($path), false, 'Tidak bisa terhubung ke RajaOngkir.');

            throw new RajaOngkirException('Tidak bisa terhubung ke RajaOngkir.');
        }

        $pesan = strtolower((string) $respons->json('meta.message'));
        $this->catatTerakhir($aksi ?: $this->namaAksi($path), $respons->successful(), $respons->successful() ? null : mb_substr((string) ($respons->json('meta.message') ?? 'HTTP '.$respons->status()), 0, 150));

        if ($respons->status() === 429 || preg_match('/limit|quota|kuota|exceed/', $pesan)) {
            Cache::put($this->kunciHabis(), true, now('Asia/Jakarta')->endOfDay()->addHour());

            throw new RajaOngkirException('RajaOngkir menolak karena batas penggunaan.', kuotaHabis: true);
        }

        return $respons;
    }

    /**
     * @throws RajaOngkirException
     */
    private function pastikanBerhasil(Response $respons, string $tindakan): void
    {
        if ($respons->successful() && (int) $respons->json('meta.code', $respons->status()) < 400) {
            return;
        }

        $pesan = $respons->json('meta.message');

        throw new RajaOngkirException("RajaOngkir {$tindakan} (HTTP {$respons->status()}): ".(is_string($pesan) ? mb_substr($pesan, 0, 200) : 'galat'));
    }

    /**
     * Kode layanan seperti "JTR>130" atau "REG" jadi huruf kecil, angka, dan garis bawah
     * (format kurir:layanan yang diterima checkout).
     */
    private function kodeLayanan(string $layanan): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($layanan)), '_');
    }

    /**
     * "2-3 day" jadi [2, 3], "1 day" jadi [1, 1].
     */
    private function angkaDurasi(string $etd, int $urutan): ?int
    {
        if (! preg_match_all('/\d+/', $etd, $cocok) || $cocok[0] === []) {
            return null;
        }

        return (int) ($cocok[0][$urutan] ?? $cocok[0][0]);
    }

    /**
     * Catatan permintaan terakhir ke RajaOngkir, supaya kegagalan (kunci salah, kurir tidak dikenal)
     * terlihat di dashboard tanpa membuka log.
     */
    private function catatTerakhir(string $aksi, bool $berhasil, ?string $pesan): void
    {
        Cache::put(self::CACHE_TERAKHIR, [
            'waktu' => now('Asia/Jakarta')->format('d/m/Y H:i:s'),
            'aksi' => $aksi,
            'berhasil' => $berhasil,
            'pesan' => $pesan,
        ], now()->addDays(7));
    }

    private function namaAksi(string $path): string
    {
        return str_contains($path, 'calculate') ? 'hitung tarif' : 'cari lokasi';
    }

    private function kunciPemakaian(): string
    {
        return 'rajaongkir:pemakaian:'.now('Asia/Jakarta')->toDateString();
    }

    private function kunciHabis(): string
    {
        return 'rajaongkir:habis:'.now('Asia/Jakarta')->toDateString();
    }
}
