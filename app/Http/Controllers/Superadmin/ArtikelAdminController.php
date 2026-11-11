<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Artikel;
use App\Services\FotoUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelola artikel di halaman /blog (situs korporat greendeahan.com). Bukan
 * fitur tenant, jadi tidak ada tenant_id sama sekali di sini, lihat
 * references/multi-tenant.md.
 */
class ArtikelAdminController extends Controller
{
    public function __construct(private readonly FotoUploadService $fotoUploadService) {}

    public function index(Request $request): Response
    {
        $cari = is_string($request->query('cari')) ? trim($request->query('cari')) : null;

        return Inertia::render('superadmin/Artikel/Index', [
            'artikel' => Artikel::orderByDesc('tanggal_terbit')
                ->orderByDesc('id')
                ->when($cari, fn ($q, $cari) => $q->where('judul', 'like', "%{$cari}%"))
                ->get()
                ->map(fn (Artikel $artikel) => [
                    'id' => $artikel->id,
                    'judul' => $artikel->judul,
                    'kategori' => $artikel->kategori,
                    'terbit' => $artikel->tanggal_terbit->format('d/m/Y'),
                    'aktif' => (bool) $artikel->status_aktif,
                    'fotoUrl' => $artikel->foto_url,
                    'publikUrl' => route('blog.show', ['slug' => $artikel->slug]),
                    'editUrl' => route('superadmin.artikel.edit', $artikel),
                    'hapusUrl' => route('superadmin.artikel.destroy', $artikel),
                ])
                ->all(),
            'cari' => $cari ?: null,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('superadmin/Artikel/Form', [
            'artikel' => null,
            ...$this->dataForm(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->buatSlugUnik($data['judul']);
        $data['status_aktif'] = true;

        if ($request->hasFile('foto')) {
            $data['foto_url'] = $this->fotoUploadService->simpan($request->file('foto'), 'artikel');
        }

        $artikel = Artikel::create($data);

        return redirect()->route('superadmin.artikel')
            ->with('success', "Artikel \"{$artikel->judul}\" berhasil dibuat.");
    }

    public function edit(Artikel $artikel): Response
    {
        return Inertia::render('superadmin/Artikel/Form', [
            'artikel' => [
                'judul' => $artikel->judul,
                'kategori' => $artikel->kategori,
                'ringkasan' => $artikel->ringkasan,
                'konten' => $artikel->konten,
                'tanggal_terbit' => $artikel->tanggal_terbit->toDateString(),
                'status_aktif' => (bool) $artikel->status_aktif,
                'slug' => $artikel->slug,
                'fotoUrl' => $artikel->foto_url,
                'publikUrl' => route('blog.show', ['slug' => $artikel->slug]),
                'updateUrl' => route('superadmin.artikel.update', $artikel),
            ],
            ...$this->dataForm(),
        ]);
    }

    public function update(Request $request, Artikel $artikel): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['status_aktif'] = $request->boolean('status_aktif');

        if ($request->hasFile('foto')) {
            $this->fotoUploadService->hapus($artikel->foto_url);
            $data['foto_url'] = $this->fotoUploadService->simpan($request->file('foto'), 'artikel');
        }

        $artikel->update($data);

        return redirect()->route('superadmin.artikel')
            ->with('success', "Artikel \"{$artikel->judul}\" berhasil diperbarui.");
    }

    public function destroy(Artikel $artikel): RedirectResponse
    {
        $this->fotoUploadService->hapus($artikel->foto_url);
        $judul = $artikel->judul;
        $artikel->delete();

        return redirect()->route('superadmin.artikel')
            ->with('success', "Artikel \"{$judul}\" berhasil dihapus.");
    }

    /**
     * @return array{kategoriDipakai: array<int, string>, tanggalHariIni: string, simpanUrl: string, kembaliUrl: string}
     */
    private function dataForm(): array
    {
        return [
            'kategoriDipakai' => Artikel::query()->distinct()->orderBy('kategori')->pluck('kategori')->all(),
            'tanggalHariIni' => now()->toDateString(),
            'simpanUrl' => route('superadmin.artikel.store'),
            'kembaliUrl' => route('superadmin.artikel'),
        ];
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
}
