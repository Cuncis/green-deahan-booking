<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mengunduh foto produk hasil impor ke storage kita. Hanya dari CDN Shopee
 * (lihat ImporProdukHtmlService::gambarDiizinkan), tanpa mengikuti redirect,
 * dengan batas ukuran dan jenis file, lalu disimpan lewat FotoUploadService
 * seperti foto unggahan biasa.
 */
class UnduhGambarProdukService
{
    private const MAKS_BYTE = 4 * 1024 * 1024;

    /** @var array<string, string> */
    private const EKSTENSI = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function __construct(private readonly FotoUploadService $fotoUploadService) {}

    /**
     * URL foto yang tersimpan, atau null kalau URL tidak diizinkan atau unduhan gagal.
     */
    public function simpan(string $url, string $folder): ?string
    {
        if (! ImporProdukHtmlService::gambarDiizinkan($url)) {
            return null;
        }

        try {
            $respons = Http::timeout(10)->withOptions(['allow_redirects' => false])->get($url);
        } catch (Throwable) {
            return null;
        }

        $isi = $respons->body();

        if (! $respons->successful() || $isi === '' || strlen($isi) > self::MAKS_BYTE) {
            return null;
        }

        $info = @getimagesizefromstring($isi);
        $mime = is_array($info) ? ($info['mime'] ?? '') : '';

        if (! isset(self::EKSTENSI[$mime])) {
            return null;
        }

        $sementara = tempnam(sys_get_temp_dir(), 'impor');
        File::put($sementara, $isi);

        try {
            $file = new UploadedFile($sementara, Str::random(12).'.'.self::EKSTENSI[$mime], $mime, null, true);

            return $this->fotoUploadService->simpan($file, $folder);
        } finally {
            File::delete($sementara);
        }
    }
}
