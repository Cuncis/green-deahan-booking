<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Artikel;
use App\Models\GaleriItem;
use App\Models\Lapangan;
use App\Models\Tenant;
use App\Services\FotoUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Pustaka media (mirip WordPress): semua gambar di disk upload, lintas
 * tenant dan situs korporat. Daftar dibaca langsung dari disk, bukan dari
 * tabel, supaya foto lama yang sudah terlanjur diupload lewat galeri,
 * artikel, lapangan, dan logo tenant ikut tampil. Superadmin platform
 * memang boleh melihat semua aset, jadi tidak ada filter tenant_id di sini.
 */
class MediaAdminController extends Controller
{
    private const PER_HALAMAN = 24;

    /**
     * @var array<int, string>
     */
    private const EKSTENSI_GAMBAR = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];

    public function __construct(private readonly FotoUploadService $fotoUploadService) {}

    public function index(Request $request): View
    {
        $semua = $this->semuaMedia();
        $folderList = $semua->pluck('folder')->unique()->sort()->values();

        $keyword = trim((string) $request->query('q'));
        $folder = $request->query('folder');

        $disaring = $semua
            ->when($folder, fn (Collection $c) => $c->where('folder', $folder))
            ->when($keyword !== '', fn (Collection $c) => $c->filter(
                fn (array $media) => Str::contains(Str::lower($media['nama']), Str::lower($keyword))
            ))
            ->values();

        $halaman = LengthAwarePaginator::resolveCurrentPage();
        $items = new LengthAwarePaginator(
            $disaring->forPage($halaman, self::PER_HALAMAN)->values(),
            $disaring->count(),
            self::PER_HALAMAN,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('pages.superadmin.media.index', [
            'items' => $items,
            'folderList' => $folderList,
            'filterFolder' => $folder,
            'keyword' => $keyword,
            'totalMedia' => $semua->count(),
            'totalUkuran' => $semua->sum('ukuran'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['image', 'max:4096'],
        ]);

        foreach ($request->file('files') as $file) {
            $this->fotoUploadService->simpan($file, 'media');
        }

        $jumlah = count($request->file('files'));

        return redirect()->route('superadmin.media')
            ->with('success', "{$jumlah} file berhasil diupload.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $path = (string) $request->input('path');
        $disk = Storage::disk($this->namaDisk());

        $valid = $path !== ''
            && ! Str::contains($path, '..')
            && in_array(Str::lower(pathinfo($path, PATHINFO_EXTENSION)), self::EKSTENSI_GAMBAR, true)
            && $disk->exists($path);

        abort_unless($valid, 404);

        if ($this->pathDipakai()->has($path)) {
            return redirect()->back()
                ->with('error', 'File ini masih dipakai di galeri, blog, lapangan, atau logo tenant. Ganti atau hapus dulu dari sana.');
        }

        $disk->delete($path);

        return redirect()->back()->with('success', 'File berhasil dihapus.');
    }

    /**
     * Disk upload sama persis dengan yang dipakai FotoUploadService.
     */
    private function namaDisk(): string
    {
        return config('filesystems.default') === 's3' ? 's3' : 'public';
    }

    /**
     * @return Collection<int, array{path: string, nama: string, folder: string, url: string, ukuran: int, diubah: int, dipakai: bool}>
     */
    private function semuaMedia(): Collection
    {
        $namaDisk = $this->namaDisk();
        $disk = Storage::disk($namaDisk);
        $dipakai = $this->pathDipakai();

        return collect($disk->allFiles())
            ->filter(fn (string $path) => in_array(Str::lower(pathinfo($path, PATHINFO_EXTENSION)), self::EKSTENSI_GAMBAR, true))
            ->map(fn (string $path) => [
                'path' => $path,
                'nama' => basename($path),
                'folder' => Str::contains($path, '/') ? Str::before($path, '/') : '(root)',
                'url' => $namaDisk === 's3' ? $disk->url($path) : asset('storage/'.$path),
                'ukuran' => (int) $disk->size($path),
                'diubah' => (int) $disk->lastModified($path),
                'dipakai' => $dipakai->has($path),
            ])
            ->sortByDesc('diubah')
            ->values();
    }

    /**
     * Set path file yang masih direferensikan tabel manapun (kunci = path).
     *
     * @return Collection<string, true>
     */
    private function pathDipakai(): Collection
    {
        $urls = collect()
            ->merge(GaleriItem::query()->pluck('foto_url'))
            ->merge(Artikel::query()->pluck('foto_url'))
            ->merge(Lapangan::withoutGlobalScopes()->pluck('foto_url'))
            ->merge(Tenant::query()->pluck('logo_url'))
            ->filter();

        $base = $this->namaDisk() === 's3' ? Storage::disk('s3')->url('') : '/storage/';

        return $urls
            ->map(fn (string $url) => Str::after($url, $base))
            ->mapWithKeys(fn (string $path) => [$path => true]);
    }
}
