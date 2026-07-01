<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Membership;
use App\Models\ReminderLog;
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
     * Jumlah member dan persen diskon untuk tiap tier membership.
     *
     * @return array<int, array{tier: string, label: string, jumlah: int, persen: int}>
     */
    public function ringkasanMembership(): array
    {
        $tenant = app('tenant');

        return collect(['bronze', 'silver', 'gold'])->map(fn (string $tier) => [
            'tier' => $tier,
            'label' => ucfirst($tier),
            'jumlah' => Membership::where('tenant_id', $tenant->id)->where('tier', $tier)->count(),
            'persen' => Membership::persenDiskonUntukTier($tier),
        ])->all();
    }

    /**
     * Gabungan booking yang reminder-nya masih terjadwal (belum ada log
     * kirim) dengan reminder yang sudah tercatat di reminder_log. Selama
     * belum ada integrasi WhatsApp Business API, "terjadwal" berarti admin
     * masih perlu mengirim manual, lihat references/notifikasi-whatsapp.md.
     *
     * @return Collection<int, array{nama: string, waktu: string, status: string}>
     */
    public function daftarReminder(): Collection
    {
        $tenant = app('tenant');

        $terjadwal = Booking::where('tenant_id', $tenant->id)
            ->where('reminder_aktif', true)
            ->where('status_booking', '!=', 'dibatalkan')
            ->whereDoesntHave('reminderLogs')
            ->whereHas('slot', fn ($q) => $q->where('tanggal', '>=', now()->toDateString()))
            ->with(['slot.lapangan', 'customer'])
            ->get()
            ->map(fn (Booking $booking) => [
                'nama' => $booking->customer->nama,
                'waktu' => $booking->slot->tanggal->format('d/m/Y').', '.substr($booking->slot->jam_mulai, 0, 5),
                'status' => 'terjadwal',
            ]);

        $terkirim = ReminderLog::where('tenant_id', $tenant->id)
            ->with(['booking.customer', 'booking.slot'])
            ->latest('waktu_kirim')
            ->take(10)
            ->get()
            ->map(fn (ReminderLog $log) => [
                'nama' => $log->booking->customer->nama,
                'waktu' => $log->booking->slot->tanggal->format('d/m/Y').', '.substr($log->booking->slot->jam_mulai, 0, 5),
                'status' => $log->status,
            ]);

        return $terjadwal->concat($terkirim)->take(15);
    }

    /**
     * Ranking cabang berdasarkan pendapatan, untuk melihat cabang mana
     * yang paling produktif.
     *
     * @return Collection<int, array{cabang: Cabang, totalBooking: int, pendapatan: int, keterisian: int}>
     */
    public function perbandinganCabang(): Collection
    {
        $tenant = app('tenant');

        return Cabang::where('tenant_id', $tenant->id)->get()
            ->map(function (Cabang $cabang) use ($tenant) {
                $bookingQuery = Booking::where('tenant_id', $tenant->id)
                    ->whereHas('slot.lapangan', fn ($q) => $q->where('cabang_id', $cabang->id))
                    ->where('status_booking', '!=', 'dibatalkan');

                $slotQuery = JadwalSlot::where('tenant_id', $tenant->id)
                    ->whereHas('lapangan', fn ($q) => $q->where('cabang_id', $cabang->id))
                    ->whereBetween('tanggal', [
                        now()->startOfWeek()->toDateString(),
                        now()->endOfWeek()->toDateString(),
                    ]);

                $totalSlot = (clone $slotQuery)->count();
                $terisi = (clone $slotQuery)->where('status', 'booked')->count();

                return [
                    'cabang' => $cabang,
                    'totalBooking' => (clone $bookingQuery)->count(),
                    'pendapatan' => (clone $bookingQuery)->whereIn('status_booking', ['dikonfirmasi', 'selesai'])->sum('total_bayar'),
                    'keterisian' => $totalSlot > 0 ? (int) round($terisi / $totalSlot * 100) : 0,
                ];
            })
            ->sortByDesc('pendapatan')
            ->values();
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
        $statistikLengkap = $tenant->punyaFitur('laporan_pendapatan');

        return view('livewire.dashboard-admin', [
            'tenant' => $tenant,
            'bookingHariIni' => $this->bookingHariIni(),
            'menungguKonfirmasi' => $this->menungguKonfirmasi(),
            'bookingMingguIni' => $statistikLengkap ? $this->bookingMingguIni() : null,
            'tingkatKeterisian' => $statistikLengkap ? $this->tingkatKeterisian() : null,
            'bookingTerbaru' => $this->bookingTerbaru(),
            'jamRamai' => $statistikLengkap ? $this->jamRamai() : [],
            'daftarCabang' => $tenant->punyaFitur('multi_cabang') ? $this->daftarCabang() : collect(),
            'ringkasanMembership' => $tenant->punyaFitur('sistem_membership') ? $this->ringkasanMembership() : [],
            'daftarReminder' => $tenant->punyaFitur('reminder_otomatis') ? $this->daftarReminder() : collect(),
            'perbandinganCabang' => $tenant->punyaFitur('analitik_lanjutan') ? $this->perbandinganCabang() : collect(),
        ]);
    }
}
