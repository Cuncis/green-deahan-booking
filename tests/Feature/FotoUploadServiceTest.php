<?php

namespace Tests\Feature;

use App\Services\FotoUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoUploadServiceTest extends TestCase
{
    public function test_simpan_di_disk_public_pakai_asset_url(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('lapangan.jpg');

        $url = (new FotoUploadService)->simpan($file, 'lapangan');

        $this->assertStringStartsWith(url('/storage/lapangan/'), $url);
        Storage::disk('public')->assertExists('lapangan/'.basename($url));
    }

    /**
     * Storage::fake('s3') tidak mereplikasi bentuk URL S3/R2 sungguhan
     * (selalu balik pola lokal generik terlepas AWS_URL di-set apa),
     * jadi yang dibuktikan di sini BUKAN bentuk persis URL-nya, tapi bahwa
     * file BENAR tersimpan di disk s3 (bukan diam-diam tetap ke disk
     * public), sesuai FILESYSTEM_DISK yang dikonfigurasi.
     */
    public function test_simpan_dengan_filesystem_disk_s3_menyimpan_ke_disk_s3_bukan_public(): void
    {
        config(['filesystems.default' => 's3']);
        Storage::fake('public');
        Storage::fake('s3');

        $file = UploadedFile::fake()->image('lapangan.jpg');

        $url = (new FotoUploadService)->simpan($file, 'lapangan');
        $path = 'lapangan/'.basename($url);

        Storage::disk('s3')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_hapus_null_tidak_error(): void
    {
        (new FotoUploadService)->hapus(null);

        $this->addToAssertionCount(1);
    }

    public function test_hapus_di_disk_public_menghapus_file(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('lapangan.jpg');
        $url = (new FotoUploadService)->simpan($file, 'lapangan');

        (new FotoUploadService)->hapus($url);

        $path = 'lapangan/'.basename($url);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_hapus_di_disk_s3_menghapus_file(): void
    {
        config(['filesystems.default' => 's3', 'filesystems.disks.s3.url' => 'https://pub-test.r2.dev']);
        Storage::fake('s3');

        $file = UploadedFile::fake()->image('lapangan.jpg');
        $url = (new FotoUploadService)->simpan($file, 'lapangan');

        (new FotoUploadService)->hapus($url);

        $path = 'lapangan/'.basename($url);
        Storage::disk('s3')->assertMissing($path);
    }
}
