<?php

namespace App\Services;

use App\Mail\ErrorBaruMail;
use App\Models\PengaturanSitus;
use App\Models\User;
use App\Notifications\ErrorBaruNotification;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Peringatan saat aplikasi mencatat error: notifikasi lonceng ke semua
 * superadmin dan email ke penerima yang dipilih, paling banyak satu kali per
 * jam untuk error yang sama.
 *
 * Layanan ini berjalan di tengah proses pencatatan log, jadi tidak boleh
 * melempar galat dan tidak boleh memicu peringatan baru (perulangan tanpa
 * akhir): semua kegagalan di sini sengaja ditelan.
 */
class PeringatanErrorService
{
    public const KUNCI_USER = 'peringatan_error_user_ids';

    public const KUNCI_EMAIL = 'peringatan_error_email_tambahan';

    /** Batas email tambahan di luar daftar superadmin. */
    public const MAKS_EMAIL_TAMBAHAN = 10;

    private static bool $sedangBekerja = false;

    public function __construct(private readonly LogErrorService $logError) {}

    public function aktif(): bool
    {
        return (bool) config('monitoring.peringatan_aktif');
    }

    /**
     * Dipanggil untuk setiap pesan log. Hanya level error ke atas yang
     * dikabarkan, dan error yang sama (kelas dan lokasi, atau pesan tanpa
     * angka) hanya sekali per jeda.
     */
    public function tangani(MessageLogged $peristiwa): void
    {
        if (! $this->aktif() || self::$sedangBekerja || ! in_array($peristiwa->level, LogErrorService::LEVEL_ERROR, true)) {
            return;
        }

        self::$sedangBekerja = true;

        try {
            $info = $this->info($peristiwa);

            if (! Cache::add('peringatan-error:'.sha1($info['sidik']), 1, now()->addMinutes(max(1, (int) config('monitoring.peringatan_jeda_menit'))))) {
                return;
            }
        } catch (Throwable) {
            // Pencatatan log tidak boleh gagal gara-gara peringatan.
            return;
        } finally {
            self::$sedangBekerja = false;
        }

        unset($info['sidik']);

        try {
            // always: tetap jalan walau respons gagal (error biasanya berujung halaman 500).
            defer(fn () => $this->kirim($info), 'peringatan-error', always: true);
        } catch (Throwable) {
            // Sama: tidak boleh mengganggu pencatatan log.
        }
    }

    /**
     * @param  array{level: string, pesan: string, kelas: string|null, lokasi: string|null, waktu: string, lingkungan: string, url: string}  $info
     */
    public function kirim(array $info): void
    {
        if (self::$sedangBekerja) {
            return;
        }

        self::$sedangBekerja = true;

        try {
            Notification::send(User::where('is_superadmin', true)->get(), new ErrorBaruNotification($info));
        } catch (Throwable) {
            // Tabel notifikasi belum ada atau database bermasalah: lanjut ke email.
        }

        foreach ($this->penerimaEmail() as $email) {
            try {
                Mail::to($email)->send(new ErrorBaruMail($info));
            } catch (Throwable) {
                // Satu penerima gagal tidak boleh menghentikan yang lain.
            }
        }

        self::$sedangBekerja = false;
    }

    /**
     * Email uji untuk memastikan pengaturan email (misalnya Resend) bekerja.
     * Berbeda dari peringatan asli, galat pengiriman dilempar supaya terlihat.
     *
     * @return int jumlah penerima
     *
     * @throws Throwable
     */
    public function kirimUji(): int
    {
        $info = [
            'level' => 'error',
            'pesan' => 'Ini email uji dari Pengaturan > Email & Notifikasi. Tidak ada error sungguhan.',
            'kelas' => null,
            'lokasi' => null,
            'waktu' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'lingkungan' => app()->environment(),
            'url' => route('superadmin.log-error', [], false),
            'uji' => true,
        ];

        $penerima = $this->penerimaEmail();

        foreach ($penerima as $email) {
            Mail::to($email)->send(new ErrorBaruMail($info));
        }

        return count($penerima);
    }

    /**
     * Alamat email yang menerima peringatan: superadmin yang dipilih (yang masih
     * superadmin) ditambah email tambahan, tanpa duplikat.
     *
     * @return array<int, string>
     */
    public function penerimaEmail(): array
    {
        ['userIds' => $idUser, 'emails' => $tambahan] = $this->penerima();

        $emailUser = $idUser === [] ? [] : User::whereIn('id', $idUser)->where('is_superadmin', true)->pluck('email')->all();

        return array_values(array_unique(array_map('strtolower', [...$emailUser, ...$tambahan])));
    }

    /**
     * @return array{userIds: array<int, int>, emails: array<int, string>}
     */
    public function penerima(): array
    {
        $nilai = PengaturanSitus::query()->whereIn('kunci', [self::KUNCI_USER, self::KUNCI_EMAIL])->pluck('nilai', 'kunci');

        return [
            'userIds' => array_values(array_filter(array_map('intval', explode(',', (string) ($nilai[self::KUNCI_USER] ?? ''))))),
            'emails' => array_values(array_filter(array_map('trim', explode(',', (string) ($nilai[self::KUNCI_EMAIL] ?? ''))))),
        ];
    }

    /**
     * @param  array<int, int>  $idUser
     * @param  array<int, string>  $emails
     */
    public function simpanPenerima(array $idUser, array $emails): void
    {
        PengaturanSitus::updateOrCreate(['kunci' => self::KUNCI_USER], ['nilai' => implode(',', array_map('intval', $idUser))]);
        PengaturanSitus::updateOrCreate(['kunci' => self::KUNCI_EMAIL], ['nilai' => implode(',', array_map('strtolower', $emails))]);
    }

    /**
     * @return array{sidik: string, level: string, pesan: string, kelas: string|null, lokasi: string|null, waktu: string, lingkungan: string, url: string}
     */
    private function info(MessageLogged $peristiwa): array
    {
        $galat = $peristiwa->context['exception'] ?? null;
        $galat = $galat instanceof Throwable ? $galat : null;

        $pesan = $this->logError->samarkan(mb_substr(trim((string) ($galat?->getMessage() ?: $peristiwa->message)), 0, 500));
        $kelas = $galat ? $galat::class : null;
        $lokasi = $galat ? str_replace(base_path().'/', '', $galat->getFile()).':'.$galat->getLine() : null;
        $sidik = $kelas ? "{$kelas}|{$lokasi}" : $peristiwa->level.'|'.preg_replace('/\d+/', '#', $pesan);

        return [
            'sidik' => $sidik,
            'level' => $peristiwa->level,
            'pesan' => $pesan,
            'kelas' => $kelas,
            'lokasi' => $lokasi,
            'waktu' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'lingkungan' => app()->environment(),
            'url' => route('superadmin.log-error', ['q' => $kelas ?? mb_substr($pesan, 0, 60)], false),
        ];
    }
}
