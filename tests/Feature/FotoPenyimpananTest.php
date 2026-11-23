<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\GaleriItem;
use App\Models\PengaturanSitus;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Tenant;
use App\Services\FotoAlamatService;
use App\Services\FotoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Penyimpanan foto (Cloudflare R2 di produksi): unggahan yang gagal tidak boleh
 * menyimpan alamat rusak, `foto:periksa` mendiagnosis disk dan alamat di
 * database, dan `foto:ganti-alamat` mengganti awalan alamat foto.
 */
class FotoPenyimpananTest extends TestCase
{
    use RefreshDatabase;

    // --- Unggahan ---

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

    // --- Alamat di database ---

    private function isiAlamat(): void
    {
        Artikel::factory()->create(['foto_url' => 'https://lama.test/storage/artikel/a.jpg']);
        GaleriItem::factory()->create(['foto_url' => 'https://cdn.libradigital.id/site-assets/interlock1.jpg']);
        Tenant::factory()->create(['logo_url' => 'https://lama.test/storage/logo/b.png']);
        $produk = Product::factory()->create(['image_path' => 'https://lama.test/storage/produk/c.jpg']);
        ProductImage::factory()->create(['product_id' => $produk->id, 'path' => 'https://lama.test/storage/produk/c.jpg']);
        ProductImage::factory()->create(['product_id' => $produk->id, 'path' => '/storage/produk/relatif.jpg']);
        PengaturanSitus::create(['kunci' => 'og_image_url', 'nilai' => 'https://lama.test/storage/og.jpg']);
        PengaturanSitus::create(['kunci' => 'nama_situs', 'nilai' => 'https://lama.test/storage/bukan-foto']);
    }

    public function test_ringkasan_host_mengelompokkan_alamat_lengkap_dan_mengabaikan_yang_relatif(): void
    {
        $this->isiAlamat();

        $ringkasan = app(FotoAlamatService::class)->ringkasanHost();

        $this->assertSame(['cdn.libradigital.id', 'lama.test'], array_keys($ringkasan));
        $this->assertSame(1, $ringkasan['cdn.libradigital.id']['jumlah']);
        $this->assertSame(5, $ringkasan['lama.test']['jumlah']);
        $this->assertSame(['artikel.foto_url' => 1, 'tenants.logo_url' => 1, 'products.image_path' => 1, 'product_images.path' => 1, 'pengaturan_situs.nilai' => 1], $ringkasan['lama.test']['sumber']);
        $this->assertLessThanOrEqual(3, count($ringkasan['lama.test']['contoh']));
    }

    public function test_ganti_tanpa_menjalankan_hanya_menghitung(): void
    {
        $this->isiAlamat();

        $hasil = app(FotoAlamatService::class)->ganti('https://lama.test/storage', 'https://foto.baru.test', false);

        $this->assertSame(['artikel.foto_url' => 1, 'tenants.logo_url' => 1, 'products.image_path' => 1, 'product_images.path' => 1, 'pengaturan_situs.nilai' => 1], $hasil);
        $this->assertSame(5, DB::table('artikel')->where('foto_url', 'like', 'https://lama.test/%')->count() + DB::table('tenants')->where('logo_url', 'like', 'https://lama.test/%')->count() + DB::table('products')->where('image_path', 'like', 'https://lama.test/%')->count() + DB::table('product_images')->where('path', 'like', 'https://lama.test/%')->count() + DB::table('pengaturan_situs')->where('kunci', 'og_image_url')->where('nilai', 'like', 'https://lama.test/%')->count());
    }

    public function test_ganti_menjalankan_hanya_awalan_yang_cocok_dan_tidak_menyentuh_data_lain(): void
    {
        $this->isiAlamat();

        $hasil = app(FotoAlamatService::class)->ganti('https://lama.test/storage', 'https://foto.baru.test', true);

        $this->assertSame(5, array_sum($hasil));
        $this->assertSame('https://foto.baru.test/artikel/a.jpg', Artikel::first()->foto_url);
        $this->assertSame('https://foto.baru.test/logo/b.png', Tenant::orderByDesc('id')->first()->logo_url);
        $this->assertSame('https://foto.baru.test/produk/c.jpg', Product::first()->image_path);
        $this->assertSame(['https://foto.baru.test/produk/c.jpg', '/storage/produk/relatif.jpg'], ProductImage::orderBy('id')->pluck('path')->all());
        $this->assertSame('https://foto.baru.test/og.jpg', PengaturanSitus::find('og_image_url')->nilai);
        $this->assertSame('https://lama.test/storage/bukan-foto', PengaturanSitus::find('nama_situs')->nilai);
        $this->assertSame('https://cdn.libradigital.id/site-assets/interlock1.jpg', GaleriItem::first()->foto_url);
    }

    public function test_perintah_ganti_alamat_menghitung_dulu_lalu_menerapkan_dengan_jalankan(): void
    {
        Artikel::factory()->create(['foto_url' => 'https://lama.test/storage/artikel/a.jpg']);

        $this->artisan('foto:ganti-alamat', ['dari' => 'https://lama.test/storage/', 'ke' => 'https://foto.baru.test/'])
            ->expectsOutputToContain('artikel.foto_url: 1 baris')
            ->expectsOutputToContain('tambahkan --jalankan')
            ->assertSuccessful();
        $this->assertSame('https://lama.test/storage/artikel/a.jpg', Artikel::first()->foto_url);

        $this->artisan('foto:ganti-alamat', ['dari' => 'https://lama.test/storage', 'ke' => 'https://foto.baru.test', '--jalankan' => true])
            ->expectsOutputToContain('1 alamat diganti')
            ->assertSuccessful();
        $this->assertSame('https://foto.baru.test/artikel/a.jpg', Artikel::first()->foto_url);
    }

    public function test_perintah_ganti_alamat_menolak_masukan_yang_salah(): void
    {
        $this->artisan('foto:ganti-alamat', ['dari' => 'https://sama.test', 'ke' => 'https://sama.test/'])->expectsOutputToContain('tidak boleh sama')->assertFailed();
        $this->artisan('foto:ganti-alamat', ['dari' => '/storage', 'ke' => 'https://baru.test'])->expectsOutputToContain('http://')->assertFailed();
        $this->artisan('foto:ganti-alamat', ['dari' => 'https://tidak-ada.test', 'ke' => 'https://baru.test'])->expectsOutputToContain('Tidak ada alamat foto')->assertSuccessful();
    }

    // --- foto:periksa ---

    public function test_periksa_menunjuk_konfigurasi_r2_yang_salah(): void
    {
        config(['filesystems.default' => 's3', 'filesystems.disks.s3.bucket' => 'foto', 'filesystems.disks.s3.key' => 'k', 'filesystems.disks.s3.secret' => 's',
            'filesystems.disks.s3.endpoint' => 'https://akun.r2.cloudflarestorage.com', 'filesystems.disks.s3.url' => '']);

        $this->artisan('foto:periksa', ['--tanpa-uji-tulis' => true])
            ->expectsOutputToContain('AWS_URL kosong')
            ->assertFailed();

        config(['filesystems.disks.s3.url' => 'https://akun.r2.cloudflarestorage.com/foto']);
        $this->artisan('foto:periksa', ['--tanpa-uji-tulis' => true])->expectsOutputToContain('tidak publik')->assertFailed();

        config(['app.url' => 'https://greendeahan.com', 'filesystems.disks.s3.url' => 'http://foto.contoh.test']);
        $this->artisan('foto:periksa', ['--tanpa-uji-tulis' => true])->expectsOutputToContain('mixed content')->assertFailed();
    }

    public function test_periksa_r2_yang_benar_tidak_melaporkan_masalah_konfigurasi(): void
    {
        config(['app.url' => 'https://greendeahan.com', 'filesystems.default' => 's3', 'filesystems.disks.s3.bucket' => 'foto', 'filesystems.disks.s3.key' => 'k', 'filesystems.disks.s3.secret' => 's',
            'filesystems.disks.s3.endpoint' => 'https://akun.r2.cloudflarestorage.com', 'filesystems.disks.s3.url' => 'https://foto.greendeahan.com']);

        $this->artisan('foto:periksa', ['--tanpa-uji-tulis' => true])->expectsOutputToContain('Tidak ada masalah')->assertSuccessful();
    }

    public function test_periksa_uji_tulis_baca_berhasil_dan_menghapus_file_uji(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public', 'app.url' => 'https://greendeahan.com']);
        Http::fake(['greendeahan.com/storage/foto-uji/*' => Http::response('uji foto', 200)]);

        $this->artisan('foto:periksa')->expectsOutputToContain('Tulis ke disk: berhasil')->expectsOutputToContain('HTTP 200')->assertSuccessful();

        $this->assertSame([], Storage::disk('public')->allFiles('foto-uji'));
    }

    public function test_periksa_melaporkan_file_tersimpan_tapi_tidak_bisa_dibuka_publik(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public', 'app.url' => 'https://greendeahan.com']);
        Http::fake(['greendeahan.com/storage/foto-uji/*' => Http::response('', 404)]);

        $this->artisan('foto:periksa')->expectsOutputToContain('tidak bisa dibuka publik')->assertFailed();
    }

    public function test_periksa_memeriksa_contoh_alamat_di_database_per_host(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);
        Artikel::factory()->create(['foto_url' => 'https://rusak.test/storage/a.jpg']);
        GaleriItem::factory()->create(['foto_url' => 'https://baik.test/b.jpg']);
        Http::fake(['rusak.test/*' => Http::response('', 403), 'baik.test/*' => Http::response('', 200)]);

        $this->artisan('foto:periksa', ['--tanpa-uji-tulis' => true])
            ->expectsOutputToContain('rusak.test: 1 foto')
            ->expectsOutputToContain('baik.test: 1 foto')
            ->expectsOutputToContain('rusak.test: ada contoh foto yang tidak bisa dibuka')
            ->expectsOutputToContain('lebih dari satu host')
            ->assertFailed();
    }
}
