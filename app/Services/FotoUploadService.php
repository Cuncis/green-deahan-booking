<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Simpan/hapus foto upload (lapangan, galeri, logo tenant, artikel), dipakai
 * LapanganAdminController, GaleriAdminController, SettingsAdminController,
 * dan ArtikelAdminController. Disk dibaca dari FILESYSTEM_DISK
 * (config('filesystems.default')), bukan di-hardcode 'public', supaya
 * ganti ke Cloudflare R2 di production cukup ganti FILESYSTEM_DISK=s3 di
 * .env, tidak perlu ubah kode.
 */
class FotoUploadService
{
    public function simpan(UploadedFile $file, string $folder): string
    {
        $disk = config('filesystems.default');
        $path = $file->store($folder, $disk);

        // Disk S3/R2 tidak melempar galat saat gagal, store() hanya mengembalikan false.
        // Tanpa pemeriksaan ini, alamat foto yang rusak tetap tersimpan di database.
        if ($path === false) {
            throw new RuntimeException('Foto gagal disimpan ke penyimpanan ('.$disk.'). Periksa pengaturan FILESYSTEM_DISK dan Cloudflare R2 di .env.');
        }

        if ($disk === 's3') {
            // URL R2 sama sekali beda host dari domain tenant, jadi HARUS
            // lewat Storage::url() (dibangun dari AWS_URL), bukan asset().
            return Storage::disk('s3')->url($path);
        }

        // Sengaja pakai asset() (resolve dari host request saat ini), BUKAN
        // Storage::disk('public')->url() yang selalu balik ke APP_URL
        // statis. Tenant diakses dari macam-macam domain (subdomain,
        // custom domain), jadi URL foto yang di-hardcode ke satu domain
        // akan rusak/404 di domain tenant manapun selain APP_URL itu
        // sendiri.
        return asset('storage/'.$path);
    }

    public function hapus(?string $url): void
    {
        if (! $url) {
            return;
        }

        $disk = config('filesystems.default');

        if ($disk === 's3') {
            $base = Storage::disk('s3')->url('');
            $path = Str::after($url, $base);

            if ($path && $path !== $url) {
                Storage::disk('s3')->delete($path);
            }

            return;
        }

        $path = Str::after($url, '/storage/');

        if ($path && $path !== $url) {
            Storage::disk('public')->delete($path);
        }
    }
}
