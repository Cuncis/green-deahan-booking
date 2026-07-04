<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Artikel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Kelola artikel di halaman /blog (situs korporat greendeahan.com). Bukan
 * fitur tenant, jadi tidak ada tenant_id sama sekali di sini, lihat
 * references/multi-tenant.md.
 */
class ArtikelAdminController extends Controller
{
    public function index(Request $request): View
    {
        $artikel = Artikel::orderByDesc('tanggal_terbit')
            ->when($request->query('cari'), fn ($q, $cari) => $q->where('judul', 'like', "%{$cari}%"))
            ->get();

        return view('pages.superadmin.artikel.index', [
            'artikel' => $artikel,
            'cari' => $request->query('cari'),
        ]);
    }

    public function create(): View
    {
        return view('pages.superadmin.artikel.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->buatSlugUnik($data['judul']);
        $data['status_aktif'] = true;

        if ($request->hasFile('foto')) {
            $data['foto_url'] = $this->simpanFoto($request);
        }

        $artikel = Artikel::create($data);

        return redirect()->route('superadmin.artikel')
            ->with('success', "Artikel \"{$artikel->judul}\" berhasil dibuat.");
    }

    public function edit(Artikel $artikel): View
    {
        return view('pages.superadmin.artikel.edit', [
            'artikel' => $artikel,
        ]);
    }

    public function update(Request $request, Artikel $artikel): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['status_aktif'] = $request->boolean('status_aktif');

        if ($request->hasFile('foto')) {
            $this->hapusFotoLama($artikel->foto_url);
            $data['foto_url'] = $this->simpanFoto($request);
        }

        $artikel->update($data);

        return redirect()->route('superadmin.artikel')
            ->with('success', "Artikel \"{$artikel->judul}\" berhasil diperbarui.");
    }

    public function destroy(Artikel $artikel): RedirectResponse
    {
        $this->hapusFotoLama($artikel->foto_url);
        $judul = $artikel->judul;
        $artikel->delete();

        return redirect()->route('superadmin.artikel')
            ->with('success', "Artikel \"{$judul}\" berhasil dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'judul' => ['required', 'string', 'max:200'],
            'kategori' => ['required', 'string', 'max:100'],
            'ringkasan' => ['required', 'string', 'max:500'],
            'konten' => ['required', 'string'],
            'tanggal_terbit' => ['required', 'date'],
            'foto' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    private function buatSlugUnik(string $judul): string
    {
        $dasar = Str::slug($judul);
        $slug = $dasar;
        $urutan = 1;

        while (Artikel::where('slug', $slug)->exists()) {
            $slug = "{$dasar}-{$urutan}";
            $urutan++;
        }

        return $slug;
    }

    /**
     * Sengaja pakai asset() (resolve dari host request saat ini), BUKAN
     * Storage::disk('public')->url() yang selalu balik ke APP_URL statis
     * dan bikin URL foto rusak/404 di domain manapun selain APP_URL itu
     * sendiri (situs korporat greendeahan.com bisa diakses dari beberapa
     * domain, lihat routes/web.php).
     */
    private function simpanFoto(Request $request): string
    {
        $path = $request->file('foto')->store('artikel', 'public');

        return asset('storage/'.$path);
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
