<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use App\Services\FotoUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LapanganAdminController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const JENIS_OLAHRAGA = ['futsal', 'padel', 'badminton', 'tennis', 'mini soccer'];

    public function __construct(private readonly FotoUploadService $fotoUploadService) {}

    public function index(): Response
    {
        $tenant = app('tenant');

        return Inertia::render('admin/Lapangan/Index', [
            'lapangan' => Lapangan::where('tenant_id', $tenant->id)->with('cabang')->orderBy('nama')->get()
                ->map(fn (Lapangan $lapangan) => [
                    'id' => $lapangan->id,
                    'nama' => $lapangan->nama,
                    'jenis' => $lapangan->jenis_olahraga,
                    'cabang' => $lapangan->cabang?->nama_cabang,
                    'hargaPerJam' => (int) $lapangan->harga_per_jam,
                    'fotoUrl' => $lapangan->foto_url,
                    'aktif' => (bool) $lapangan->status_aktif,
                    'editUrl' => route('admin.lapangan.edit', $lapangan),
                    'hapusUrl' => route('admin.lapangan.destroy', $lapangan),
                ])->all(),
            'batasLapangan' => $tenant->fitur?->batas_lapangan,
            'jumlahLapangan' => Lapangan::where('tenant_id', $tenant->id)->count(),
            'paketUpgrade' => $this->paketUpgradeTersedia($tenant->paket),
        ]);
    }

    public function create(): Response
    {
        $tenant = app('tenant');

        return Inertia::render('admin/Lapangan/Form', [
            'lapangan' => null,
            ...$this->dataForm($tenant),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $jumlahLapanganSekarang = Lapangan::where('tenant_id', $tenant->id)->count();
        $batas = $tenant->fitur?->batas_lapangan;

        if ($batas !== null && $jumlahLapanganSekarang >= $batas) {
            throw ValidationException::withMessages([
                'nama' => "Paket {$tenant->paket} kamu dibatasi {$batas} lapangan. Upgrade paket untuk tambah lebih banyak.",
            ]);
        }

        $data = $this->validateData($request, $tenant);

        $data['tenant_id'] = $tenant->id;
        $data['status_aktif'] = true;
        $data['cabang_id'] = $this->resolveCabangId($request, $tenant);

        if ($request->hasFile('foto')) {
            $data['foto_url'] = $this->fotoUploadService->simpan($request->file('foto'), 'lapangan');
        }

        Lapangan::create($data);

        return redirect()->route('admin.lapangan')
            ->with('success', "Lapangan \"{$data['nama']}\" berhasil ditambahkan.");
    }

    /**
     * Tenant Basic/Pro minta upgrade paket lewat tombol di halaman Lapangan
     * Saya (lihat admin/Lapangan/Index.tsx). Tidak ada upgrade otomatis,
     * cuma kirim email permintaan ke admin_email (sama seperti
     * TenantRegistrationController::kirimNotifikasiPendaftaran()), tim yang
     * proses manual lewat `php artisan tenant:activate --paket=`.
     */
    public function mintaUpgrade(Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'paket_tujuan' => ['required', Rule::in($this->paketUpgradeTersedia($tenant->paket))],
        ]);

        $this->kirimPermintaanUpgrade($tenant, $data['paket_tujuan']);

        return redirect()->route('admin.lapangan')
            ->with('success', 'Permintaan upgrade sudah dikirim, tim kami akan segera menghubungimu.');
    }

    /**
     * @return array<int, string>
     */
    private function paketUpgradeTersedia(string $paketSaatIni): array
    {
        return match ($paketSaatIni) {
            'basic' => ['pro', 'premium'],
            'pro' => ['premium'],
            default => [],
        };
    }

    /**
     * Best-effort saja (sama seperti TenantRegistrationController::
     * kirimNotifikasiPendaftaran()), kegagalan kirim email tidak boleh
     * menggagalkan alur permintaan upgrade.
     */
    private function kirimPermintaanUpgrade(Tenant $tenant, string $paketTujuan): void
    {
        $adminEmail = config('app.admin_email');

        if (! $adminEmail) {
            return;
        }

        $subdomain = Str::before($tenant->domain, '.greendeahan.com');

        $pesan = implode("\n", [
            'Ada tenant minta upgrade paket.',
            '',
            "Nama Bisnis: {$tenant->nama_bisnis}",
            "Domain: {$tenant->domain}",
            "Paket saat ini: {$tenant->paket}",
            "Paket yang diminta: {$paketTujuan}",
            "Email PIC: {$tenant->email_admin}",
            "WhatsApp PIC: {$tenant->whatsapp_admin}",
            '',
            "Kalau disetujui, aktifkan lewat: php artisan tenant:activate {$subdomain} --paket={$paketTujuan}",
        ]);

        try {
            Mail::raw($pesan, function ($message) use ($adminEmail, $tenant) {
                $message->to($adminEmail)
                    ->subject("Permintaan Upgrade Paket, {$tenant->nama_bisnis}");
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function edit(Lapangan $lapangan): Response
    {
        $tenant = app('tenant');

        abort_unless($lapangan->tenant_id === $tenant->id, 404);

        return Inertia::render('admin/Lapangan/Form', [
            'lapangan' => [
                'id' => $lapangan->id,
                'nama' => $lapangan->nama,
                'jenis_olahraga' => $lapangan->jenis_olahraga,
                'harga_per_jam' => (int) $lapangan->harga_per_jam,
                'harga_jam_sibuk' => $lapangan->harga_jam_sibuk !== null ? (int) $lapangan->harga_jam_sibuk : null,
                'deskripsi' => $lapangan->deskripsi,
                'cabang_id' => $lapangan->cabang_id,
                'status_aktif' => (bool) $lapangan->status_aktif,
                'fotoUrl' => $lapangan->foto_url,
                'updateUrl' => route('admin.lapangan.update', $lapangan),
            ],
            ...$this->dataForm($tenant),
        ]);
    }

    /**
     * Pilihan untuk form tambah/edit lapangan.
     *
     * @return array{jenisOlahraga: array<int, string>, daftarCabang: array<int, array{id: int, nama: string}>, simpanUrl: string, kembaliUrl: string}
     */
    private function dataForm(Tenant $tenant): array
    {
        return [
            'jenisOlahraga' => self::JENIS_OLAHRAGA,
            'daftarCabang' => $tenant->punyaFitur('multi_cabang')
                ? Cabang::where('tenant_id', $tenant->id)->orderBy('nama_cabang')->get()
                    ->map(fn (Cabang $cabang) => ['id' => $cabang->id, 'nama' => $cabang->nama_cabang])->all()
                : [],
            'simpanUrl' => route('admin.lapangan.store'),
            'kembaliUrl' => route('admin.lapangan'),
        ];
    }

    public function update(Request $request, Lapangan $lapangan): RedirectResponse
    {
        $tenant = app('tenant');

        abort_unless($lapangan->tenant_id === $tenant->id, 404);

        $data = $this->validateData($request, $tenant);

        $data['status_aktif'] = $request->boolean('status_aktif');
        $data['cabang_id'] = $this->resolveCabangId($request, $tenant);

        if ($request->hasFile('foto')) {
            $this->fotoUploadService->hapus($lapangan->foto_url);
            $data['foto_url'] = $this->fotoUploadService->simpan($request->file('foto'), 'lapangan');
        }

        $lapangan->update($data);

        return redirect()->route('admin.lapangan')
            ->with('success', "Lapangan \"{$lapangan->nama}\" berhasil diperbarui.");
    }

    public function destroy(Lapangan $lapangan): RedirectResponse
    {
        $tenant = app('tenant');

        abort_unless($lapangan->tenant_id === $tenant->id, 404);

        $punyaBooking = JadwalSlot::where('tenant_id', $tenant->id)
            ->where('lapangan_id', $lapangan->id)
            ->whereHas('booking')
            ->exists();

        if ($punyaBooking) {
            return redirect()->route('admin.lapangan')
                ->with('error', "Lapangan \"{$lapangan->nama}\" punya riwayat booking, tidak bisa dihapus. Nonaktifkan saja lewat form edit.");
        }

        $this->fotoUploadService->hapus($lapangan->foto_url);
        $lapangan->delete();

        return redirect()->route('admin.lapangan')
            ->with('success', "Lapangan \"{$lapangan->nama}\" berhasil dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, Tenant $tenant): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'jenis_olahraga' => ['required', Rule::in(self::JENIS_OLAHRAGA)],
            'harga_per_jam' => ['required', 'integer', 'min:0'],
            'harga_jam_sibuk' => ['nullable', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'foto' => ['nullable', 'image', 'max:4096'],
            'cabang_id' => $tenant->punyaFitur('multi_cabang')
                ? ['required', Rule::exists('cabang', 'id')->where('tenant_id', $tenant->id)]
                : ['nullable'],
        ]);
    }

    private function resolveCabangId(Request $request, Tenant $tenant): int
    {
        if ($tenant->punyaFitur('multi_cabang')) {
            return (int) $request->input('cabang_id');
        }

        return Cabang::where('tenant_id', $tenant->id)->value('id');
    }
}
