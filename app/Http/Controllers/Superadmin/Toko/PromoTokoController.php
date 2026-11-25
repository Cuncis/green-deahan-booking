<?php

namespace App\Http\Controllers\Superadmin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\Toko\PromoTokoRequest;
use App\Models\PromoCode;
use App\Services\PromoTokoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kode promo toko online (CRUD sederhana, form di dialog). Hanya superadmin.
 */
class PromoTokoController extends Controller
{
    public function index(PromoTokoService $layanan): Response
    {
        $terpakai = $layanan->terpakaiSemua();

        return Inertia::render('superadmin/Toko/Promo', [
            'promo' => PromoCode::query()->latest('id')->get()->map(fn (PromoCode $promo) => [
                'id' => $promo->id,
                'code' => $promo->code,
                'description' => $promo->description,
                'type' => $promo->type,
                'value' => $promo->value,
                'max_discount' => $promo->max_discount,
                'min_subtotal' => $promo->min_subtotal,
                'quota' => $promo->quota,
                'starts_on' => $promo->starts_on?->toDateString(),
                'ends_on' => $promo->ends_on?->toDateString(),
                'is_active' => $promo->is_active,
                'terpakai' => $terpakai[$promo->code] ?? 0,
                'ringkasan' => $promo->ringkasan(),
            ])->all(),
        ]);
    }

    public function store(PromoTokoRequest $request): RedirectResponse
    {
        $promo = PromoCode::create($request->dataPromo());

        return back()->with('success', "Kode promo {$promo->code} ditambahkan.");
    }

    public function update(PromoTokoRequest $request, PromoCode $promo): RedirectResponse
    {
        // Kode tidak bisa diganti: pemakaian dihitung per kode di pesanan, jadi mengganti kode akan mengosongkan kuota.
        $promo->update(Arr::except($request->dataPromo(), 'code'));

        return back()->with('success', "Kode promo {$promo->code} diperbarui.");
    }

    public function destroy(PromoCode $promo): RedirectResponse
    {
        $kode = $promo->code;
        $promo->delete();

        return back()->with('success', "Kode promo {$kode} dihapus. Pesanan yang sudah memakainya tidak berubah.");
    }
}
