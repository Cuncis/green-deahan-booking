<?php

namespace App\Services;

use App\Exceptions\BiteshipException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Pembungkus API Biteship untuk ongkir toko (hanya tarif kurir).
 *
 * Dokumentasi: https://biteship.com/en/docs/api/rates/retrieve
 *
 * Pengaman sandbox: selama BITESHIP_ENV bukan "production", key live
 * ("biteship_live.") ditolak. API key hanya dibaca dari konfigurasi dan
 * tidak pernah masuk ke pesan galat, log, atau respons.
 */
class BiteshipService
{
    /** Kode galat Biteship yang artinya "tidak ada kurir untuk rute ini", bukan kegagalan. */
    private const KODE_TANPA_KURIR = [40001001, 40001010];

    public function aktif(): bool
    {
        return is_string(config('biteship.api_key')) && trim((string) config('biteship.api_key')) !== '';
    }

    public function modeSandbox(): bool
    {
        return config('biteship.env') !== 'production';
    }

    /**
     * Tarif semua layanan kurir untuk satu pengiriman, termurah dulu.
     *
     * @param  array<int, array{name: string, value: int, weight: int, quantity: int}>  $items
     * @return array<int, array{courier_code: string, courier_name: string, service_code: string, service_name: string, duration: string, duration_min: int|null, duration_max: int|null, duration_unit: string, price: int}>
     */
    public function tarif(string $kodePosAsal, string $kodePosTujuan, array $items): array
    {
        $this->pastikanAman();

        if (! preg_match('/^\d{5}$/', $kodePosAsal) || ! preg_match('/^\d{5}$/', $kodePosTujuan)) {
            throw new BiteshipException('Kode pos asal atau tujuan tidak valid.');
        }

        $badan = [
            'origin_postal_code' => (int) $kodePosAsal,
            'destination_postal_code' => (int) $kodePosTujuan,
            'couriers' => (string) config('biteship.couriers'),
            'items' => array_map(fn (array $item) => [
                'name' => mb_substr($item['name'], 0, 100),
                'value' => $item['value'],
                'weight' => $item['weight'],
                'quantity' => $item['quantity'],
            ], $items),
        ];

        return Cache::remember(
            'biteship:tarif:'.sha1(json_encode($badan)),
            now()->addMinutes(max(1, (int) config('biteship.cache_menit'))),
            fn () => $this->ambilTarif($badan),
        );
    }

    /**
     * @param  array<string, mixed>  $badan
     * @return array<int, array<string, mixed>>
     */
    private function ambilTarif(array $badan): array
    {
        $this->catatPemakaian();

        try {
            $respons = Http::baseUrl((string) config('biteship.base_url'))
                ->withHeaders(['Authorization' => (string) config('biteship.api_key')])
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('biteship.timeout'))
                ->post('/v1/rates/couriers', $badan);
        } catch (ConnectionException) {
            throw new BiteshipException('Tidak bisa terhubung ke Biteship.');
        }

        if (! $respons->successful() || $respons->json('success') === false) {
            if (in_array((int) $respons->json('code'), self::KODE_TANPA_KURIR, true)) {
                return [];
            }

            $pesan = $respons->json('error') ?? $respons->json('message') ?? 'Respons tidak dikenal';

            throw new BiteshipException('Biteship menolak permintaan tarif (HTTP '.$respons->status().'): '.(is_string($pesan) ? mb_substr($pesan, 0, 200) : 'galat'));
        }

        $hasil = [];

        foreach ((array) $respons->json('pricing', []) as $tarif) {
            $harga = (int) ($tarif['price'] ?? 0);
            $kurir = (string) ($tarif['courier_code'] ?? '');
            $layanan = (string) ($tarif['courier_service_code'] ?? '');

            if ($harga <= 0 || $kurir === '' || $layanan === '') {
                continue;
            }

            $hasil[] = [
                'courier_code' => $kurir,
                'courier_name' => (string) ($tarif['courier_name'] ?? strtoupper($kurir)),
                'service_code' => $layanan,
                'service_name' => (string) ($tarif['courier_service_name'] ?? $layanan),
                'duration' => (string) ($tarif['duration'] ?? ''),
                'duration_min' => $this->angkaDurasi($tarif['shipment_duration_range'] ?? null, 0),
                'duration_max' => $this->angkaDurasi($tarif['shipment_duration_range'] ?? null, 1),
                'duration_unit' => (string) ($tarif['shipment_duration_unit'] ?? 'days'),
                'price' => $harga,
            ];
        }

        usort($hasil, fn (array $a, array $b) => $a['price'] <=> $b['price']);

        return $hasil;
    }

    /**
     * Permintaan ke Biteship hari ini dan bulan ini (WIB), untuk memantau biaya (Rp5 per permintaan).
     * Hasil yang di cache tidak dihitung karena tidak memanggil API.
     *
     * @return array{hariIni: int, bulanIni: int, tarifPerPermintaan: int, biayaBulanIni: int}
     */
    public function pemakaian(): array
    {
        $bulanIni = (int) Cache::get($this->kunciBulan(), 0);
        $tarif = (int) config('biteship.tarif_per_permintaan');

        return [
            'hariIni' => (int) Cache::get($this->kunciHari(), 0),
            'bulanIni' => $bulanIni,
            'tarifPerPermintaan' => $tarif,
            'biayaBulanIni' => $bulanIni * $tarif,
        ];
    }

    private function catatPemakaian(): void
    {
        foreach ([[$this->kunciHari(), now('Asia/Jakarta')->endOfDay()->addHour()], [$this->kunciBulan(), now('Asia/Jakarta')->endOfMonth()->addDays(2)]] as [$kunci, $berakhir]) {
            Cache::add($kunci, 0, $berakhir);
            Cache::increment($kunci);
        }
    }

    private function kunciHari(): string
    {
        return 'biteship:pemakaian:'.now('Asia/Jakarta')->toDateString();
    }

    private function kunciBulan(): string
    {
        return 'biteship:pemakaian-bulan:'.now('Asia/Jakarta')->format('Y-m');
    }

    /**
     * "2 - 3" jadi [2, 3], "1" jadi [1, 1].
     */
    private function angkaDurasi(mixed $rentang, int $urutan): ?int
    {
        if (! is_string($rentang) || ! preg_match_all('/\d+/', $rentang, $cocok) || $cocok[0] === []) {
            return null;
        }

        return (int) ($cocok[0][$urutan] ?? $cocok[0][0]);
    }

    private function pastikanAman(): void
    {
        if (! $this->aktif()) {
            throw new BiteshipException('Biteship belum dikonfigurasi.');
        }

        if ($this->modeSandbox() && str_starts_with(trim((string) config('biteship.api_key')), 'biteship_live.')) {
            throw new BiteshipException('Mode sandbox: API key live Biteship ditolak. Pakai key "biteship_test." atau set BITESHIP_ENV=production.');
        }
    }
}
