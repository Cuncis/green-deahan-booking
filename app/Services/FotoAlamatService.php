<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Alamat (URL) foto yang tersimpan di database: dikelompokkan per host untuk
 * pemeriksaan, dan diganti awalannya (misalnya saat pindah ke domain publik
 * Cloudflare R2). URL foto disimpan utuh saat unggah, jadi mengganti
 * AWS_URL di .env tidak memperbaiki foto yang sudah tersimpan.
 */
class FotoAlamatService
{
    /**
     * Tabel dan kolom yang menyimpan alamat foto, beserta kunci utamanya.
     *
     * @var array<int, array{tabel: string, kolom: string, kunci: string, saring: array<string, string>}>
     */
    public const KOLOM = [
        ['tabel' => 'artikel', 'kolom' => 'foto_url', 'kunci' => 'id', 'saring' => []],
        ['tabel' => 'galeri_items', 'kolom' => 'foto_url', 'kunci' => 'id', 'saring' => []],
        ['tabel' => 'lapangan', 'kolom' => 'foto_url', 'kunci' => 'id', 'saring' => []],
        ['tabel' => 'tenants', 'kolom' => 'logo_url', 'kunci' => 'id', 'saring' => []],
        ['tabel' => 'products', 'kolom' => 'image_path', 'kunci' => 'id', 'saring' => []],
        ['tabel' => 'product_images', 'kolom' => 'path', 'kunci' => 'id', 'saring' => []],
        ['tabel' => 'pengaturan_situs', 'kolom' => 'nilai', 'kunci' => 'kunci', 'saring' => ['kunci' => 'og_image_url']],
    ];

    /**
     * Semua alamat foto yang berupa URL lengkap, per sumber (tabel.kolom).
     *
     * @return array<int, array{sumber: string, url: string}>
     */
    public function semuaAlamat(): array
    {
        $hasil = [];

        foreach (self::KOLOM as $definisi) {
            foreach ($this->nilai($definisi) as $baris) {
                if (preg_match('#^https?://#i', (string) $baris->nilai)) {
                    $hasil[] = ['sumber' => "{$definisi['tabel']}.{$definisi['kolom']}", 'url' => (string) $baris->nilai];
                }
            }
        }

        return $hasil;
    }

    /**
     * Foto yang alamatnya memuat /storage/ (disimpan di disk server, bukan R2),
     * termasuk alamat relatif, dengan jalur file di dalam disk.
     *
     * @return array<int, array{sumber: string, url: string, path: string}>
     */
    public function jalurStorage(): array
    {
        $hasil = [];

        foreach (self::KOLOM as $definisi) {
            foreach ($this->nilai($definisi) as $baris) {
                $nilai = (string) $baris->nilai;
                $posisi = strpos($nilai, '/storage/');

                if ($posisi === false) {
                    continue;
                }

                $path = ltrim(rawurldecode((string) parse_url(substr($nilai, $posisi + strlen('/storage/')), PHP_URL_PATH)), '/');

                if ($path !== '' && ! str_contains($path, '..')) {
                    $hasil[] = ['sumber' => "{$definisi['tabel']}.{$definisi['kolom']}", 'url' => $nilai, 'path' => $path];
                }
            }
        }

        return $hasil;
    }

    /**
     * Jumlah alamat per host beserta contoh dan asal tabelnya.
     *
     * @return array<string, array{jumlah: int, contoh: array<int, string>, sumber: array<string, int>}>
     */
    public function ringkasanHost(): array
    {
        $ringkasan = [];

        foreach ($this->semuaAlamat() as $alamat) {
            $host = (string) parse_url($alamat['url'], PHP_URL_HOST).(parse_url($alamat['url'], PHP_URL_PORT) ? ':'.parse_url($alamat['url'], PHP_URL_PORT) : '');
            $ringkasan[$host] ??= ['jumlah' => 0, 'contoh' => [], 'sumber' => []];
            $ringkasan[$host]['jumlah']++;
            $ringkasan[$host]['sumber'][$alamat['sumber']] = ($ringkasan[$host]['sumber'][$alamat['sumber']] ?? 0) + 1;

            if (count($ringkasan[$host]['contoh']) < 3 && ! in_array($alamat['url'], $ringkasan[$host]['contoh'], true)) {
                $ringkasan[$host]['contoh'][] = $alamat['url'];
            }
        }

        ksort($ringkasan);

        return $ringkasan;
    }

    /**
     * Ganti awalan alamat dari $dari ke $ke. Tanpa $jalankan hanya menghitung.
     *
     * @return array<string, int> jumlah baris per tabel.kolom yang cocok
     */
    public function ganti(string $dari, string $ke, bool $jalankan): array
    {
        $hasil = [];

        DB::transaction(function () use ($dari, $ke, $jalankan, &$hasil) {
            foreach (self::KOLOM as $definisi) {
                $cocok = 0;

                foreach ($this->nilai($definisi) as $baris) {
                    if (! str_starts_with((string) $baris->nilai, $dari)) {
                        continue;
                    }

                    $cocok++;

                    if ($jalankan) {
                        DB::table($definisi['tabel'])->where($definisi['kunci'], $baris->kunci)->update([$definisi['kolom'] => $ke.substr((string) $baris->nilai, strlen($dari))]);
                    }
                }

                if ($cocok > 0) {
                    $hasil["{$definisi['tabel']}.{$definisi['kolom']}"] = $cocok;
                }
            }
        });

        return $hasil;
    }

    /**
     * @param  array{tabel: string, kolom: string, kunci: string, saring: array<string, string>}  $definisi
     * @return Collection<int, object{kunci: mixed, nilai: mixed}>
     */
    private function nilai(array $definisi)
    {
        $query = DB::table($definisi['tabel'])->whereNotNull($definisi['kolom'])->where($definisi['kolom'], '!=', '');

        foreach ($definisi['saring'] as $kolom => $isi) {
            $query->where($kolom, $isi);
        }

        return $query->get([$definisi['kunci'].' as kunci', $definisi['kolom'].' as nilai']);
    }
}
