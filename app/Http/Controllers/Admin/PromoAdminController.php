<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KodePromo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kode promo (fitur Pro kode_promo), sebelumnya Livewire KodePromoManager.
 * React: resources/js/admin/pages/admin/Promo.tsx.
 */
class PromoAdminController extends Controller
{
    public function index(): Response
    {
        $tenant = app('tenant');
        abort_unless($tenant->punyaFitur('kode_promo'), 404);

        return Inertia::render('admin/Promo', [
            'promo' => KodePromo::where('tenant_id', $tenant->id)->latest()->get()
                ->map(fn (KodePromo $promo) => [
                    'id' => $promo->id,
                    'kode' => $promo->kode,
                    'tipe' => $promo->tipe_diskon,
                    'nilai' => (int) $promo->nilai,
                    'mulai' => $promo->tanggal_mulai->format('d/m/Y'),
                    'berakhir' => $promo->tanggal_berakhir->format('d/m/Y'),
                    'kuota' => $promo->kuota,
                    'aktif' => (bool) $promo->status_aktif,
                ])->all(),
            'bawaan' => [
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_berakhir' => now()->addMonth()->toDateString(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app('tenant');
        abort_unless($tenant->punyaFitur('kode_promo'), 404);

        $data = $request->validate([
            'kode' => ['required', 'string', 'max:50'],
            'tipe_diskon' => ['required', 'in:persen,nominal'],
            'nilai' => ['required', 'integer', 'min:1', Rule::when($request->input('tipe_diskon') === 'persen', ['max:100'])],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_berakhir' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'kuota' => ['nullable', 'integer', 'min:1'],
        ]);

        $kode = strtoupper($data['kode']);

        if (KodePromo::where('tenant_id', $tenant->id)->where('kode', $kode)->exists()) {
            throw ValidationException::withMessages(['kode' => 'Kode promo ini sudah dipakai, coba kode lain.']);
        }

        KodePromo::create([
            'tenant_id' => $tenant->id,
            'kode' => $kode,
            'tipe_diskon' => $data['tipe_diskon'],
            'nilai' => $data['nilai'],
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_berakhir' => $data['tanggal_berakhir'],
            'kuota' => $data['kuota'] ?? null,
            'status_aktif' => true,
        ]);

        return back()->with('success', "Kode promo {$kode} ditambahkan.");
    }
}
