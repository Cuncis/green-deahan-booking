<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\CatatanPromosiRequest;
use App\Models\CatatanPromosi;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Catatan promosi dan iklan (rencana Google Ads, prompt gambar, ide kampanye).
 * Bukan fitur tenant, jadi tidak ada tenant_id. Hanya superadmin.
 */
class CatatanPromosiController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('superadmin/CatatanPromosi', [
            'catatan' => CatatanPromosi::query()
                ->orderByDesc('disematkan')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get()
                ->map(fn (CatatanPromosi $catatan) => [
                    'id' => $catatan->id,
                    'judul' => $catatan->judul,
                    'kategori' => $catatan->kategori,
                    'isi' => $catatan->isi,
                    'disematkan' => $catatan->disematkan,
                    'diubah' => $catatan->updated_at->format('d/m/Y H:i'),
                ])->all(),
            'kategori' => collect(CatatanPromosi::KATEGORI)->map(fn (string $label, string $nilai) => ['nilai' => $nilai, 'label' => $label])->values()->all(),
        ]);
    }

    public function store(CatatanPromosiRequest $request): RedirectResponse
    {
        $catatan = CatatanPromosi::create($request->validated());

        return back()->with('success', "Catatan \"{$catatan->judul}\" disimpan.");
    }

    public function update(CatatanPromosiRequest $request, CatatanPromosi $catatan): RedirectResponse
    {
        $catatan->update($request->validated());

        return back()->with('success', "Catatan \"{$catatan->judul}\" diperbarui.");
    }

    public function destroy(CatatanPromosi $catatan): RedirectResponse
    {
        $judul = $catatan->judul;
        $catatan->delete();

        return back()->with('success', "Catatan \"{$judul}\" dihapus.");
    }
}
