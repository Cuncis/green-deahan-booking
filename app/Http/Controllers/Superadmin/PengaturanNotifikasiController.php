<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PeringatanErrorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Pengaturan > Email & Notifikasi: siapa yang menerima email "Error baru di
 * aplikasi". Notifikasi lonceng selalu ke semua superadmin.
 */
class PengaturanNotifikasiController extends Controller
{
    public function __construct(private readonly PeringatanErrorService $peringatan) {}

    public function edit(): Response
    {
        $penerima = $this->peringatan->penerima();

        return Inertia::render('superadmin/PengaturanNotifikasi', [
            'pengaturan' => [
                'userIds' => $penerima['userIds'],
                'emailTambahan' => implode("\n", $penerima['emails']),
            ],
            'pilihanUser' => User::where('is_superadmin', true)->orderBy('name')->get(['id', 'name', 'email'])->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])->all(),
            'mailer' => (string) config('mail.default'),
            'emailAktif' => ! in_array(config('mail.default'), ['log', 'array'], true),
            'peringatanAktif' => $this->peringatan->aktif(),
            'jedaMenit' => (int) config('monitoring.peringatan_jeda_menit'),
            'simpanUrl' => route('superadmin.notifikasi.simpan'),
            'ujiUrl' => route('superadmin.notifikasi.uji'),
            'logErrorUrl' => route('superadmin.log-error'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(['email_tambahan' => $this->daftarEmail((string) $request->input('email_tambahan'))]);

        $data = $request->validate([
            'user_ids' => ['nullable', 'array', 'max:50'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id,is_superadmin,1'],
            'email_tambahan' => ['array', 'max:'.PeringatanErrorService::MAKS_EMAIL_TAMBAHAN],
            'email_tambahan.*' => ['email', 'max:150', 'distinct:ignore_case'],
        ], [
            'user_ids.*.exists' => 'Penerima harus superadmin yang masih aktif.',
            'email_tambahan.max' => 'Email tambahan maksimal '.PeringatanErrorService::MAKS_EMAIL_TAMBAHAN.' alamat.',
            'email_tambahan.*.email' => 'Format email ":input" belum benar.',
            'email_tambahan.*.distinct' => 'Email ":input" ditulis dua kali.',
        ], ['email_tambahan' => 'email tambahan']);

        $this->peringatan->simpanPenerima($data['user_ids'] ?? [], $data['email_tambahan']);

        return back()->with('success', 'Penerima email error disimpan.');
    }

    public function uji(): RedirectResponse
    {
        if ($this->peringatan->penerimaEmail() === []) {
            return back()->with('error', 'Pilih atau tulis penerima dulu, lalu simpan sebelum mengirim email uji.');
        }

        try {
            $jumlah = $this->peringatan->kirimUji();
        } catch (Throwable $galat) {
            return back()->with('error', 'Email uji gagal dikirim: '.mb_substr($galat->getMessage(), 0, 200));
        }

        $pesan = "Email uji dikirim ke {$jumlah} penerima.";

        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $pesan .= ' Pengiriman email belum aktif (MAIL_MAILER='.config('mail.default').'), jadi isinya hanya tercatat di log.';
        }

        return back()->with('success', $pesan);
    }

    /**
     * Email dipisah koma, titik koma, spasi, atau baris baru.
     *
     * @return array<int, string>
     */
    private function daftarEmail(string $teks): array
    {
        return array_values(array_filter(preg_split('/[\s,;]+/', trim($teks)) ?: []));
    }
}
