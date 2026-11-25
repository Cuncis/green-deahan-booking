<?php

namespace App\Http\Controllers\Superadmin\Toko;

use App\Http\Controllers\Controller;
use App\Services\BiteshipService;
use App\Services\RajaOngkirService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengaturan ongkir toko: batas permintaan harian RajaOngkir (gratis 100 per hari),
 * pemakaian hari ini, dan perkiraan biaya Biteship yang jadi cadangannya. Hanya superadmin.
 */
class PengaturanOngkirController extends Controller
{
    public function __construct(
        private readonly RajaOngkirService $rajaOngkir,
        private readonly BiteshipService $biteship,
    ) {}

    public function edit(): Response
    {
        return Inertia::render('superadmin/Toko/PengaturanOngkir', [
            'rajaongkir' => $this->rajaOngkir->ringkasan(),
            'biteship' => ['aktif' => $this->biteship->aktif(), ...$this->biteship->pemakaian()],
            'pengaturan' => [
                'batas' => $this->rajaOngkir->batasHarian(),
                'sisaAman' => $this->rajaOngkir->sisaAman(),
                'batasEnv' => (int) config('rajaongkir.batas_harian'),
                'sisaAmanEnv' => (int) config('rajaongkir.sisa_aman'),
            ],
            'kurir' => (string) config('rajaongkir.couriers'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'batas' => ['required', 'integer', 'min:1', 'max:100000'],
            'sisa_aman' => ['required', 'integer', 'min:0', 'lt:batas'],
        ], [
            'sisa_aman.lt' => 'Sisa aman harus lebih kecil dari batas harian.',
        ], ['batas' => 'batas harian', 'sisa_aman' => 'sisa aman']);

        $this->rajaOngkir->simpanPengaturan((int) $data['batas'], (int) $data['sisa_aman']);

        return back()->with('success', 'Pengaturan ongkir disimpan.');
    }

    /**
     * Uji API key RajaOngkir dengan satu permintaan (dihitung ke kuota harian).
     */
    public function uji(): RedirectResponse
    {
        $hasil = $this->rajaOngkir->uji();

        return back()->with($hasil['berhasil'] ? 'success' : 'error', $hasil['pesan']);
    }

    /**
     * Cabut status "habis" supaya RajaOngkir dicoba lagi (hitungan hari ini tetap).
     */
    public function cobaLagi(): RedirectResponse
    {
        $this->rajaOngkir->cobaLagi();

        return back()->with('success', 'RajaOngkir akan dicoba lagi di permintaan berikutnya.');
    }

    /**
     * Nolkan hitungan hari ini dan status "habis", misal hitungan kita meleset dari catatan RajaOngkir.
     */
    public function setelUlang(): RedirectResponse
    {
        $this->rajaOngkir->setelUlangHariIni();

        return back()->with('success', 'Hitungan RajaOngkir hari ini disetel ulang ke 0.');
    }
}
