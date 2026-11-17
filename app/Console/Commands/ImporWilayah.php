<?php

namespace App\Console\Commands;

use App\Models\Wilayah;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi tabel wilayah dari database/data/wilayah.tsv.gz: kode dan nama
 * wilayah Kemendagri (github.com/cahyadsn/wilayah) dan kode pos per kelurahan
 * (github.com/cahyadsn/wilayah_kodepos), keduanya berlisensi MIT. Aman dijalankan
 * ulang: data yang sama diperbarui, bukan digandakan.
 */
#[Signature('wilayah:impor {--berkas= : Lokasi berkas .tsv.gz lain}')]
#[Description('Impor data provinsi, kabupaten/kota, kecamatan, dan kelurahan untuk dropdown alamat checkout')]
class ImporWilayah extends Command
{
    private const UKURAN_BATCH = 1000;

    public function handle(): int
    {
        $berkas = $this->option('berkas') ?: database_path('data/wilayah.tsv.gz');
        $handle = is_file($berkas) ? gzopen($berkas, 'rb') : false;

        if ($handle === false) {
            $this->error("Berkas {$berkas} tidak bisa dibuka.");

            return self::FAILURE;
        }

        $batch = [];
        $jumlah = 0;

        DB::transaction(function () use ($handle, &$batch, &$jumlah) {
            while (($baris = gzgets($handle)) !== false) {
                $kolom = explode("\t", rtrim($baris, "\r\n"));

                if (count($kolom) < 2 || ! preg_match('/^\d{2}(\.\d{2}){0,2}(\.\d{4})?$/', $kolom[0])) {
                    continue;
                }

                $batch[] = [
                    'kode' => $kolom[0],
                    'nama' => $kolom[1],
                    'level' => Wilayah::levelDari($kolom[0]),
                    'induk' => Wilayah::indukDari($kolom[0]),
                    'kodepos' => ($kolom[2] ?? '') !== '' ? $kolom[2] : null,
                ];

                if (count($batch) === self::UKURAN_BATCH) {
                    $jumlah += $this->simpan($batch);
                    $batch = [];
                }
            }

            $jumlah += $this->simpan($batch);
        });

        gzclose($handle);

        $this->info("{$jumlah} wilayah diimpor.");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     */
    private function simpan(array $batch): int
    {
        if ($batch === []) {
            return 0;
        }

        Wilayah::query()->upsert($batch, ['kode'], ['nama', 'level', 'induk', 'kodepos']);

        return count($batch);
    }
}
