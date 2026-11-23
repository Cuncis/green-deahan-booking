<?php

namespace Tests\Feature;

use App\Mail\ErrorBaruMail;
use App\Models\User;
use App\Notifications\ErrorBaruNotification;
use App\Services\PeringatanErrorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pengaturan > Email & Notifikasi (penerima email error) dan lonceng notifikasi superadmin.
 */
class PengaturanNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(string $email = 'admin@contoh.com'): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    private function notifikasiUntuk(User $user, array $data = []): string
    {
        $user->notify(new class($data) extends ErrorBaruNotification
        {
            public function __construct(private readonly array $data)
            {
                parent::__construct(['level' => 'error', 'pesan' => '', 'kelas' => null, 'lokasi' => null, 'waktu' => '', 'lingkungan' => 'testing', 'url' => '/superadmin/log-error']);
            }

            public function toArray(object $notifiable): array
            {
                return $this->data + ['judul' => 'Error baru: Uji', 'pesan' => 'Pesan uji', 'url' => '/superadmin/log-error?q=Uji'];
            }
        });

        return $user->notifications()->latest()->first()->id;
    }

    // --- Halaman pengaturan ---

    public function test_hanya_superadmin_yang_bisa_membuka_pengaturan_notifikasi(): void
    {
        $this->get('/superadmin/pengaturan/notifikasi')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/superadmin/pengaturan/notifikasi')->assertForbidden();
        $this->actingAs(User::factory()->create())->put('/superadmin/pengaturan/notifikasi', [])->assertForbidden();
        $this->actingAs(User::factory()->create())->post('/superadmin/pengaturan/notifikasi/uji')->assertForbidden();
    }

    public function test_halaman_mengirim_superadmin_penerima_dan_status_email(): void
    {
        $admin = $this->superadmin();
        User::factory()->create(['name' => 'Bukan superadmin']);
        app(PeringatanErrorService::class)->simpanPenerima([$admin->id], ['teknisi@contoh.com', 'lain@contoh.com']);

        $this->actingAs($admin)->get('/superadmin/pengaturan/notifikasi')->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/PengaturanNotifikasi')
            ->where('pengaturan.userIds', [$admin->id])
            ->where('pengaturan.emailTambahan', "teknisi@contoh.com\nlain@contoh.com")
            ->has('pilihanUser', 1)
            ->where('pilihanUser.0.email', 'admin@contoh.com')
            ->where('emailAktif', false)
            ->where('mailer', 'array')
            ->where('jedaMenit', 60)
        );
    }

    public function test_simpan_penerima_dengan_pemisah_bebas_dan_huruf_kecil(): void
    {
        $admin = $this->superadmin();
        $kedua = $this->superadmin('kedua@contoh.com');

        $this->actingAs($admin)->put('/superadmin/pengaturan/notifikasi', [
            'user_ids' => [$admin->id, $kedua->id],
            'email_tambahan' => "Teknisi@Contoh.com, lain@contoh.com;\nketiga@contoh.com  keempat@contoh.com",
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            ['userIds' => [$admin->id, $kedua->id], 'emails' => ['teknisi@contoh.com', 'lain@contoh.com', 'ketiga@contoh.com', 'keempat@contoh.com']],
            app(PeringatanErrorService::class)->penerima(),
        );
    }

    public function test_simpan_kosong_menghapus_semua_penerima(): void
    {
        $admin = $this->superadmin();
        app(PeringatanErrorService::class)->simpanPenerima([$admin->id], ['teknisi@contoh.com']);

        $this->actingAs($admin)->put('/superadmin/pengaturan/notifikasi', ['user_ids' => [], 'email_tambahan' => ''])->assertSessionHasNoErrors();

        $this->assertSame(['userIds' => [], 'emails' => []], app(PeringatanErrorService::class)->penerima());
    }

    public function test_validasi_penerima(): void
    {
        $admin = $this->superadmin();
        $biasa = User::factory()->create();
        $kirim = fn (array $data) => $this->actingAs($admin)->put('/superadmin/pengaturan/notifikasi', $data);

        $kirim(['email_tambahan' => 'bukan-email'])->assertSessionHasErrors('email_tambahan.0');
        $kirim(['email_tambahan' => 'a@contoh.com, A@contoh.com'])->assertSessionHasErrors('email_tambahan.1');
        $kirim(['email_tambahan' => implode(',', array_map(fn (int $nomor) => "orang{$nomor}@contoh.com", range(1, 11)))])->assertSessionHasErrors('email_tambahan');
        $kirim(['user_ids' => [$biasa->id]])->assertSessionHasErrors('user_ids.0');
        $kirim(['user_ids' => [99999]])->assertSessionHasErrors('user_ids.0');
        $kirim(['user_ids' => [$admin->id, $admin->id]])->assertSessionHasErrors('user_ids.1');

        $this->assertSame(['userIds' => [], 'emails' => []], app(PeringatanErrorService::class)->penerima());
    }

    public function test_email_uji_dikirim_ke_penerima_yang_tersimpan(): void
    {
        Mail::fake();
        $admin = $this->superadmin();
        app(PeringatanErrorService::class)->simpanPenerima([$admin->id], ['teknisi@contoh.com']);

        $this->actingAs($admin)->post('/superadmin/pengaturan/notifikasi/uji')->assertSessionHas('success');

        Mail::assertSent(ErrorBaruMail::class, 2);
    }

    public function test_email_uji_tanpa_penerima_ditolak_dengan_pesan(): void
    {
        Mail::fake();

        $this->actingAs($this->superadmin())->post('/superadmin/pengaturan/notifikasi/uji')->assertSessionHas('error');

        Mail::assertNothingSent();
    }

    public function test_email_uji_yang_gagal_dikirim_menampilkan_alasan(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);
        app('mail.manager')->forgetMailers();
        $admin = $this->superadmin();
        app(PeringatanErrorService::class)->simpanPenerima([$admin->id], []);

        $this->actingAs($admin)->post('/superadmin/pengaturan/notifikasi/uji')->assertSessionHas('error', fn (string $pesan) => str_starts_with($pesan, 'Email uji gagal dikirim'));
    }

    // --- Lonceng ---

    public function test_halaman_superadmin_membawa_data_lonceng(): void
    {
        $admin = $this->superadmin();
        $lain = $this->superadmin('lain@contoh.com');
        $this->notifikasiUntuk($admin);
        $dibaca = $this->notifikasiUntuk($admin, ['judul' => 'Error lama']);
        $admin->notifications()->findOrFail($dibaca)->markAsRead();
        $this->notifikasiUntuk($lain);

        $this->actingAs($admin)->get('/superadmin/log-error')->assertInertia(fn (Assert $page) => $page
            ->where('notifikasi.belumDibaca', 1)
            ->has('notifikasi.terbaru', 2)
            ->where('notifikasi.terbaru.0.dibaca', fn ($nilai) => is_bool($nilai))
            ->where('notifikasi.bacaSemuaUrl', route('superadmin.notifikasi.baca-semua'))
        );
    }

    public function test_membuka_notifikasi_menandai_dibaca_dan_mengarahkan_ke_tautannya(): void
    {
        $admin = $this->superadmin();
        $id = $this->notifikasiUntuk($admin);

        $this->actingAs($admin)->post("/superadmin/notifikasi/{$id}/buka")->assertRedirect('/superadmin/log-error?q=Uji');

        $this->assertNotNull($admin->notifications()->find($id)->read_at);
        $this->assertSame(0, $admin->unreadNotifications()->count());
    }

    public function test_notifikasi_milik_orang_lain_tidak_bisa_dibuka(): void
    {
        $admin = $this->superadmin();
        $lain = $this->superadmin('lain@contoh.com');
        $idLain = $this->notifikasiUntuk($lain);

        $this->actingAs($admin)->post("/superadmin/notifikasi/{$idLain}/buka")->assertNotFound();
        $this->assertNull($lain->notifications()->find($idLain)->read_at);
    }

    public function test_tautan_ke_luar_situs_diganti_ke_log_error(): void
    {
        $admin = $this->superadmin();

        foreach (['https://jahat.example/x', '//jahat.example/x', 'javascript:alert(1)', ''] as $tujuan) {
            $id = $this->notifikasiUntuk($admin, ['url' => $tujuan]);

            $this->actingAs($admin)->post("/superadmin/notifikasi/{$id}/buka")->assertRedirect('/superadmin/log-error');
        }
    }

    public function test_tandai_semua_dibaca_hanya_untuk_pengguna_itu(): void
    {
        $admin = $this->superadmin();
        $lain = $this->superadmin('lain@contoh.com');
        $this->notifikasiUntuk($admin);
        $this->notifikasiUntuk($admin);
        $this->notifikasiUntuk($lain);

        $this->actingAs($admin)->post('/superadmin/notifikasi/baca-semua')->assertRedirect();

        $this->assertSame(0, $admin->unreadNotifications()->count());
        $this->assertSame(1, $lain->unreadNotifications()->count());
    }

    public function test_hanya_superadmin_yang_bisa_memakai_endpoint_lonceng(): void
    {
        $this->post('/superadmin/notifikasi/baca-semua')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->post('/superadmin/notifikasi/baca-semua')->assertForbidden();
        $this->actingAs(User::factory()->create())->post('/superadmin/notifikasi/abc/buka')->assertForbidden();
    }
}
