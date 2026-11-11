<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Services\HargaBookingService;
use App\Services\SlotBookingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman booking customer di domain tenant (React,
 * resources/js/booking/pages/Booking.tsx). Semua aksi lewat API
 * BookingController, lihat SlotBookingService dan HargaBookingService.
 */
class HalamanBookingController extends Controller
{
    public const HARI_DITAMPILKAN = 7;

    public function __construct(private readonly HargaBookingService $hargaBooking) {}

    public function index(Request $request): Response
    {
        $tenant = app('tenant');

        $lapangan = Lapangan::with('cabang')
            ->where('tenant_id', $tenant->id)
            ->where('status_aktif', true)
            ->orderBy('nama')
            ->get();

        $lapanganAktif = $lapangan->firstWhere('id', (int) $request->query('lapangan')) ?? $lapangan->first();

        $multiCabang = $tenant->punyaFitur('multi_cabang');
        $hariIni = SlotBookingService::hariIni();

        return Inertia::render('Booking', [
            'bisnis' => [
                'nama' => $tenant->nama_bisnis,
                'logoUrl' => $tenant->logo_url,
                'warnaUtama' => $tenant->warna_utama ?: '#3A6B4A',
                'whatsapp' => $tenant->whatsapp_admin ? preg_replace('/\D/', '', $tenant->whatsapp_admin) : null,
            ],
            'fitur' => [
                'multiCabang' => $multiCabang,
                'kodePromo' => $tenant->punyaFitur('kode_promo'),
                'membership' => $tenant->punyaFitur('sistem_membership'),
                'reminder' => $tenant->punyaFitur('reminder_otomatis'),
                'tipePembayaran' => $this->hargaBooking->tipePembayaranTersedia($tenant),
            ],
            'cabang' => $multiCabang
                ? Cabang::where('tenant_id', $tenant->id)->where('status_aktif', true)->orderBy('nama_cabang')->get()
                    ->filter(fn (Cabang $cabang) => $lapangan->contains('cabang_id', $cabang->id))
                    ->map(fn (Cabang $cabang) => ['id' => $cabang->id, 'nama' => $cabang->nama_cabang])
                    ->values()
                    ->all()
                : [],
            'lapangan' => $lapangan->map(fn (Lapangan $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'jenis' => $item->jenis_olahraga,
                'hargaPerJam' => (int) $item->harga_per_jam,
                'fotoUrl' => $item->foto_url,
                'cabangId' => $item->cabang_id,
                'alamat' => $item->cabang?->alamat,
                'jamBuka' => $item->cabang ? substr($item->cabang->jam_buka, 0, 5) : null,
                'jamTutup' => $item->cabang ? substr($item->cabang->jam_tutup, 0, 5) : null,
            ])->values()->all(),
            'lapanganAwal' => $lapanganAktif?->id,
            'tanggalPilihan' => collect(range(0, self::HARI_DITAMPILKAN - 1))
                ->map(fn (int $hari) => $hariIni->copy()->addDays($hari))
                ->map(fn ($tanggal) => [
                    'tanggal' => $tanggal->toDateString(),
                    'hari' => $tanggal->locale('id')->translatedFormat('D'),
                    'angka' => $tanggal->day,
                    'label' => $tanggal->locale('id')->translatedFormat('l, j F'),
                ])
                ->all(),
            'kodeBookingKembali' => is_string($request->query('kode_booking')) ? $request->query('kode_booking') : null,
            'menitHold' => SlotBookingService::MENIT_HOLD,
            'persenDp' => HargaBookingService::PERSEN_DP,
            'meta' => [
                'title' => "Booking Lapangan, {$tenant->nama_bisnis}",
                'description' => "Booking lapangan di {$tenant->nama_bisnis} secara online. Pilih jam, bayar, selesai.",
            ],
        ]);
    }
}
