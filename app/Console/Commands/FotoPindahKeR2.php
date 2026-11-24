<?php

namespace App\Console\Commands;

use App\Services\FotoAlamatService;
use App\Services\FotoUploadService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('foto:pindah-ke-r2 {--jalankan : Unggah ke R2 dan ganti alamat di database (tanpa ini hanya menghitung)}')]
#[Description('Pindahkan foto di disk server (storage/app/public atau private) ke Cloudflare R2 lalu ganti alamatnya di database')]
class FotoPindahKeR2 extends Command
{
    public function __construct(private readonly FotoAlamatService $alamat)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (FotoUploadService::namaDisk() !== 's3') {
            $this->error('R2 belum aktif. Isi variabel R2 di .env dan set FILESYSTEM_DISK=s3 dulu, lalu jalankan `php artisan config:clear`.');

            return self::FAILURE;
        }

        $jalankan = (bool) $this->option('jalankan');
        $r2 = Storage::disk('s3');
        $publik = Storage::disk('public');
        $privat = Storage::disk('local');

        // Satu file bisa dipakai beberapa baris (misalnya foto utama produk dan galerinya).
        $perJalur = [];

        foreach ($this->alamat->jalurStorage() as $foto) {
            $perJalur[$foto['path']]['url'][] = $foto['url'];
            $perJalur[$foto['path']]['sumber'][] = $foto['sumber'];
        }

        $this->info('Foto di disk server yang dipakai database: '.count($perJalur));

        $peta = [];
        $sudahDiR2 = 0;
        $diunggah = 0;
        $hilang = [];
        $gagal = [];

        foreach ($perJalur as $path => $data) {
            try {
                $ada = $r2->exists($path);

                if (! $ada) {
                    $sumber = $publik->exists($path) ? $publik : ($privat->exists($path) ? $privat : null);

                    if ($sumber === null) {
                        $hilang[$path] = $data['sumber'][0];

                        continue;
                    }

                    if ($jalankan) {
                        $r2->writeStream($path, $sumber->readStream($path));

                        if (! $r2->exists($path)) {
                            $gagal[$path] = 'tidak ditemukan di R2 setelah diunggah';

                            continue;
                        }
                    }

                    $diunggah++;
                } else {
                    $sudahDiR2++;
                }
            } catch (Throwable $galat) {
                $gagal[$path] = mb_substr($galat->getMessage(), 0, 200);

                continue;
            }

            foreach ($data['url'] as $url) {
                $peta[$url] = $r2->url($path);
            }
        }

        $this->line("Sudah ada di R2: {$sudahDiR2}");
        $this->line(($jalankan ? 'Diunggah ke R2: ' : 'Akan diunggah ke R2: ').$diunggah);

        if ($hilang !== []) {
            $this->error('Tidak ditemukan di disk server maupun R2 ('.count($hilang).'), unggah ulang fotonya lewat admin:');

            foreach (array_slice($hilang, 0, 20, true) as $path => $sumber) {
                $this->line("  {$path} ({$sumber})");
            }
        }

        foreach (array_slice($gagal, 0, 20, true) as $path => $pesan) {
            $this->error("Gagal memindahkan {$path}: {$pesan}");
        }

        if (! $jalankan) {
            $this->warn(count($peta).' alamat akan diganti ke R2. Ini baru hitungan: cadangkan database lalu jalankan lagi dengan --jalankan.');

            return $hilang === [] && $gagal === [] ? self::SUCCESS : self::FAILURE;
        }

        $diubah = $this->alamat->gantiPersis($peta);
        $this->info("{$diubah} alamat foto di database diganti ke R2. File di disk server tidak dihapus; hapus manual setelah foto di situs dicek.");

        return $hilang === [] && $gagal === [] ? self::SUCCESS : self::FAILURE;
    }
}
