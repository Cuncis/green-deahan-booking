<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Laporan Pendapatan (fitur Pro laporan_pendapatan), sebelumnya Livewire
 * RevenueChart. React: resources/js/admin/pages/admin/Laporan.tsx.
 */
class LaporanAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('tenant');
        abort_unless($tenant->punyaFitur('laporan_pendapatan'), 404);

        $daftarCabang = $tenant->punyaFitur('multi_cabang')
            ? Cabang::where('tenant_id', $tenant->id)->orderBy('nama_cabang')->get(['id', 'nama_cabang'])
            : collect();
        $cabangId = $request->filled('cabang') && $daftarCabang->contains('id', (int) $request->query('cabang'))
            ? (int) $request->query('cabang')
            : null;

        return Inertia::render('admin/Laporan', [
            'filterCabang' => $daftarCabang->isNotEmpty() ? [
                'terpilih' => $cabangId,
                'pilihan' => $daftarCabang->map(fn (Cabang $cabang) => ['id' => $cabang->id, 'nama' => $cabang->nama_cabang])->values()->all(),
            ] : null,
            'pendapatanMingguan' => $this->pendapatanMingguan($tenant->id, $cabangId),
            'jamRamai' => $this->jamRamai($tenant->id, $cabangId),
        ]);
    }

    /**
     * Pendapatan 7 hari terakhir dari booking dikonfirmasi/selesai,
     * dikelompokkan per tanggal booking dibuat.
     *
     * @return array<int, array{tanggal: string, hari: string, pendapatan: int}>
     */
    public function pendapatanMingguan(int $tenantId, ?int $cabangId): array
    {
        $terkumpul = Booking::where('tenant_id', $tenantId)
            ->when($cabangId, fn ($q) => $q->whereHas('slot.lapangan', fn ($q2) => $q2->where('cabang_id', $cabangId)))
            ->whereIn('status_booking', ['dikonfirmasi', 'selesai'])
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->created_at->toDateString())
            ->map(fn ($group) => $group->sum('total_bayar'));

        $hasil = [];
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = now()->subDays($i);
            $hasil[] = [
                'tanggal' => $tanggal->toDateString(),
                'hari' => $tanggal->locale('id')->translatedFormat('D'),
                'pendapatan' => (int) ($terkumpul[$tanggal->toDateString()] ?? 0),
            ];
        }

        return $hasil;
    }

    /**
     * Jumlah slot booked per jam mulai (0-23), untuk melihat jam paling ramai.
     *
     * @return array<int, int>
     */
    public function jamRamai(int $tenantId, ?int $cabangId): array
    {
        $perJam = JadwalSlot::where('tenant_id', $tenantId)
            ->when($cabangId, fn ($q) => $q->whereHas('lapangan', fn ($q2) => $q2->where('cabang_id', $cabangId)))
            ->where('status', 'booked')
            ->get()
            ->groupBy(fn (JadwalSlot $slot) => (int) substr($slot->jam_mulai, 0, 2));

        $hasil = [];
        for ($jam = 0; $jam < 24; $jam++) {
            $hasil[$jam] = $perJam->has($jam) ? $perJam->get($jam)->count() : 0;
        }

        return $hasil;
    }
}
