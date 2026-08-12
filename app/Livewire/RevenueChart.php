<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use Illuminate\Support\Collection;
use Livewire\Component;

class RevenueChart extends Component
{
    public ?int $cabangId = null;

    /**
     * Pendapatan 7 hari terakhir dari booking yang sudah dikonfirmasi/selesai,
     * dikelompokkan per tanggal booking dibuat.
     *
     * @return array<string, int>
     */
    public function pendapatanMingguan(): array
    {
        $tenant = app('tenant');
        $mulai = now()->subDays(6)->startOfDay();

        $terkumpul = Booking::where('tenant_id', $tenant->id)
            ->when($this->cabangId, fn ($q) => $q->whereHas('slot.lapangan', fn ($q2) => $q2->where('cabang_id', $this->cabangId)))
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
     * Jumlah slot yang sudah dibooking, dikelompokkan per jam mulai,
     * untuk melihat jam berapa yang paling ramai.
     *
     * @return array<int, int>
     */
    public function jamRamai(): array
    {
        $perJam = JadwalSlot::where('tenant_id', app('tenant')->id)
            ->when($this->cabangId, fn ($q) => $q->whereHas('lapangan', fn ($q2) => $q2->where('cabang_id', $this->cabangId)))
            ->where('status', 'booked')
            ->get()
            ->groupBy(fn (JadwalSlot $slot) => (int) substr($slot->jam_mulai, 0, 2));

        $hasil = [];

        for ($jam = 0; $jam < 24; $jam++) {
            $hasil[$jam] = $perJam->has($jam) ? $perJam->get($jam)->count() : 0;
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

    public function render()
    {
        $tenant = app('tenant');

        return view('livewire.revenue-chart', [
            'tenant' => $tenant,
            'pendapatanMingguan' => $this->pendapatanMingguan(),
            'jamRamai' => $this->jamRamai(),
            'daftarCabang' => $tenant->punyaFitur('multi_cabang') ? $this->daftarCabang() : collect(),
        ]);
    }
}
