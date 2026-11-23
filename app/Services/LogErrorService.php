<?php

namespace App\Services;

/**
 * Membaca file log Laravel (laravel.log atau laravel-YYYY-MM-DD.log) untuk
 * halaman Log Error di superadmin: pesan, lokasi, dan jejak tumpukan tiap
 * entri, dengan pencarian dan penyaringan level.
 *
 * File log bisa sangat besar, jadi hanya bagian akhirnya yang dibaca (batas
 * di config monitoring.log_maks_byte). Nilai yang mirip rahasia (kunci API,
 * token, kata sandi) disamarkan sebelum ditampilkan.
 */
class LogErrorService
{
    /** Urutan dari yang paling parah. */
    public const LEVEL = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    /** Level yang dihitung sebagai "error" (juga pemicu peringatan). */
    public const LEVEL_ERROR = ['emergency', 'alert', 'critical', 'error'];

    public const SEMUA = 'semua';

    public const ERROR_KE_ATAS = 'error_ke_atas';

    private const PENANDA_ENTRI = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2})?)\] (\w+)\.(\w+): /m';

    private const BATAS_DETAIL = 20000;

    /**
     * @return array{entri: array<int, array{id: string, waktu: string, lingkungan: string, level: string, pesan: string, kelas: string|null, lokasi: string|null, detail: string}>, terpotong: bool, ukuranLog: int}
     */
    public function cari(?string $kata, string $level = self::ERROR_KE_ATAS): array
    {
        ['entri' => $semua, 'terpotong' => $terpotong, 'ukuranLog' => $ukuran] = $this->bacaSemua();

        $kata = trim((string) $kata);
        $levelDiizinkan = match (true) {
            $level === self::SEMUA => self::LEVEL,
            $level === self::ERROR_KE_ATAS => self::LEVEL_ERROR,
            in_array($level, self::LEVEL, true) => [$level],
            default => self::LEVEL_ERROR,
        };

        $entri = array_values(array_filter($semua, function (array $item) use ($levelDiizinkan, $kata) {
            if (! in_array($item['level'], $levelDiizinkan, true)) {
                return false;
            }

            return $kata === '' || mb_stripos($item['pesan'].' '.$item['kelas'].' '.$item['lokasi'], $kata) !== false;
        }));

        return ['entri' => $entri, 'terpotong' => $terpotong, 'ukuranLog' => $ukuran];
    }

    /**
     * Semua entri dari file log terbaru, entri terbaru di urutan pertama.
     *
     * @return array{entri: array<int, array<string, mixed>>, terpotong: bool, ukuranLog: int}
     */
    private function bacaSemua(): array
    {
        $berkas = glob(rtrim((string) config('monitoring.log_folder'), '/').'/laravel*.log') ?: [];
        usort($berkas, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        $sisa = (int) config('monitoring.log_maks_byte');
        $ukuranTotal = 0;
        $terpotong = false;
        $entri = [];

        foreach ($berkas as $path) {
            $ukuran = (int) filesize($path);
            $ukuranTotal += $ukuran;

            if ($sisa <= 0) {
                $terpotong = $terpotong || $ukuran > 0;

                continue;
            }

            ['isi' => $isi, 'terpotong' => $dipotong] = $this->bacaEkor($path, $sisa);
            $sisa -= min($ukuran, $sisa);
            $terpotong = $terpotong || $dipotong;

            $entri = [...$entri, ...array_reverse($this->urai($isi, basename($path)))];
        }

        return ['entri' => $entri, 'terpotong' => $terpotong, 'ukuranLog' => $ukuranTotal];
    }

    /**
     * Isi file dari ujung belakang sebanyak $maks byte, mulai dari entri utuh pertama.
     *
     * @return array{isi: string, terpotong: bool}
     */
    private function bacaEkor(string $path, int $maks): array
    {
        $ukuran = (int) filesize($path);

        if ($ukuran === 0) {
            return ['isi' => '', 'terpotong' => false];
        }

        $mulai = max(0, $ukuran - $maks);
        $gagang = fopen($path, 'rb');

        if ($gagang === false) {
            return ['isi' => '', 'terpotong' => false];
        }

        fseek($gagang, $mulai);
        $isi = (string) stream_get_contents($gagang);
        fclose($gagang);

        if ($mulai > 0) {
            // Potongan di awal bisa berupa setengah entri: mulai dari penanda entri pertama.
            $isi = preg_match(self::PENANDA_ENTRI, $isi, $cocok, PREG_OFFSET_CAPTURE) ? substr($isi, $cocok[0][1]) : '';
        }

        return ['isi' => $isi, 'terpotong' => $mulai > 0];
    }

    /**
     * @return array<int, array{id: string, waktu: string, lingkungan: string, level: string, pesan: string, kelas: string|null, lokasi: string|null, detail: string}>
     */
    private function urai(string $isi, string $namaBerkas): array
    {
        if ($isi === '' || preg_match_all(self::PENANDA_ENTRI, $isi, $penanda, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === 0) {
            return [];
        }

        $hasil = [];

        foreach ($penanda as $urutan => $cocok) {
            $awalBadan = $cocok[0][1] + strlen($cocok[0][0]);
            $akhirBadan = $penanda[$urutan + 1][0][1] ?? strlen($isi);
            $badan = rtrim(substr($isi, $awalBadan, $akhirBadan - $awalBadan));

            $hasil[] = $this->entri($cocok[1][0], $cocok[2][0], strtolower($cocok[3][0]), $badan, "{$namaBerkas}:{$cocok[0][1]}");
        }

        return $hasil;
    }

    /**
     * @return array{id: string, waktu: string, lingkungan: string, level: string, pesan: string, kelas: string|null, lokasi: string|null, detail: string}
     */
    private function entri(string $waktu, string $lingkungan, string $level, string $badan, string $sumber): array
    {
        // Nama kelas di log ditulis dengan garis miring terbalik ganda (hasil JSON).
        $badan = str_replace('\\\\', '\\', $badan);
        $barisPertama = strtok($badan, "\n") ?: '';

        $pesan = $barisPertama;
        $posisiKonteks = strpos($pesan, ' {"');

        if ($posisiKonteks !== false) {
            $pesan = substr($pesan, 0, $posisiKonteks);
        }

        $pesan = trim(preg_replace('/\s\[\]$/', '', $pesan) ?? $pesan);
        $kelas = preg_match('/\[object\] \(([A-Za-z0-9_\\\\]+)\(code:/', $badan, $cocokKelas) ? $cocokKelas[1] : null;

        return [
            'id' => sha1($sumber),
            'waktu' => $waktu,
            'lingkungan' => $lingkungan,
            'level' => $level,
            'pesan' => $this->samarkan(mb_substr($pesan, 0, 500)),
            'kelas' => $kelas,
            'lokasi' => $kelas ? $this->lokasi($badan) : null,
            'detail' => $this->samarkan(mb_substr($badan, 0, self::BATAS_DETAIL)),
        ];
    }

    /**
     * "app/Services/MayarService.php:88" dari baris "... at /path/app/...:88)" di atas [stacktrace].
     */
    private function lokasi(string $badan): ?string
    {
        $sebelumJejak = explode("\n[stacktrace]", $badan)[0];

        if (preg_match_all('/ at (\/\S+?):(\d+)\)/', $sebelumJejak, $cocok, PREG_SET_ORDER) === 0) {
            return null;
        }

        $terakhir = end($cocok);

        return str_replace(base_path().'/', '', $terakhir[1]).':'.$terakhir[2];
    }

    /**
     * Menyamarkan nilai yang tampak seperti rahasia: token Bearer, kunci Biteship,
     * dan pasangan nama=nilai untuk api key, token, secret, password, authorization.
     */
    public function samarkan(string $teks): string
    {
        $teks = preg_replace('/Bearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [disamarkan]', $teks) ?? $teks;
        $teks = preg_replace('/biteship_(?:test|live)\.[A-Za-z0-9._-]+/', 'biteship_[disamarkan]', $teks) ?? $teks;

        return preg_replace_callback(
            '/((?:api[_-]?key|token|secret|password|authorization)["\']?\s*[:=]\s*["\']?)([^\s"\',}&]+)/i',
            fn (array $cocok) => $cocok[1].'[disamarkan]',
            $teks,
        ) ?? $teks;
    }
}
