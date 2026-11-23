<?php

namespace App\Console\Commands;

use App\Services\FotoAlamatService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

#[Signature('foto:periksa {--tanpa-uji-tulis : Lewati uji tulis dan baca satu file kecil di disk foto}')]
#[Description('Periksa penyimpanan foto (disk, Cloudflare R2, AWS_URL) dan alamat foto di database')]
class FotoPeriksa extends Command
{
    public function __construct(private readonly FotoAlamatService $alamat)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $disk = (string) config('filesystems.default');
        $masalah = 0;

        $this->info("Disk foto (FILESYSTEM_DISK): {$disk}");

        $masalah += $disk === 's3' ? $this->periksaR2() : $this->periksaDiskLokal($disk);

        if (! $this->option('tanpa-uji-tulis')) {
            $masalah += $this->ujiTulisBaca($disk);
        }

        $masalah += $this->periksaDatabase();

        $this->newLine();
        $masalah === 0
            ? $this->info('Tidak ada masalah yang terdeteksi.')
            : $this->warn("{$masalah} hal perlu diperbaiki, lihat tanda [X] di atas.");

        return $masalah === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function periksaR2(): int
    {
        $masalah = 0;
        $endpoint = (string) config('filesystems.disks.s3.endpoint');
        $url = (string) config('filesystems.disks.s3.url');

        $this->line('  Bucket: '.(config('filesystems.disks.s3.bucket') ?: '(kosong)'));
        $this->line('  Endpoint (AWS_ENDPOINT): '.($endpoint ?: '(kosong)'));
        $this->line('  Alamat publik (AWS_URL): '.($url ?: '(kosong)'));

        foreach ([
            'AWS_BUCKET belum diisi.' => ! config('filesystems.disks.s3.bucket'),
            'AWS_ACCESS_KEY_ID atau AWS_SECRET_ACCESS_KEY belum diisi.' => ! config('filesystems.disks.s3.key') || ! config('filesystems.disks.s3.secret'),
            'AWS_ENDPOINT belum diisi (untuk R2: https://<account_id>.r2.cloudflarestorage.com).' => $endpoint === '',
            'AWS_URL kosong: alamat foto akan dibangun dari endpoint R2, yang bukan alamat publik, jadi fotonya tidak tampil. Isi dengan domain publik bucket (r2.dev atau domain sendiri).' => $url === '',
            'AWS_URL memakai alamat API R2 (r2.cloudflarestorage.com) yang tidak publik. Pakai domain publik bucket (r2.dev atau domain sendiri).' => str_contains($url, 'r2.cloudflarestorage.com'),
            'AWS_URL memakai http, padahal situs https: browser memblokir fotonya (mixed content).' => str_starts_with($url, 'http://') && str_starts_with((string) config('app.url'), 'https://'),
        ] as $pesan => $salah) {
            if ($salah) {
                $this->error("  [X] {$pesan}");
                $masalah++;
            }
        }

        return $masalah;
    }

    private function periksaDiskLokal(string $disk): int
    {
        if ($disk !== 'public') {
            return 0;
        }

        $this->line('  Foto disimpan di server ini (storage/app/public), bukan di Cloudflare R2.');

        if (! is_link(public_path('storage')) && ! is_dir(public_path('storage'))) {
            $this->error('  [X] public/storage belum ada: jalankan `php artisan storage:link`.');

            return 1;
        }

        return 0;
    }

    /**
     * Tulis satu file kecil lewat disk foto, buka lewat alamat publiknya, lalu hapus.
     */
    private function ujiTulisBaca(string $disk): int
    {
        $this->newLine();
        $this->info('Uji tulis dan baca satu file kecil:');
        $path = 'foto-uji/'.Str::random(10).'.txt';

        try {
            $berhasil = Storage::disk($disk)->put($path, 'uji foto');
        } catch (Throwable $galat) {
            $this->error('  [X] Gagal menulis ke disk: '.Str::limit($galat->getMessage(), 300));

            return 1;
        }

        if (! $berhasil) {
            $this->error('  [X] Gagal menulis ke disk (tanpa pesan). Periksa kunci, bucket, dan izin tulis.');

            return 1;
        }

        $this->line('  Tulis ke disk: berhasil.');
        $url = $disk === 's3' ? Storage::disk('s3')->url($path) : rtrim((string) config('app.url'), '/').'/storage/'.$path;
        $masalah = 0;

        try {
            $status = Http::timeout(10)->get($url)->status();
            $this->line("  Buka lewat {$url}: HTTP {$status}");

            if ($status !== 200) {
                $this->error('  [X] File tersimpan tapi tidak bisa dibuka publik. Untuk R2: aktifkan akses publik bucket (r2.dev atau domain sendiri) dan cocokkan AWS_URL.');
                $masalah = 1;
            }
        } catch (Throwable $galat) {
            $this->error('  [X] Alamat publik tidak bisa dijangkau: '.Str::limit($galat->getMessage(), 200));
            $masalah = 1;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (Throwable) {
            $this->warn("  File uji {$path} tidak terhapus, hapus manual.");
        }

        return $masalah;
    }

    private function periksaDatabase(): int
    {
        $this->newLine();
        $this->info('Alamat foto di database, per host:');
        $ringkasan = $this->alamat->ringkasanHost();

        if ($ringkasan === []) {
            $this->line('  Tidak ada alamat foto berupa URL lengkap.');

            return 0;
        }

        $masalah = 0;

        foreach ($ringkasan as $host => $data) {
            $rusak = 0;
            $diperiksa = 0;

            foreach ($data['contoh'] as $url) {
                $diperiksa++;

                try {
                    $status = Http::timeout(10)->head($url)->status();
                } catch (Throwable) {
                    $status = 0;
                }

                $rusak += $status === 200 ? 0 : 1;
            }

            $asal = collect($data['sumber'])->map(fn (int $jumlah, string $sumber) => "{$sumber}: {$jumlah}")->implode(', ');
            $this->line("  {$host}: {$data['jumlah']} foto ({$asal}), contoh yang bisa dibuka {$this->bagian($diperiksa - $rusak, $diperiksa)}");

            if ($rusak > 0) {
                $this->error("  [X] {$host}: ada contoh foto yang tidak bisa dibuka, mis. {$data['contoh'][0]}");
                $masalah++;
            }
        }

        if (count($ringkasan) > 1) {
            $this->warn('  Foto tersebar di lebih dari satu host. Ganti awalan lama dengan `php artisan foto:ganti-alamat <awalan lama> <awalan baru>` setelah file-nya dipindah.');
        }

        return $masalah;
    }

    private function bagian(int $baik, int $total): string
    {
        return "{$baik}/{$total}";
    }
}
