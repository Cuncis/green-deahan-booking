<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use Illuminate\Support\Collection;
use Livewire\Component;

class DashboardAdmin extends Component
{
    public ?int $cabangId = null;

    /**
     * Jumlah booking (bukan yang dibatalkan) dengan jadwal main hari ini.
     */
    public function bookingHariIni(): int
    {
        return $this->queryBooking()
            ->whereHas('slot', fn ($q) => $q->whereDate('tanggal', now()))
            ->count();
    }

    public function menungguKonfirmasi(): int
    {
        return $this->queryBooking()
            ->where('status_booking', 'menunggu')
            ->count();
    }

    public function bookingMingguIni(): int
    {
        return $this->queryBooking()
            ->whereHas('slot', fn ($q) => $q->whereBetween('tanggal', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]))
            ->count();
    }

    public function tingkatKeterisian(): int
    {
        $slot = JadwalSlot::where('tenant_id', app('tenant')->id)
            ->when($this->cabangId, fn ($q) => $q->whereHas('lapangan', fn ($q2) => $q2->where('cabang_id', $this->cabangId)))
            ->whereBetween('tanggal', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]);

        $total = (clone $slot)->count();

        if ($total === 0) {
            return 0;
        }

        $terisi = (clone $slot)->where('status', 'booked')->count();

        return (int) round($terisi / $total * 100);
    }

    /**
     * 10 booking terbaru, termasuk yang dibatalkan supaya admin tetap
     * bisa memantau semuanya (beda dengan statistik hari ini/minggu ini
     * yang sengaja tidak menghitung booking dibatalkan).
     *
     * @return Collection<int, Booking>
     */
    public function bookingTerbaru(): Collection
    {
        return $this->baseQuery()
            ->with(['slot.lapangan', 'customer'])
            ->latest()
            ->take(10)
            ->get();
    }

    /**
     * Pendapatan 7 hari terakhir dari booking yang sudah dikonfirmasi/selesai,
     * dikelompokkan per tanggal booking dibuat.
     *
     * @return array<string, int>
     */
    public function pendapatanMingguan(): array
    {
        $mulai = now()->subDays(6)->startOfDay();

        $terkumpul = $this->queryBooking()
            ->whereIn('status_booking', ['dikonfirmasi', 'selesai'])
            ->where('created_at', '>=', $mulai)
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->created_at->toDateString())
            ->map(fn ($group) => $group->sum('total_bayar'));

        $hasil = [];

        for ($i = 6; $i >= 0; $i--) {
            $tanggal = now()->subDays($i)->toDateString();
            $hasil[$tanggal] = (int) ($terkumpul[$tanggal] ?? 0);
        }

        return $hasil;
    }

    /**
     * @return Collection<int, Cabang>
     */
    public function daftarCabang(): Collection
    {
        return Cabang::where('tenant_id', app('tenant')->id)->get();
    }

    private function baseQuery()
    {
        return Booking::where('tenant_id', app('tenant')->id)
            ->when($this->cabangId, fn ($q) => $q->whereHas('slot.lapangan', fn ($q2) => $q2->where('cabang_id', $this->cabangId)));
    }

    private function queryBooking()
    {
        return $this->baseQuery()->where('status_booking', '!=', 'dibatalkan');
    }

    public function render()
    {
        $tenant = app('tenant');

        return view('livewire.dashboard-admin', [
            'bookingHariIni' => $this->bookingHariIni(),
            'menungguKonfirmasi' => $this->menungguKonfirmasi(),
            'bookingMingguIni' => $this->bookingMingguIni(),
            'tingkatKeterisian' => $this->tingkatKeterisian(),
            'bookingTerbaru' => $this->bookingTerbaru(),
            'pendapatanMingguan' => $tenant->punyaFitur('laporan_pendapatan') ? $this->pendapatanMingguan() : [],
            'daftarCabang' => $tenant->punyaFitur('multi_cabang') ? $this->daftarCabang() : collect(),
        ]);
    }
}
