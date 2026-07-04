<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\GaleriItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Kelola portofolio proyek di halaman /galeri (situs korporat
 * greendeahan.com). Bukan fitur tenant, jadi tidak ada tenant_id sama
 * sekali di sini, lihat references/multi-tenant.md.
 */
class GaleriAdminController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const KATEGORI = ['futsal', 'minisoccer', 'padel', 'badminton', 'proses'];

    public function index(Request $request): View
    {
        $items = GaleriItem::orderBy('urutan')
            ->orderByDesc('id')
            ->when($request->query('kategori'), fn ($q, $kategori) => $q->where('kategori', $kategori))
            ->get();

        return view('pages.superadmin.galeri.index', [
            'items' => $items,
            'filterKategori' => $request->query('kategori'),
            'kategoriList' => self::KATEGORI,
        ]);
    }

    public function create(): View
    {
        return view('pages.superadmin.galeri.create', [
            'kategoriList' => self::KATEGORI,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request, fotoWajib: true);
        $data['foto_url'] = $this->simpanFoto($request);
        $data['tampilan_besar'] = $request->boolean('tampilan_besar');
        $data['status_aktif'] = true;

        $item = GaleriItem::create($data);

        return redirect()->route('superadmin.galeri')
            ->with('success', "Item galeri \"{$item->judul}\" berhasil ditambahkan.");
    }

    public function edit(GaleriItem $galeri): View
    {
        return view('pages.superadmin.galeri.edit', [
            'item' => $galeri,
            'kategoriList' => self::KATEGORI,
        ]);
    }

    public function update(Request $request, GaleriItem $galeri): RedirectResponse
    {
        $data = $this->validateData($request, fotoWajib: false);
        $data['tampilan_besar'] = $request->boolean('tampilan_besar');
        $data['status_aktif'] = $request->boolean('status_aktif');

        if ($request->hasFile('foto')) {
            $this->hapusFotoLama($galeri->foto_url);
            $data['foto_url'] = $this->simpanFoto($request);
        }

        $galeri->update($data);

        return redirect()->route('superadmin.galeri')
            ->with('success', "Item galeri \"{$galeri->judul}\" berhasil diperbarui.");
    }

    public function destroy(GaleriItem $galeri): RedirectResponse
    {
        $this->hapusFotoLama($galeri->foto_url);
        $judul = $galeri->judul;
        $galeri->delete();

        return redirect()->route('superadmin.galeri')
            ->with('success', "Item galeri \"{$judul}\" berhasil dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, bool $fotoWajib): array
    {
        return $request->validate([
            'kategori' => ['required', Rule::in(self::KATEGORI)],
            'judul' => ['required', 'string', 'max:150'],
            'kota' => ['required', 'string', 'max:150'],
            'material' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string', 'max:1000'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'foto' => [$fotoWajib ? 'required' : 'nullable', 'image', 'max:4096'],
        ]);
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
        $path = $request->file('foto')->store('galeri', 'public');

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
