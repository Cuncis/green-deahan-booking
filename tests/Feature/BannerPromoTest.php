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
}
