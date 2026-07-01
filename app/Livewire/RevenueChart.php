<?php

namespace App\Livewire;

use App\Models\Booking;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class RevenueChart extends Component
{
    #[Reactive]
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

    public function render()
    {
        return view('livewire.revenue-chart', [
            'pendapatanMingguan' => $this->pendapatanMingguan(),
        ]);
    }
}
