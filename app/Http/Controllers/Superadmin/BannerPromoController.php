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
        $data = Arr::except($request->validated(), 'foto');
        $data['foto_url'] = $this->foto->simpan($request->file('foto'), 'banner');

        $banner = BannerPromo::create($data);

        return back()->with('success', "Banner \"{$banner->judul}\" ditambahkan.");
    }

    public function update(BannerPromoRequest $request, BannerPromo $banner): RedirectResponse
    {
        $data = Arr::except($request->validated(), 'foto');

        if ($request->hasFile('foto')) {
            $this->foto->hapus($banner->foto_url);
            $data['foto_url'] = $this->foto->simpan($request->file('foto'), 'banner');
        }

        $banner->update($data);

        return back()->with('success', "Banner \"{$banner->judul}\" diperbarui.");
    }

    public function destroy(BannerPromo $banner): RedirectResponse
    {
        $judul = $banner->judul;
        $this->foto->hapus($banner->foto_url);
        $banner->delete();

        return back()->with('success', "Banner \"{$judul}\" dihapus.");
    }
}
