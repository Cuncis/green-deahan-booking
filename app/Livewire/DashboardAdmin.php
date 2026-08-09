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
     * Gabungan booking yang reminder-nya masih terjadwal (belum ada baris
     * reminder_log sama sekali, mis. data demo yang dibuat langsung lewat
     * factory) dengan reminder yang sudah tercatat di reminder_log,
     * dikelompokkan berdasar status sebenarnya: perlu_dikirim (menunggu
     * dan waktu_kirim sudah lewat, staf perlu klik kirim sekarang),
     * terjadwal (menunggu tapi belum waktunya), terkirim/gagal (sudah
     * diproses). Selama belum ada integrasi WhatsApp Business API,
     * "perlu_dikirim" berarti admin masih perlu mengirim manual lewat
     * tombol di widget ini, lihat references/notifikasi-whatsapp.md.
     *
     * @return Collection<int, array{id: ?int, nama: string, no_telepon: ?string, lapangan: string, waktu: string, pesan: ?string, status: string}>
     */
    public function daftarReminder(): Collection
    {
        $tenant = app('tenant');

        $terjadwalBelumAdaLog = Booking::where('tenant_id', $tenant->id)
            ->where('reminder_aktif', true)
            ->where('status_booking', '!=', 'dibatalkan')
            ->whereDoesntHave('reminderLogs')
            ->whereHas('slot', fn ($q) => $q->where('tanggal', '>=', now()->toDateString()))
            ->with(['slot.lapangan', 'customer'])
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => null,
                'nama' => $booking->customer->nama,
                'no_telepon' => $booking->customer->no_telepon,
                'lapangan' => $booking->slot->lapangan->nama,
                'waktu' => $booking->slot->tanggal->format('d/m/Y').', '.substr($booking->slot->jam_mulai, 0, 5),
                'pesan' => null,
                'status' => 'terjadwal',
            ]);

        $baseLogQuery = fn () => ReminderLog::where('tenant_id', $tenant->id)
            ->with(['booking.customer', 'booking.slot.lapangan']);

        $perluDikirim = $baseLogQuery()
            ->where('status', 'menunggu')
            ->where('waktu_kirim', '<=', now())
            ->orderBy('waktu_kirim')
            ->get()
            ->map(fn (ReminderLog $log) => $this->formatReminderLog($log, 'perlu_dikirim'));

        $terjadwalDenganLog = $baseLogQuery()
            ->where('status', 'menunggu')
            ->where('waktu_kirim', '>', now())
            ->orderBy('waktu_kirim')
            ->take(10)
            ->get()
            ->map(fn (ReminderLog $log) => $this->formatReminderLog($log, 'terjadwal'));

        $terkirim = $baseLogQuery()
            ->whereIn('status', ['terkirim', 'gagal'])
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(fn (ReminderLog $log) => $this->formatReminderLog($log, $log->status));

        return $perluDikirim
            ->concat($terjadwalBelumAdaLog)
            ->concat($terjadwalDenganLog)
            ->concat($terkirim)
            ->take(20);
    }

    /**
     * @return array{id: int, nama: string, no_telepon: ?string, lapangan: string, waktu: string, pesan: string, status: string}
     */
    private function formatReminderLog(ReminderLog $log, string $status): array
    {
        return [
            'id' => $log->id,
            'nama' => $log->booking->customer->nama,
            'no_telepon' => $log->booking->customer->no_telepon,
            'lapangan' => $log->booking->slot->lapangan->nama,
            'waktu' => $log->booking->slot->tanggal->format('d/m/Y').', '.substr($log->booking->slot->jam_mulai, 0, 5),
            'pesan' => $log->pesan,
            'status' => $status,
        ];
    }

    /**
     * Tandai satu reminder sudah dikirim manual lewat tombol "Kirim
     * Sekarang" di widget (lihat dashboard-admin.blade.php), dipanggil
     * bareng window.bukaChatWhatsApp() lewat x-on:click supaya satu klik
     * langsung buka chat WhatsApp DAN tandai terkirim. Guard
     * status='menunggu' bikin ini aman diklik dua kali (tidak menimpa baris
     * yang sudah terkirim/gagal), dan tenant_id eksplisit (bukan cuma
     * andalkan global scope) sesuai references/multi-tenant.md.
     */
    public function tandaiTerkirim(int $reminderLogId): void
    {
        ReminderLog::where('tenant_id', app('tenant')->id)
            ->where('id', $reminderLogId)
            ->where('status', 'menunggu')
            ->update(['status' => 'terkirim']);
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
