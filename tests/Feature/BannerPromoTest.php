<?php

namespace Tests\Feature;

use App\Models\BannerPromo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Banner pop-up promo situs korporat: banner yang dikirim ke pengunjung
 * (jadwal, aktif, yang terbaru) dan pengelolaannya di superadmin.
 */
class BannerPromoTest extends TestCase
{
    use RefreshDatabase;

    private const SITUS = 'http://greendeahan.com';

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function data(array $override = []): array
    {
        return array_merge([
            'judul' => 'Diskon Akhir Tahun',
            'foto' => UploadedFile::fake()->image('promo.jpg', 800, 1000),
            'tautan' => '/toko',
            'label_tombol' => 'Belanja Sekarang',
            'tampilkan_tombol' => 1,
            'target' => 'semua',
            'frekuensi' => 'sekali',
            'tunda_detik' => 3,
            'status_aktif' => 1,
        ], $override);
    }

    public function test_pengunjung_menerima_banner_yang_sedang_tayang(): void
    {
        $banner = BannerPromo::factory()->create(['judul' => 'Promo Natal', 'label_tombol' => null, 'target' => 'toko', 'frekuensi' => 'sesi', 'tunda_detik' => 5]);

        $this->get(self::SITUS.'/')->assertInertia(fn (Assert $page) => $page
            ->where('bannerPromo.id', $banner->id)
            ->where('bannerPromo.judul', 'Promo Natal')
            ->where('bannerPromo.labelTombol', 'Lihat Promo')
            ->where('bannerPromo.target', 'toko')
            ->where('bannerPromo.frekuensi', 'sesi')
            ->where('bannerPromo.tundaDetik', 5)
            ->where('bannerPromo.tautan', '/toko'));
    }

    public function test_tanpa_banner_tayang_propnya_null(): void
    {
        $this->get(self::SITUS.'/')->assertInertia(fn (Assert $page) => $page->where('bannerPromo', null));
    }

    public function test_banner_nonaktif_belum_mulai_dan_berakhir_tidak_dikirim(): void
    {
        BannerPromo::factory()->nonaktif()->create();
        BannerPromo::factory()->create(['mulai_pada' => now()->addDay()]);
        BannerPromo::factory()->create(['berakhir_pada' => now()->subMinute()]);

        $this->get(self::SITUS.'/')->assertInertia(fn (Assert $page) => $page->where('bannerPromo', null));
    }

    public function test_banner_di_dalam_jadwal_dikirim_dan_yang_terbaru_menang(): void
    {
        BannerPromo::factory()->create(['judul' => 'Lama']);
        $baru = BannerPromo::factory()->create(['judul' => 'Baru', 'mulai_pada' => now()->subHour(), 'berakhir_pada' => now()->addHour()]);

        $this->get(self::SITUS.'/')->assertInertia(fn (Assert $page) => $page->where('bannerPromo.id', $baru->id));
    }

    public function test_versi_banner_berubah_saat_diedit_supaya_tampil_lagi(): void
    {
        $banner = BannerPromo::factory()->create();
        $versiLama = $this->get(self::SITUS.'/')->viewData('page')['props']['bannerPromo']['versi'];

        $this->travel(5)->minutes();
        $banner->update(['judul' => 'Diedit']);

        $versiBaru = $this->get(self::SITUS.'/')->viewData('page')['props']['bannerPromo']['versi'];
        $this->assertGreaterThan($versiLama, $versiBaru);
    }

    public function test_status_banner_untuk_admin(): void
    {
        $this->assertSame('tayang', BannerPromo::factory()->make()->status());
        $this->assertSame('nonaktif', BannerPromo::factory()->nonaktif()->make()->status());
        $this->assertSame('terjadwal', BannerPromo::factory()->make(['mulai_pada' => now()->addDay()])->status());
        $this->assertSame('berakhir', BannerPromo::factory()->make(['berakhir_pada' => now()->subDay()])->status());
    }

    public function test_superadmin_menambah_banner_dengan_gambar(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superadmin())->post('/superadmin/banner-promo', $this->data(['mulai_pada' => '2026-12-01 08:00', 'berakhir_pada' => '2026-12-31 23:59']))->assertSessionHasNoErrors();

        $banner = BannerPromo::firstOrFail();
        $this->assertSame('Diskon Akhir Tahun', $banner->judul);
        $this->assertSame(3, $banner->tunda_detik);
        $this->assertSame('2026-12-31 23:59', $banner->berakhir_pada->format('Y-m-d H:i'));
        $this->assertStringContainsString('/storage/banner/', $banner->foto_url);
        Storage::disk('public')->assertExists('banner/'.basename($banner->foto_url));
    }

    public function test_mengganti_gambar_menghapus_yang_lama_dan_edit_tanpa_gambar_mempertahankannya(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();
        $this->actingAs($admin)->post('/superadmin/banner-promo', $this->data());
        $banner = BannerPromo::firstOrFail();
        $fotoLama = basename($banner->foto_url);

        $this->actingAs($admin)->put("/superadmin/banner-promo/{$banner->id}", $this->data(['foto' => null, 'judul' => 'Judul Baru']))->assertSessionHasNoErrors();
        $this->assertSame($banner->foto_url, $banner->fresh()->foto_url);
        $this->assertSame('Judul Baru', $banner->fresh()->judul);

        $this->actingAs($admin)->put("/superadmin/banner-promo/{$banner->id}", $this->data(['foto' => UploadedFile::fake()->image('baru.png')]))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing('banner/'.$fotoLama);
        Storage::disk('public')->assertExists('banner/'.basename($banner->fresh()->foto_url));
    }

    public function test_menghapus_banner_menghapus_gambarnya(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();
        $this->actingAs($admin)->post('/superadmin/banner-promo', $this->data());
        $banner = BannerPromo::firstOrFail();

        $this->actingAs($admin)->delete("/superadmin/banner-promo/{$banner->id}")->assertRedirect();

        $this->assertDatabaseMissing('banner_promo', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing('banner/'.basename($banner->foto_url));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function inputTidakValid(): array
    {
        return [
            'gambar wajib saat tambah' => [['foto' => null], 'foto'],
            'bukan gambar' => [['foto' => 'teks'], 'foto'],
            'tautan javascript' => [['tautan' => 'javascript:alert(1)'], 'tautan'],
            'tautan protokol relatif' => [['tautan' => '//jahat.example/x'], 'tautan'],
            'tautan tanpa garis miring' => [['tautan' => 'toko'], 'tautan'],
            'jeda terlalu lama' => [['tunda_detik' => 31], 'tunda_detik'],
            'target asing' => [['target' => 'semua-halaman'], 'target'],
            'frekuensi asing' => [['frekuensi' => 'tiap-detik'], 'frekuensi'],
            'berakhir sebelum mulai' => [['mulai_pada' => '2026-12-31 10:00', 'berakhir_pada' => '2026-12-01 10:00'], 'berakhir_pada'],
            'judul kosong' => [['judul' => ''], 'judul'],
        ];
    }

    #[DataProvider('inputTidakValid')]
    public function test_validasi_form_banner(array $override, string $kolom): void
    {
        Storage::fake('public');

        $this->actingAs($this->superadmin())->post('/superadmin/banner-promo', $this->data($override))->assertSessionHasErrors($kolom);
        $this->assertSame(0, BannerPromo::count());
    }

    public function test_tautan_penuh_https_diterima(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superadmin())->post('/superadmin/banner-promo', $this->data(['tautan' => 'https://wa.me/6281200000000']))->assertSessionHasNoErrors();
        $this->assertSame('https://wa.me/6281200000000', BannerPromo::firstOrFail()->tautan);
    }

    public function test_halaman_admin_menandai_banner_yang_sedang_tampil(): void
    {
        BannerPromo::factory()->create(['judul' => 'Lama']);
        $baru = BannerPromo::factory()->create(['judul' => 'Baru']);

        $this->actingAs($this->superadmin())->get('/superadmin/banner-promo')->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/BannerPromo')
            ->has('banner', 2)
            ->where('banner.0.id', $baru->id)
            ->where('banner.0.sedangTampil', true)
            ->where('banner.1.sedangTampil', false));
    }

    public function test_bukan_superadmin_tidak_bisa_mengelola_banner(): void
    {
        Storage::fake('public');
        $banner = BannerPromo::factory()->create();
        $biasa = User::factory()->create();

        $this->get('/superadmin/banner-promo')->assertRedirect();
        $this->actingAs($biasa)->get('/superadmin/banner-promo')->assertForbidden();
        $this->actingAs($biasa)->post('/superadmin/banner-promo', $this->data())->assertForbidden();
        $this->actingAs($biasa)->put("/superadmin/banner-promo/{$banner->id}", $this->data())->assertForbidden();
        $this->actingAs($biasa)->delete("/superadmin/banner-promo/{$banner->id}")->assertForbidden();
        $this->assertSame(1, BannerPromo::count());
    }

    private function fileMedia(string $nama = 'media/pilih.jpg'): string
    {
        Storage::disk('public')->putFileAs(dirname($nama), UploadedFile::fake()->image(basename($nama)), basename($nama));

        return $nama;
    }

    public function test_banner_memakai_gambar_dari_pustaka_media_tanpa_upload(): void
    {
        Storage::fake('public');
        $path = $this->fileMedia();

        $this->actingAs($this->superadmin())->post('/superadmin/banner-promo', $this->data(['foto' => null, 'media_path' => $path]))->assertSessionHasNoErrors();

        $banner = BannerPromo::firstOrFail();
        $this->assertSame(asset('storage/'.$path), $banner->foto_url);
        $this->assertSame([$path], Storage::disk('public')->allFiles('media'));
        $this->assertCount(0, Storage::disk('public')->allFiles('banner'));
    }

    public function test_upload_dipakai_ketika_gambar_dan_pilihan_media_dikirim_bersamaan(): void
    {
        Storage::fake('public');
        $path = $this->fileMedia();

        $this->actingAs($this->superadmin())->post('/superadmin/banner-promo', $this->data(['media_path' => $path]))->assertSessionHasNoErrors();

        $this->assertStringContainsString('/storage/banner/', BannerPromo::firstOrFail()->foto_url);
    }

    public function test_gambar_wajib_dari_salah_satu_sumber_saat_tambah(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superadmin())->post('/superadmin/banner-promo', $this->data(['foto' => null]))->assertSessionHasErrors('foto');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function mediaTidakValid(): array
    {
        return [
            'tidak ada di disk' => ['media/hilang.jpg'],
            'keluar folder' => ['../.env'],
            'bukan format yang diizinkan' => ['media/logo.svg'],
            'bukan gambar' => ['media/catatan.txt'],
        ];
    }

    #[DataProvider('mediaTidakValid')]
    public function test_media_path_tidak_valid_ditolak(string $path): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/logo.svg', '<svg/>');
        Storage::disk('public')->put('media/catatan.txt', 'x');

        $this->actingAs($this->superadmin())->post('/superadmin/banner-promo', $this->data(['foto' => null, 'media_path' => $path]))->assertSessionHasErrors('media_path');
        $this->assertSame(0, BannerPromo::count());
    }

    public function test_mengganti_ke_gambar_media_menghapus_upload_lama_tapi_file_media_tidak_dihapus(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();
        $path = $this->fileMedia();
        $this->actingAs($admin)->post('/superadmin/banner-promo', $this->data());
        $banner = BannerPromo::firstOrFail();
        $unggahan = basename($banner->foto_url);

        $this->actingAs($admin)->put("/superadmin/banner-promo/{$banner->id}", $this->data(['foto' => null, 'media_path' => $path]))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing('banner/'.$unggahan);
        $this->assertSame(asset('storage/'.$path), $banner->fresh()->foto_url);

        $this->actingAs($admin)->put("/superadmin/banner-promo/{$banner->id}", $this->data(['foto' => UploadedFile::fake()->image('lagi.jpg')]))->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists($path);
    }

    public function test_menghapus_banner_tidak_menghapus_file_pustaka_media(): void
    {
        Storage::fake('public');
        $path = $this->fileMedia();
        $this->actingAs($admin = $this->superadmin())->post('/superadmin/banner-promo', $this->data(['foto' => null, 'media_path' => $path]));
        $banner = BannerPromo::firstOrFail();

        $this->actingAs($admin)->delete("/superadmin/banner-promo/{$banner->id}")->assertRedirect();

        $this->assertDatabaseMissing('banner_promo', ['id' => $banner->id]);
        Storage::disk('public')->assertExists($path);
    }

    public function test_file_banner_yang_dipakai_banner_lain_tidak_ikut_terhapus(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();
        $this->actingAs($admin)->post('/superadmin/banner-promo', $this->data());
        $pertama = BannerPromo::firstOrFail();
        $kedua = BannerPromo::factory()->create(['foto_url' => $pertama->foto_url]);

        $this->actingAs($admin)->delete("/superadmin/banner-promo/{$pertama->id}")->assertRedirect();
        Storage::disk('public')->assertExists('banner/'.basename($kedua->foto_url));

        $this->actingAs($admin)->delete("/superadmin/banner-promo/{$kedua->id}")->assertRedirect();
        Storage::disk('public')->assertMissing('banner/'.basename($kedua->foto_url));
    }

    public function test_daftar_media_untuk_pemilih_bisa_dicari_dan_dibagi_per_halaman(): void
    {
        Storage::fake('public');
        $this->fileMedia('media/sepatu.jpg');
        $this->fileMedia('galeri/lapangan.png');
        foreach (range(1, 18) as $nomor) {
            $this->fileMedia("media/foto-{$nomor}.jpg");
        }
        $admin = $this->superadmin();

        $this->actingAs($admin)->getJson('/superadmin/media/daftar?q=sepatu')->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.path', 'media/sepatu.jpg')->assertJsonPath('adaLagi', false);
        $this->actingAs($admin)->getJson('/superadmin/media/daftar?q=galeri')->assertOk()->assertJsonPath('items.0.folder', 'galeri');
        $this->actingAs($admin)->getJson('/superadmin/media/daftar')->assertOk()->assertJsonCount(18, 'items')->assertJsonPath('adaLagi', true);
        $this->actingAs($admin)->getJson('/superadmin/media/daftar?page=2')->assertOk()->assertJsonCount(2, 'items')->assertJsonPath('adaLagi', false);
    }

    public function test_daftar_media_untuk_pemilih_hanya_memuat_format_yang_boleh_jadi_banner(): void
    {
        Storage::fake('public');
        $this->fileMedia('media/bagus.webp');
        Storage::disk('public')->put('media/vektor.svg', '<svg/>');
        Storage::disk('public')->put('media/animasi.gif', 'GIF89a');

        $this->actingAs($this->superadmin())->getJson('/superadmin/media/daftar')->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.nama', 'bagus.webp')->assertJsonPath('adaLagi', false);
    }

    public function test_daftar_media_hanya_untuk_superadmin(): void
    {
        $this->getJson('/superadmin/media/daftar')->assertRedirect();
        $this->actingAs(User::factory()->create())->getJson('/superadmin/media/daftar')->assertForbidden();
    }

    public function test_tombol_banner_opsional_dan_menyala_secara_bawaan(): void
    {
        $dengan = BannerPromo::factory()->create();
        $this->get(self::SITUS.'/')->assertInertia(fn (Assert $page) => $page->where('bannerPromo.id', $dengan->id)->where('bannerPromo.tampilkanTombol', true)->where('bannerPromo.tautan', '/toko'));

        $tanpa = BannerPromo::factory()->create(['tampilkan_tombol' => false]);
        $this->get(self::SITUS.'/')->assertInertia(fn (Assert $page) => $page->where('bannerPromo.id', $tanpa->id)->where('bannerPromo.tampilkanTombol', false)->where('bannerPromo.tautan', '/toko'));
    }

    public function test_superadmin_mematikan_tombol_banner(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();

        $this->actingAs($admin)->post('/superadmin/banner-promo', $this->data(['tampilkan_tombol' => 0]))->assertSessionHasNoErrors();
        $banner = BannerPromo::firstOrFail();
        $this->assertFalse($banner->tampilkan_tombol);

        $this->actingAs($admin)->put("/superadmin/banner-promo/{$banner->id}", $this->data(['foto' => null, 'tampilkan_tombol' => 1]))->assertSessionHasNoErrors();
        $this->assertTrue($banner->fresh()->tampilkan_tombol);

        $this->actingAs($admin)->get('/superadmin/banner-promo')->assertInertia(fn (Assert $page) => $page->where('banner.0.tampilkan_tombol', true));
        $this->actingAs($admin)->post('/superadmin/banner-promo', $this->data(['tampilkan_tombol' => 'mungkin']))->assertSessionHasErrors('tampilkan_tombol');
    }
}
