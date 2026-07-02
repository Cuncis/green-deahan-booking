<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LapanganAdminController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const JENIS_OLAHRAGA = ['futsal', 'padel', 'badminton', 'tennis', 'mini soccer'];

    public function index(): View
    {
        $tenant = app('tenant');

        return view('pages.admin.lapangan.index', [
            'tenant' => $tenant,
            'daftarLapangan' => Lapangan::where('tenant_id', $tenant->id)->with('cabang')->orderBy('nama')->get(),
            'batasLapangan' => $tenant->fitur?->batas_lapangan,
            'jumlahLapangan' => Lapangan::where('tenant_id', $tenant->id)->count(),
        ]);
    }

    public function create(): View
    {
        $tenant = app('tenant');

        return view('pages.admin.lapangan.create', [
            'tenant' => $tenant,
            'daftarCabang' => $tenant->punyaFitur('multi_cabang')
                ? Cabang::where('tenant_id', $tenant->id)->orderBy('nama_cabang')->get()
                : collect(),
            'jenisOlahraga' => self::JENIS_OLAHRAGA,
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
            $data['foto_url'] = $this->simpanFoto($request);
        }

        Lapangan::create($data);

        return redirect()->route('admin.lapangan')
            ->with('success', "Lapangan \"{$data['nama']}\" berhasil ditambahkan.");
    }

    public function edit(Lapangan $lapangan): View
    {
        $tenant = app('tenant');

        abort_unless($lapangan->tenant_id === $tenant->id, 404);

        return view('pages.admin.lapangan.edit', [
            'tenant' => $tenant,
            'lapangan' => $lapangan,
            'daftarCabang' => $tenant->punyaFitur('multi_cabang')
                ? Cabang::where('tenant_id', $tenant->id)->orderBy('nama_cabang')->get()
                : collect(),
            'jenisOlahraga' => self::JENIS_OLAHRAGA,
        ]);
    }

    public function update(Request $request, Lapangan $lapangan): RedirectResponse
    {
        $tenant = app('tenant');

        abort_unless($lapangan->tenant_id === $tenant->id, 404);

        $data = $this->validateData($request, $tenant);

        $data['status_aktif'] = $request->boolean('status_aktif');
        $data['cabang_id'] = $this->resolveCabangId($request, $tenant);

        if ($request->hasFile('foto')) {
            $this->hapusFotoLama($lapangan->foto_url);
            $data['foto_url'] = $this->simpanFoto($request);
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

        $this->hapusFotoLama($lapangan->foto_url);
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

    private function simpanFoto(Request $request): string
    {
        $path = $request->file('foto')->store('lapangan', 'public');

        return Storage::disk('public')->url($path);
    }

    private function hapusFotoLama(?string $fotoUrl): void
    {
        if (! $fotoUrl) {
            return;
        }

        $path = Str::after($fotoUrl, '/storage/');

        if ($path && $path !== $fotoUrl) {
            Storage::disk('public')->delete($path);
        }
    }
}
