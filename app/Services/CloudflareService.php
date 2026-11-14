<?php

namespace App\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * greendeahan.com di-proxy lewat Cloudflare. Rentang IP di bawah dipakai
 * untuk dua hal: trustProxies (bootstrap/app.php) supaya $request->ip()
 * adalah IP pengunjung asli, dan memastikan header lokasi Cloudflare hanya
 * dibaca kalau request benar-benar datang dari Cloudflare (header seperti
 * cf-ipcountry bisa dipalsukan kalau server diakses langsung).
 *
 * Header lokasi provinsi (cf-region-code) baru dikirim setelah Managed
 * Transform "Add visitor location headers" diaktifkan di dashboard
 * Cloudflare (Rules > Transform Rules > Managed Transforms).
 * cf-ipcountry sudah aktif secara bawaan.
 */
class CloudflareService
{
    /**
     * Sumber: https://www.cloudflare.com/ips-v4 dan /ips-v6. Jarang berubah,
     * tapi cek ulang sesekali.
     *
     * @var array<int, string>
     */
    public const RENTANG_IP = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    /**
     * Kode negara Cloudflare yang bukan negara: XX = tidak diketahui, T1 = Tor.
     *
     * @var array<int, string>
     */
    private const KODE_BUKAN_NEGARA = ['XX', 'T1'];

    public function dariCloudflare(Request $request): bool
    {
        $ipPengirim = $request->server('REMOTE_ADDR');

        return is_string($ipPengirim) && IpUtils::checkIp($ipPengirim, self::RENTANG_IP);
    }

    /**
     * Negara (ISO 3166-1, misal "ID") dan kode wilayah tingkat pertama
     * (ISO 3166-2 tanpa awalan negara, misal "JB" untuk Jawa Barat). Null
     * kalau tidak diketahui atau request tidak lewat Cloudflare.
     *
     * @return array{negara: ?string, wilayah: ?string}
     */
    public function lokasi(Request $request): array
    {
        if (! $this->dariCloudflare($request)) {
            return ['negara' => null, 'wilayah' => null];
        }

        $negara = strtoupper(trim((string) $request->header('cf-ipcountry')));
        $negara = preg_match('/^[A-Z]{2}$/', $negara) && ! in_array($negara, self::KODE_BUKAN_NEGARA, true) ? $negara : null;

        $wilayah = strtoupper(trim((string) $request->header('cf-region-code')));
        $wilayah = $negara !== null && preg_match('/^[A-Z0-9]{1,3}$/', $wilayah) ? $wilayah : null;

        return ['negara' => $negara, 'wilayah' => $wilayah];
    }
}
