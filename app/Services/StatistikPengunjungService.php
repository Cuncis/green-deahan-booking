<?php

namespace App\Services;

use App\Models\Artikel;
use App\Models\KunjunganHalaman;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan kunjungan situs korporat untuk dashboard superadmin:
 * angka utama, grafik harian, halaman terpopuler, perangkat, dan sumber.
 *
 * "Pengunjung" = pengunjung unik per hari (hash harian), dijumlahkan untuk
 * rentang yang dipilih. "Tampilan" = jumlah halaman yang dibuka.
 */
class StatistikPengunjungService
{
    /**
     * @var array<int, int>
     */
    public const RENTANG_HARI = [7, 30, 90];

    /**
     * @var array<string, string>
     */
    private const NAMA_HALAMAN = [
        '/' => 'Beranda',
        '/tentang-kami' => 'Tentang Kami',
        '/galeri' => 'Galeri',
        '/blog' => 'Blog',
        '/kontak' => 'Kontak',
        '/harga' => 'Harga',
        '/daftar' => 'Daftar',
    ];

    /**
     * Kode provinsi ISO 3166-2:ID (tanpa "ID-"), dari header cf-region-code.
     *
     * @var array<string, string>
     */
    private const NAMA_PROVINSI = [
        'AC' => 'Aceh',
        'BA' => 'Bali',
        'BB' => 'Kepulauan Bangka Belitung',
        'BE' => 'Bengkulu',
        'BT' => 'Banten',
        'GO' => 'Gorontalo',
        'JA' => 'Jambi',
        'JB' => 'Jawa Barat',
        'JI' => 'Jawa Timur',
        'JK' => 'DKI Jakarta',
        'JT' => 'Jawa Tengah',
        'KB' => 'Kalimantan Barat',
        'KI' => 'Kalimantan Timur',
        'KR' => 'Kepulauan Riau',
        'KS' => 'Kalimantan Selatan',
        'KT' => 'Kalimantan Tengah',
        'KU' => 'Kalimantan Utara',
        'LA' => 'Lampung',
        'MA' => 'Maluku',
        'MU' => 'Maluku Utara',
        'NB' => 'Nusa Tenggara Barat',
        'NT' => 'Nusa Tenggara Timur',
        'PA' => 'Papua',
        'PB' => 'Papua Barat',
        'PD' => 'Papua Barat Daya',
        'PE' => 'Papua Pegunungan',
        'PS' => 'Papua Selatan',
        'PT' => 'Papua Tengah',
        'RI' => 'Riau',
        'SA' => 'Sulawesi Utara',
        'SB' => 'Sumatera Barat',
        'SG' => 'Sulawesi Tenggara',
        'SN' => 'Sulawesi Selatan',
        'SR' => 'Sulawesi Barat',
        'SS' => 'Sumatera Selatan',
        'ST' => 'Sulawesi Tengah',
        'SU' => 'Sumatera Utara',
        'YO' => 'DI Yogyakarta',
    ];

    /**
     * Domain referer (persis, atau subdomain-nya) => nama sumber.
     *
     * @var array<string, string>
     */
    private const SUMBER_DOMAIN = [
        'wa.me' => 'WhatsApp',
        't.co' => 'X / Twitter',
        'x.com' => 'X / Twitter',
        'fb.me' => 'Facebook',
    ];

    /**
     * Potongan nama host referer => nama sumber.
     *
     * @var array<string, string>
     */
    private const SUMBER_POTONGAN = [
        'google.' => 'Google',
        'bing.' => 'Bing',
        'yahoo.' => 'Yahoo',
        'whatsapp' => 'WhatsApp',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'twitter' => 'X / Twitter',
        'linkedin' => 'LinkedIn',
    ];

    /**
     * @return array{
     *     rentang: int,
     *     pilihanRentang: array<int, int>,
     *     ringkasan: array{pengunjung: int, tampilan: int, pengunjungHariIni: int, halamanPerPengunjung: float, perubahanPengunjung: float|null, perubahanTampilan: float|null},
     *     harian: array<int, array{tanggal: string, label: string, pengunjung: int, tampilan: int}>,
     *     halamanTeratas: array<int, array{path: string, nama: string, tampilan: int, pengunjung: int}>,
     *     perangkat: array<int, array{nama: string, jumlah: int}>,
     *     sumber: array<int, array{nama: string, jumlah: int}>,
     *     lokasi: array{indonesia: int, luarNegeri: int, tidakDiketahui: int, provinsi: array<int, array{nama: string, jumlah: int}>, negara: array<int, array{kode: string, nama: string, jumlah: int}>}
     * }
     */
    public function ringkasan(int $rentangHari): array
    {
        $rentangHari = in_array($rentangHari, self::RENTANG_HARI, true) ? $rentangHari : 30;
        $hariIni = CarbonImmutable::now(KunjunganHalaman::ZONA_WAKTU)->startOfDay();
        $mulai = $hariIni->subDays($rentangHari - 1);
        $mulaiSebelumnya = $mulai->subDays($rentangHari);

        $periodeIni = $this->query($mulai, $hariIni);
        $harian = $this->harian($mulai, $hariIni);

        $pengunjung = array_sum(array_column($harian, 'pengunjung'));
        $tampilan = array_sum(array_column($harian, 'tampilan'));
        $sebelumnya = $this->total($mulaiSebelumnya, $mulai->subDay());

        return [
            'rentang' => $rentangHari,
            'pilihanRentang' => self::RENTANG_HARI,
            'ringkasan' => [
                'pengunjung' => $pengunjung,
                'tampilan' => $tampilan,
                'pengunjungHariIni' => end($harian)['pengunjung'] ?? 0,
                'halamanPerPengunjung' => $pengunjung > 0 ? round($tampilan / $pengunjung, 1) : 0.0,
                'perubahanPengunjung' => $this->persenPerubahan($pengunjung, $sebelumnya['pengunjung']),
                'perubahanTampilan' => $this->persenPerubahan($tampilan, $sebelumnya['tampilan']),
            ],
            'harian' => $harian,
            'halamanTeratas' => $this->halamanTeratas($periodeIni->clone()),
            'perangkat' => $periodeIni->clone()
                ->select('perangkat as nama', DB::raw('COUNT(*) as jumlah'))
                ->groupBy('perangkat')
                ->orderByDesc('jumlah')
                ->get()
                ->map(fn ($row) => ['nama' => ucfirst((string) $row->nama), 'jumlah' => (int) $row->jumlah])
                ->all(),
            'sumber' => $this->sumber($periodeIni->clone()),
            'lokasi' => $this->lokasi($periodeIni->clone()),
        ];
    }

    private function query(CarbonImmutable $mulai, CarbonImmutable $sampai): Builder
    {
        return KunjunganHalaman::query()
            ->whereBetween('tanggal', [$mulai->toDateString(), $sampai->toDateString()]);
    }

    /**
     * @return array<int, array{tanggal: string, label: string, pengunjung: int, tampilan: int}>
     */
    private function harian(CarbonImmutable $mulai, CarbonImmutable $sampai): array
    {
        $perTanggal = $this->query($mulai, $sampai)
            ->select('tanggal', DB::raw('COUNT(*) as tampilan'), DB::raw('COUNT(DISTINCT pengunjung_hash) as pengunjung'))
            ->groupBy('tanggal')
            ->get()
            ->keyBy(fn ($row) => CarbonImmutable::parse($row->tanggal)->toDateString());

        $hasil = [];
        for ($tanggal = $mulai; $tanggal->lte($sampai); $tanggal = $tanggal->addDay()) {
            $baris = $perTanggal->get($tanggal->toDateString());
            $hasil[] = [
                'tanggal' => $tanggal->toDateString(),
                'label' => $tanggal->locale('id')->translatedFormat('j M'),
                'pengunjung' => (int) ($baris->pengunjung ?? 0),
                'tampilan' => (int) ($baris->tampilan ?? 0),
            ];
        }

        return $hasil;
    }

    /**
     * @return array{pengunjung: int, tampilan: int}
     */
    private function total(CarbonImmutable $mulai, CarbonImmutable $sampai): array
    {
        $rows = $this->query($mulai, $sampai)
            ->select('tanggal', DB::raw('COUNT(*) as tampilan'), DB::raw('COUNT(DISTINCT pengunjung_hash) as pengunjung'))
            ->groupBy('tanggal')
            ->get();

        return [
            'pengunjung' => (int) $rows->sum('pengunjung'),
            'tampilan' => (int) $rows->sum('tampilan'),
        ];
    }

    /**
     * @return array<int, array{path: string, nama: string, tampilan: int, pengunjung: int}>
     */
    private function halamanTeratas(Builder $query): array
    {
        $baris = $query
            ->select('path', DB::raw('COUNT(*) as tampilan'), DB::raw('COUNT(DISTINCT pengunjung_hash) as pengunjung'))
            ->groupBy('path')
            ->orderByDesc('tampilan')
            ->limit(8)
            ->get();

        $judulArtikel = Artikel::whereIn('slug', $baris->pluck('path')
            ->filter(fn (string $path) => str_starts_with($path, '/blog/'))
            ->map(fn (string $path) => substr($path, strlen('/blog/'))))
            ->pluck('judul', 'slug');

        return $baris->map(fn ($row) => [
            'path' => $row->path,
            'nama' => $this->namaHalaman($row->path, $judulArtikel),
            'tampilan' => (int) $row->tampilan,
            'pengunjung' => (int) $row->pengunjung,
        ])->all();
    }

    /**
     * @param  Collection<string, string>  $judulArtikel
     */
    private function namaHalaman(string $path, Collection $judulArtikel): string
    {
        if (isset(self::NAMA_HALAMAN[$path])) {
            return self::NAMA_HALAMAN[$path];
        }

        if (str_starts_with($path, '/blog/')) {
            return $judulArtikel->get(substr($path, strlen('/blog/'))) ?? 'Artikel: '.substr($path, strlen('/blog/'));
        }

        if (str_starts_with($path, '/v2')) {
            return 'Versi lama: '.$path;
        }

        return $path;
    }

    /**
     * @return array<int, array{nama: string, jumlah: int}>
     */
    private function sumber(Builder $query): array
    {
        $perHost = $query
            ->select('sumber_host', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('sumber_host')
            ->get();

        $perSumber = [];
        foreach ($perHost as $row) {
            $nama = $this->namaSumber($row->sumber_host);
            $perSumber[$nama] = ($perSumber[$nama] ?? 0) + (int) $row->jumlah;
        }
        arsort($perSumber);

        return collect($perSumber)
            ->map(fn (int $jumlah, string $nama) => ['nama' => $nama, 'jumlah' => $jumlah])
            ->values()
            ->take(6)
            ->all();
    }

    private function namaSumber(?string $host): string
    {
        if ($host === null || $host === '') {
            return 'Langsung';
        }

        foreach (self::SUMBER_DOMAIN as $domain => $nama) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return $nama;
            }
        }

        foreach (self::SUMBER_POTONGAN as $potongan => $nama) {
            if (str_contains($host, $potongan)) {
                return $nama;
            }
        }

        return preg_replace('/^www\./', '', $host) ?? $host;
    }

    /**
     * Pengunjung unik (hash harian) per negara dan per provinsi. Lokasi dari
     * header Cloudflare berdasarkan IP, jadi provinsi hanya perkiraan:
     * pengguna data seluler sering terbaca DKI Jakarta.
     *
     * @return array{indonesia: int, luarNegeri: int, tidakDiketahui: int, provinsi: array<int, array{nama: string, jumlah: int}>, negara: array<int, array{kode: string, nama: string, jumlah: int}>}
     */
    private function lokasi(Builder $query): array
    {
        $perNegara = $query->clone()
            ->select('negara', DB::raw('COUNT(DISTINCT pengunjung_hash) as jumlah'))
            ->groupBy('negara')
            ->get();

        $indonesia = (int) ($perNegara->firstWhere('negara', 'ID')->jumlah ?? 0);
        $tidakDiketahui = (int) ($perNegara->firstWhere('negara', null)->jumlah ?? 0);

        $provinsi = $query->clone()
            ->where('negara', 'ID')
            ->select('wilayah', DB::raw('COUNT(DISTINCT pengunjung_hash) as jumlah'))
            ->groupBy('wilayah')
            ->orderByDesc('jumlah')
            ->get()
            ->map(fn ($row) => [
                'nama' => $row->wilayah === null ? 'Provinsi tidak terdeteksi' : (self::NAMA_PROVINSI[$row->wilayah] ?? $row->wilayah),
                'jumlah' => (int) $row->jumlah,
            ])
            ->sortBy(fn (array $baris) => $baris['nama'] === 'Provinsi tidak terdeteksi' ? 1 : 0, SORT_REGULAR)
            ->values()
            ->take(10)
            ->all();

        $negara = $perNegara
            ->filter(fn ($row) => $row->negara !== null && $row->negara !== 'ID')
            ->sortByDesc('jumlah')
            ->map(fn ($row) => ['kode' => $row->negara, 'nama' => $this->namaNegara($row->negara), 'jumlah' => (int) $row->jumlah])
            ->values()
            ->take(6)
            ->all();

        return [
            'indonesia' => $indonesia,
            'luarNegeri' => (int) $perNegara->filter(fn ($row) => $row->negara !== null && $row->negara !== 'ID')->sum('jumlah'),
            'tidakDiketahui' => $tidakDiketahui,
            'provinsi' => $provinsi,
            'negara' => $negara,
        ];
    }

    private function namaNegara(string $kode): string
    {
        if (class_exists(\Locale::class)) {
            $nama = \Locale::getDisplayRegion('und-'.$kode, 'id');

            if (is_string($nama) && $nama !== '' && $nama !== $kode) {
                return $nama;
            }
        }

        return $kode;
    }

    private function persenPerubahan(int $sekarang, int $sebelumnya): ?float
    {
        if ($sebelumnya === 0) {
            return null;
        }

        return round(($sekarang - $sebelumnya) / $sebelumnya * 100, 1);
    }
}
