<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LogErrorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Halaman Log Error (superadmin): membaca file log Laravel dengan pencarian,
 * filter level, dan 25/50/100 entri per halaman.
 */
class LogErrorTest extends TestCase
{
    use RefreshDatabase;

    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = storage_path('framework/testing/log-error-'.uniqid());
        File::ensureDirectoryExists($this->folder);
        config(['monitoring.log_folder' => $this->folder, 'monitoring.log_maks_byte' => 8 * 1024 * 1024]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->folder);

        parent::tearDown();
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    /**
     * Entri log berformat Laravel: jejak tumpukan dengan garis miring terbalik ganda (hasil JSON).
     */
    private function entriGalat(string $waktu = '2026-11-18 10:24:59', string $pesan = 'MAYAR_API_KEY belum diisi di .env.'): string
    {
        $file = base_path('app/Services/MayarService.php');

        return "[{$waktu}] local.ERROR: {$pesan} {\"userId\":1,\"exception\":\"[object] (App\\\\Exceptions\\\\MayarException(code: 0): {$pesan} at {$file}:88)\n"
            ."[stacktrace]\n#0 {$file}(35): App\\\\Services\\\\MayarService->kirim()\n#1 {main}\n\"} \n";
    }

    private function tulis(string $nama, string $isi, ?int $waktuUbah = null): void
    {
        file_put_contents("{$this->folder}/{$nama}", $isi);

        if ($waktuUbah !== null) {
            touch("{$this->folder}/{$nama}", $waktuUbah);
        }
    }

    public function test_entri_diurai_jadi_pesan_jenis_lokasi_dan_jejak(): void
    {
        $this->tulis('laravel.log', $this->entriGalat());

        $hasil = app(LogErrorService::class)->cari(null);

        $this->assertCount(1, $hasil['entri']);
        $entri = $hasil['entri'][0];
        $this->assertSame('error', $entri['level']);
        $this->assertSame('2026-11-18 10:24:59', $entri['waktu']);
        $this->assertSame('local', $entri['lingkungan']);
        $this->assertSame('MAYAR_API_KEY belum diisi di .env.', $entri['pesan']);
        $this->assertSame('App\Exceptions\MayarException', $entri['kelas']);
        $this->assertSame('app/Services/MayarService.php:88', $entri['lokasi']);
        $this->assertStringContainsString('[stacktrace]', $entri['detail']);
        $this->assertStringContainsString('App\Services\MayarService->kirim()', $entri['detail']);
        $this->assertFalse($hasil['terpotong']);
    }

    public function test_entri_tanpa_pengecualian_tidak_punya_jenis_dan_lokasi(): void
    {
        $this->tulis('laravel.log', "[2026-11-18 10:30:00] local.ERROR: Pembayaran ditolak {\"order\":\"GD-1\"} \n");

        $entri = app(LogErrorService::class)->cari(null)['entri'][0];

        $this->assertSame('Pembayaran ditolak', $entri['pesan']);
        $this->assertNull($entri['kelas']);
        $this->assertNull($entri['lokasi']);
    }

    public function test_filter_level_bawaan_error_ke_atas_dan_pilihan_lain(): void
    {
        $this->tulis('laravel.log', $this->entriGalat()
            ."[2026-11-18 10:30:00] local.WARNING: Webhook signature invalid {\"ip\":\"127.0.0.1\"} \n"
            ."[2026-11-18 10:31:00] local.INFO: Halo info \n"
            ."[2026-11-18 10:32:00] local.CRITICAL: Database mati \n");

        $layanan = app(LogErrorService::class);

        $this->assertSame(['critical', 'error'], collect($layanan->cari(null)['entri'])->pluck('level')->sort()->values()->all());
        $this->assertCount(4, $layanan->cari(null, LogErrorService::SEMUA)['entri']);
        $this->assertSame(['warning'], collect($layanan->cari(null, 'warning')['entri'])->pluck('level')->all());
        $this->assertCount(2, $layanan->cari(null, 'level-ngawur')['entri']);
    }

    public function test_entri_terbaru_di_atas_lintas_file_dan_di_dalam_file(): void
    {
        $this->tulis('laravel-2026-11-18.log', $this->entriGalat('2026-11-18 08:00:00', 'Lama pertama').$this->entriGalat('2026-11-18 09:00:00', 'Lama kedua'), 1_700_000_000);
        $this->tulis('laravel-2026-11-19.log', $this->entriGalat('2026-11-19 08:00:00', 'Baru pertama').$this->entriGalat('2026-11-19 09:00:00', 'Baru kedua'), 1_700_100_000);

        $pesan = collect(app(LogErrorService::class)->cari(null)['entri'])->pluck('pesan')->all();

        $this->assertSame(['Baru kedua', 'Baru pertama', 'Lama kedua', 'Lama pertama'], $pesan);
    }

    public function test_pencarian_tanpa_membedakan_huruf_dan_mencakup_pesan_jenis_dan_lokasi(): void
    {
        $this->tulis('laravel.log', $this->entriGalat('2026-11-18 10:00:00', 'Kunci hilang')."[2026-11-18 11:00:00] local.ERROR: Tidak ada hubungannya \n");

        $layanan = app(LogErrorService::class);

        $this->assertCount(1, $layanan->cari('KUNCI HILANG')['entri']);
        $this->assertCount(1, $layanan->cari('MayarException')['entri']);
        $this->assertCount(1, $layanan->cari('Services/MayarService.php')['entri']);
        $this->assertCount(2, $layanan->cari('')['entri']);
        $this->assertCount(0, $layanan->cari('tidak ketemu di mana pun')['entri']);
    }

    public function test_file_log_lain_tidak_ikut_dibaca(): void
    {
        $this->tulis('browser.log', $this->entriGalat());
        $this->tulis('laravel.log', $this->entriGalat('2026-11-18 10:00:00', 'Dari laravel'));

        $this->assertSame(['Dari laravel'], collect(app(LogErrorService::class)->cari(null)['entri'])->pluck('pesan')->all());
    }

    public function test_folder_kosong_atau_tidak_ada_memberi_daftar_kosong(): void
    {
        $this->assertSame([], app(LogErrorService::class)->cari(null)['entri']);

        config(['monitoring.log_folder' => $this->folder.'/tidak-ada']);

        $hasil = app(LogErrorService::class)->cari(null);
        $this->assertSame([], $hasil['entri']);
        $this->assertFalse($hasil['terpotong']);
    }

    public function test_hanya_ujung_file_yang_dibaca_dan_entri_di_awal_potongan_dibuang(): void
    {
        $isi = '';

        foreach (range(1, 20) as $nomor) {
            $isi .= $this->entriGalat(sprintf('2026-11-18 10:%02d:00', $nomor), "Entri nomor {$nomor}");
        }

        $this->tulis('laravel.log', $isi);
        config(['monitoring.log_maks_byte' => (int) (strlen($isi) / 3)]);

        $hasil = app(LogErrorService::class)->cari(null);

        $this->assertTrue($hasil['terpotong']);
        $this->assertGreaterThan(0, count($hasil['entri']));
        $this->assertLessThan(20, count($hasil['entri']));
        $this->assertSame('Entri nomor 20', $hasil['entri'][0]['pesan']);

        foreach ($hasil['entri'] as $entri) {
            $this->assertNotNull($entri['kelas'], 'Entri yang terpotong separuh tidak boleh ikut tampil.');
        }
    }

    public function test_nilai_rahasia_disamarkan_di_pesan_dan_detail(): void
    {
        $this->tulis('laravel.log', "[2026-11-18 10:00:00] local.ERROR: Gagal api_key=abc123rahasia dengan Bearer tok.en-rahasia dan biteship_test.kunciRahasia {\"x\":1} \n");

        $entri = app(LogErrorService::class)->cari(null)['entri'][0];

        foreach (['abc123rahasia', 'tok.en-rahasia', 'kunciRahasia'] as $rahasia) {
            $this->assertStringNotContainsString($rahasia, $entri['pesan']);
            $this->assertStringNotContainsString($rahasia, $entri['detail']);
        }

        $this->assertStringContainsString('[disamarkan]', $entri['pesan']);
    }

    // --- Halaman ---

    public function test_hanya_superadmin_yang_bisa_membuka_log_error(): void
    {
        $this->get('/superadmin/log-error')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/superadmin/log-error')->assertForbidden();
    }

    public function test_halaman_mengirim_entri_filter_dan_pilihan(): void
    {
        $this->tulis('laravel.log', $this->entriGalat('2026-11-18 10:00:00', 'Halaman uji'));

        $this->actingAs($this->superadmin())->get('/superadmin/log-error')->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/LogError')
            ->where('daftar.total', 1)
            ->where('daftar.data.0.pesan', 'Halaman uji')
            ->where('daftar.data.0.lokasi', 'app/Services/MayarService.php:88')
            ->where('filter', ['q' => '', 'level' => 'error_ke_atas', 'perHalaman' => 25])
            ->where('pilihanPerHalaman', [25, 50, 100])
            ->where('pilihanLevel.0.nilai', 'error_ke_atas')
            ->where('terpotong', false)
        );
    }

    public function test_per_halaman_25_50_100_dan_nilai_lain_kembali_ke_25(): void
    {
        $this->tulis('laravel.log', implode('', array_map(fn (int $nomor) => $this->entriGalat('2026-11-18 10:00:00', "Entri {$nomor}"), range(1, 130))));
        $admin = $this->superadmin();

        $this->actingAs($admin)->get('/superadmin/log-error')->assertInertia(fn (Assert $page) => $page->has('daftar.data', 25)->where('daftar.last_page', 6)->where('daftar.total', 130));
        $this->actingAs($admin)->get('/superadmin/log-error?per_halaman=50')->assertInertia(fn (Assert $page) => $page->has('daftar.data', 50)->where('filter.perHalaman', 50));
        $this->actingAs($admin)->get('/superadmin/log-error?per_halaman=100')->assertInertia(fn (Assert $page) => $page->has('daftar.data', 100)->where('daftar.last_page', 2));
        $this->actingAs($admin)->get('/superadmin/log-error?per_halaman=7')->assertInertia(fn (Assert $page) => $page->has('daftar.data', 25)->where('filter.perHalaman', 25));
        $this->actingAs($admin)->get('/superadmin/log-error?per_halaman=100&page=2')->assertInertia(fn (Assert $page) => $page->has('daftar.data', 30)->where('daftar.data.0.pesan', 'Entri 30'));
    }

    public function test_pencarian_dan_level_lewat_alamat(): void
    {
        $this->tulis('laravel.log', $this->entriGalat('2026-11-18 10:00:00', 'Kunci hilang')
            ."[2026-11-18 11:00:00] local.WARNING: Peringatan biasa \n");
        $admin = $this->superadmin();

        $this->actingAs($admin)->get('/superadmin/log-error?q=hilang')->assertInertia(fn (Assert $page) => $page->where('daftar.total', 1)->where('filter.q', 'hilang'));
        $this->actingAs($admin)->get('/superadmin/log-error?level=warning')->assertInertia(fn (Assert $page) => $page->where('daftar.total', 1)->where('daftar.data.0.level', 'warning'));
        $this->actingAs($admin)->get('/superadmin/log-error?level=semua')->assertInertia(fn (Assert $page) => $page->where('daftar.total', 2));
        $this->actingAs($admin)->get('/superadmin/log-error?level=ngawur')->assertInertia(fn (Assert $page) => $page->where('filter.level', 'error_ke_atas')->where('daftar.total', 1));
    }
}
