<?php

namespace Tests\Feature;

use App\Exceptions\MayarException;
use App\Mail\ErrorBaruMail;
use App\Models\PengaturanSitus;
use App\Models\User;
use App\Services\PeringatanErrorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * Peringatan error: lonceng ke semua superadmin, email ke penerima pilihan,
 * paling banyak satu kali per jam untuk error yang sama.
 */
class PeringatanErrorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['monitoring.peringatan_aktif' => true, 'monitoring.peringatan_jeda_menit' => 60]);
        Cache::flush();
        Mail::fake();
        $this->withoutDefer();
    }

    private function superadmin(string $email = 'admin@contoh.com'): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    private function pilihPenerima(array $idUser = [], array $email = []): void
    {
        app(PeringatanErrorService::class)->simpanPenerima($idUser, $email);
    }

    private function galat(string $pesan = 'Kunci Mayar belum diisi', string $kelas = MayarException::class): void
    {
        Log::error($pesan, ['exception' => new $kelas($pesan)]);
    }

    public function test_error_memberi_lonceng_ke_semua_superadmin_tapi_tidak_ke_pengguna_biasa(): void
    {
        $satu = $this->superadmin('satu@contoh.com');
        $dua = $this->superadmin('dua@contoh.com');
        $biasa = User::factory()->create();

        $this->galat();

        $this->assertSame(1, $satu->notifications()->count());
        $this->assertSame(1, $dua->notifications()->count());
        $this->assertSame(0, $biasa->notifications()->count());

        $data = $satu->notifications()->first()->data;
        $this->assertSame('Error baru: MayarException', $data['judul']);
        $this->assertSame('Kunci Mayar belum diisi', $data['pesan']);
        $this->assertSame('error', $data['level']);
        $this->assertStringStartsWith('/superadmin/log-error?q=', $data['url']);
        $this->assertStringStartsWith('tests/Feature/PeringatanErrorTest.php:', (string) $data['lokasi']);
    }

    public function test_email_dikirim_ke_penerima_pilihan_dan_email_tambahan_tanpa_duplikat(): void
    {
        $dipilih = $this->superadmin('pilih@contoh.com');
        $this->superadmin('tidak-dipilih@contoh.com');
        $this->pilihPenerima([$dipilih->id], ['Teknisi@Contoh.com', 'pilih@contoh.com']);

        $this->galat();

        Mail::assertSent(ErrorBaruMail::class, 2);
        Mail::assertSent(ErrorBaruMail::class, fn (ErrorBaruMail $mail) => $mail->hasTo('pilih@contoh.com'));
        Mail::assertSent(ErrorBaruMail::class, fn (ErrorBaruMail $mail) => $mail->hasTo('teknisi@contoh.com'));
        Mail::assertNotSent(ErrorBaruMail::class, fn (ErrorBaruMail $mail) => $mail->hasTo('tidak-dipilih@contoh.com'));
    }

    public function test_tanpa_penerima_hanya_lonceng_yang_dikirim(): void
    {
        $admin = $this->superadmin();

        $this->galat();

        Mail::assertNothingSent();
        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_penerima_yang_bukan_lagi_superadmin_tidak_dikirimi_email(): void
    {
        $mantan = $this->superadmin('mantan@contoh.com');
        $this->pilihPenerima([$mantan->id]);
        $mantan->is_superadmin = false;
        $mantan->save();

        $this->galat();

        Mail::assertNothingSent();
    }

    public function test_error_yang_sama_hanya_sekali_per_jam(): void
    {
        $admin = $this->superadmin();
        $this->pilihPenerima([], ['teknisi@contoh.com']);

        $this->galat();
        $this->galat();
        $this->galat('Pesan lain tapi kelas dan lokasi sama');

        $this->assertSame(1, $admin->notifications()->count());
        Mail::assertSent(ErrorBaruMail::class, 1);

        $this->travel(59)->minutes();
        $this->galat();
        $this->assertSame(1, $admin->notifications()->count());

        $this->travel(2)->minutes();
        $this->galat();
        $this->assertSame(2, $admin->notifications()->count());
        Mail::assertSent(ErrorBaruMail::class, 2);
    }

    public function test_error_berbeda_dikabarkan_terpisah(): void
    {
        $admin = $this->superadmin();

        $this->galat('Satu', MayarException::class);
        $this->galat('Dua', RuntimeException::class);

        $this->assertSame(2, $admin->notifications()->count());
    }

    public function test_pesan_biasa_yang_hanya_beda_angka_dianggap_error_yang_sama(): void
    {
        $admin = $this->superadmin();

        Log::error('Pesanan 123 gagal diproses');
        Log::error('Pesanan 456 gagal diproses');
        Log::error('Hal lain sama sekali');

        $this->assertSame(2, $admin->notifications()->count());
    }

    public function test_level_di_bawah_error_dan_saklar_mati_tidak_memicu_peringatan(): void
    {
        $admin = $this->superadmin();

        Log::warning('Cuma peringatan');
        Log::info('Cuma info');
        Log::notice('Cuma catatan');
        $this->assertSame(0, $admin->notifications()->count());

        config(['monitoring.peringatan_aktif' => false]);
        $this->galat();
        $this->assertSame(0, $admin->notifications()->count());

        config(['monitoring.peringatan_aktif' => true]);
        Log::critical('Kritis');
        Log::emergency('Darurat');
        $this->assertSame(2, $admin->notifications()->count());
    }

    public function test_rahasia_di_pesan_peringatan_disamarkan(): void
    {
        $admin = $this->superadmin();

        Log::error('Gagal memanggil dengan api_key=rahasia123 dan biteship_test.kunciRahasia');

        $pesan = $admin->notifications()->first()->data['pesan'];
        $this->assertStringNotContainsString('rahasia123', $pesan);
        $this->assertStringNotContainsString('kunciRahasia', $pesan);
    }

    public function test_pengiriman_email_yang_gagal_tidak_melempar_galat_dan_lonceng_tetap_masuk(): void
    {
        Mail::swap(app('mail.manager'));
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);
        app('mail.manager')->forgetMailers();
        $admin = $this->superadmin();
        $this->pilihPenerima([$admin->id]);

        $this->galat();

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, DatabaseNotification::count());
    }

    public function test_email_uji_dikirim_ke_penerima_dan_galatnya_terlihat(): void
    {
        $this->pilihPenerima([], ['teknisi@contoh.com']);

        $this->assertSame(1, app(PeringatanErrorService::class)->kirimUji());
        Mail::assertSent(ErrorBaruMail::class, fn (ErrorBaruMail $mail) => $mail->hasTo('teknisi@contoh.com') && ! empty($mail->info['uji']));
    }

    public function test_email_peringatan_memuat_pesan_lokasi_dan_tautan_log(): void
    {
        $mail = new ErrorBaruMail([
            'level' => 'error', 'pesan' => 'Kunci Mayar belum diisi', 'kelas' => MayarException::class, 'lokasi' => 'app/Services/MayarService.php:88',
            'waktu' => '2026-11-18 10:24:59', 'lingkungan' => 'production', 'url' => '/superadmin/log-error?q=MayarException',
        ]);

        $mail->assertSeeInHtml('Kunci Mayar belum diisi');
        $mail->assertSeeInHtml('app/Services/MayarService.php:88');
        $mail->assertSeeInHtml('/superadmin/log-error?q=MayarException');
        $this->assertSame('['.config('app.name').'] Error baru: MayarException', $mail->envelope()->subject);
    }

    public function test_penerima_tersimpan_di_pengaturan_dan_dibaca_kembali(): void
    {
        $admin = $this->superadmin();
        $this->pilihPenerima([$admin->id], ['A@Contoh.com', 'b@contoh.com']);

        $this->assertSame(['userIds' => [$admin->id], 'emails' => ['a@contoh.com', 'b@contoh.com']], app(PeringatanErrorService::class)->penerima());
        $this->assertSame(2, PengaturanSitus::whereIn('kunci', [PeringatanErrorService::KUNCI_USER, PeringatanErrorService::KUNCI_EMAIL])->count());

        $this->pilihPenerima([], []);
        $this->assertSame(['userIds' => [], 'emails' => []], app(PeringatanErrorService::class)->penerima());
    }
}
