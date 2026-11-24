<?php

namespace Tests\Feature;

use App\Services\FotoUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Penyimpanan foto: unggahan yang gagal tidak boleh menyimpan alamat rusak,
 * disk selain s3 selalu jatuh ke "public" (bukan "local" yang tertutup), dan
 * alamat foto R2 memuat folder bucket dari AWS_ROOT.
 */
class FotoPenyimpananTest extends TestCase
{
    public function test_unggahan_yang_gagal_disimpan_melempar_galat_bukan_menyimpan_alamat_rusak(): void
    {
        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3' => [
                'driver' => 's3', 'key' => 'kunci', 'secret' => 'rahasia', 'region' => 'auto', 'bucket' => 'bucket-uji',
                'url' => 'https://foto.contoh.test', 'endpoint' => 'http://127.0.0.1:1', 'use_path_style_endpoint' => true,
                'throw' => false, 'report' => false, 'retries' => 0,
            ],
        ]);
        Storage::forgetDisk('s3');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Foto gagal disimpan');

        app(FotoUploadService::class)->simpan(UploadedFile::fake()->image('a.jpg'), 'produk');
    }

    public function test_unggahan_di_disk_lokal_tetap_menghasilkan_alamat_dari_host_saat_ini(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);

        $url = app(FotoUploadService::class)->simpan(UploadedFile::fake()->image('a.jpg'), 'produk');

        $this->assertStringContainsString('/storage/produk/', $url);
    }

    public function test_disk_local_bawaan_laravel_tetap_menyimpan_foto_di_disk_public(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $url = app(FotoUploadService::class)->simpan(UploadedFile::fake()->image('a.jpg'), 'produk');

        $path = str($url)->after('/storage/')->toString();
        Storage::disk('public')->assertExists($path);
        Storage::disk('local')->assertMissing($path);

        app(FotoUploadService::class)->hapus($url);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_nama_disk_s3_hanya_kalau_r2_aktif(): void
    {
        config(['filesystems.default' => 's3']);
        $this->assertSame('s3', FotoUploadService::namaDisk());

        foreach (['local', 'public', 'apa-saja'] as $nama) {
            config(['filesystems.default' => $nama]);
            $this->assertSame('public', FotoUploadService::namaDisk());
        }
    }

    public function test_alamat_foto_memuat_folder_bucket_dari_aws_root(): void
    {
        $atur = function (string $root): void {
            config(['filesystems.disks.s3' => [
                'driver' => 's3', 'key' => 'k', 'secret' => 's', 'region' => 'auto', 'bucket' => 'libra-digital',
                'url' => 'https://cdn.libradigital.id', 'endpoint' => 'https://akun.r2.cloudflarestorage.com', 'use_path_style_endpoint' => true,
                'root' => $root, 'throw' => false, 'report' => false,
            ]]);
            Storage::forgetDisk('s3');
        };

        $atur('green-deahan');
        $this->assertSame('https://cdn.libradigital.id/green-deahan/produk/a.jpg', Storage::disk('s3')->url('produk/a.jpg'));
        $this->assertSame('https://cdn.libradigital.id/green-deahan/', Storage::disk('s3')->url(''));

        $atur('');
        $this->assertSame('https://cdn.libradigital.id/produk/a.jpg', Storage::disk('s3')->url('produk/a.jpg'));
    }
}
