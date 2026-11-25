<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\BannerPromoRequest;
use App\Models\BannerPromo;
use App\Services\FotoUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelola banner pop-up promo situs korporat (greendeahan.com). Bukan fitur
 * tenant, jadi tidak ada tenant_id. Hanya superadmin.
 */
class BannerPromoController extends Controller
{
    public function __construct(private readonly FotoUploadService $foto) {}

    public function index(): Response
    {
        $tampil = BannerPromo::yangTampil();

        return Inertia::render('superadmin/BannerPromo', [
            'banner' => BannerPromo::query()->latest('id')->get()->map(fn (BannerPromo $banner) => [
                'id' => $banner->id,
                'judul' => $banner->judul,
                'fotoUrl' => $banner->foto_url,
                'tautan' => $banner->tautan,
                'label_tombol' => $banner->label_tombol,
                'tampilkan_tombol' => $banner->tampilkan_tombol,
                'mulai_pada' => $banner->mulai_pada?->format('Y-m-d\TH:i'),
                'berakhir_pada' => $banner->berakhir_pada?->format('Y-m-d\TH:i'),
                'target' => $banner->target,
                'frekuensi' => $banner->frekuensi,
                'tunda_detik' => $banner->tunda_detik,
                'status_aktif' => $banner->status_aktif,
                'status' => $banner->status(),
                'sedangTampil' => $tampil?->is($banner) ?? false,
            ])->all(),
        ]);
    }

    public function store(BannerPromoRequest $request): RedirectResponse
    {
        $data = Arr::except($request->validated(), ['foto', 'media_path']);
        $data['foto_url'] = $this->fotoBaru($request);

        $banner = BannerPromo::create($data);

        return back()->with('success', "Banner \"{$banner->judul}\" ditambahkan.");
    }

    public function update(BannerPromoRequest $request, BannerPromo $banner): RedirectResponse
    {
        $data = Arr::except($request->validated(), ['foto', 'media_path']);

        if ($request->hasFile('foto') || $request->filled('media_path')) {
            $lama = $banner->foto_url;
            $data['foto_url'] = $this->fotoBaru($request);

            if ($data['foto_url'] !== $lama) {
                $this->hapusFotoBanner($lama, $banner);
            }
        }

        $banner->update($data);

        return back()->with('success', "Banner \"{$banner->judul}\" diperbarui.");
    }

    public function destroy(BannerPromo $banner): RedirectResponse
    {
        $judul = $banner->judul;
        $banner->delete();
        $this->hapusFotoBanner($banner->foto_url, $banner);

        return back()->with('success', "Banner \"{$judul}\" dihapus.");
    }

    /**
     * Alamat gambar dari upload (disimpan di folder banner) atau dari file pustaka Media yang dipilih.
     */
    private function fotoBaru(BannerPromoRequest $request): string
    {
        if ($request->hasFile('foto')) {
            return $this->foto->simpan($request->file('foto'), 'banner');
        }

        return FotoUploadService::urlDariPath((string) $request->input('media_path'));
    }

    /**
     * Hanya file yang diupload lewat form banner (folder banner) yang ikut dihapus, dan hanya
     * kalau tidak dipakai banner lain. File dari pustaka Media bisa dipakai galeri, blog, dan
     * lainnya, jadi tidak pernah dihapus dari sini.
     */
    private function hapusFotoBanner(?string $url, BannerPromo $banner): void
    {
        $path = FotoUploadService::pathDariUrl($url);

        if ($path === null || ! str_starts_with($path, 'banner/')) {
            return;
        }

        if (BannerPromo::query()->where('foto_url', $url)->whereKeyNot($banner->getKey())->exists()) {
            return;
        }

        $this->foto->hapus($url);
    }
}
