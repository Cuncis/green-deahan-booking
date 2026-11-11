<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Membership;
use App\Models\ReminderLog;
use App\Models\Staf;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Data dashboard admin tenant (sebelumnya Livewire DashboardAdmin). Semua
 * query difilter tenant_id secara eksplisit (references/multi-tenant.md),
 * panel fitur berbayar hanya dihitung kalau tenant punyaFitur(), jadi
 * tenant Basic tidak pernah menerima data panel Pro/Premium.
 */
class DashboardAdminService
{
    /**
     * @return array<string, mixed>
     */
    public function data(Tenant $tenant, ?int $cabangId): array
    {
        $multiCabang = $tenant->punyaFitur('multi_cabang');
        $daftarCabang = $multiCabang ? Cabang::where('tenant_id', $tenant->id)->orderBy('nama_cabang')->get(['id', 'nama_cabang']) : collect();

        // Filter cabang hanya berlaku untuk cabang milik tenant ini.
        $cabangId = $multiCabang && $daftarCabang->contains('id', $cabangId) ? $cabangId : null;
        $statistikLengkap = $tenant->punyaFitur('laporan_pendapatan');

        return [
            'filterCabang' => $multiCabang ? [
                'terpilih' => $cabangId,
                'pilihan' => $daftarCabang->map(fn (Cabang $cabang) => ['id' => $cabang->id, 'nama' => $cabang->nama_cabang])->values()->all(),
            ] : null,
            'statistik' => [
                'bookingHariIni' => $this->queryBooking($tenant, $cabangId)
                    ->whereHas('slot', fn ($q) => $q->whereDate('tanggal', now()))
                    ->count(),
                'menungguKonfirmasi' => $this->queryBooking($tenant, $cabangId)->where('status_booking', 'menunggu')->count(),
                'bookingMingguIni' => $statistikLengkap ? $this->bookingMingguIni($tenant, $cabangId) : null,
                'tingkatKeterisian' => $statistikLengkap ? $this->tingkatKeterisian($tenant, $cabangId) : null,
            ],
            'bookingTerbaru' => $this->bookingTerbaru($tenant, $cabangId),
            'perbandinganCabang' => $tenant->punyaFitur('analitik_lanjutan') ? $this->perbandinganCabang($tenant) : null,
            'staf' => $tenant->punyaFitur('manajemen_staf') ? $this->daftarStaf($tenant) : null,
            'membership' => $tenant->punyaFitur('sistem_membership') ? $this->ringkasanMembership($tenant) : null,
            'reminder' => $tenant->punyaFitur('reminder_otomatis') ? $this->daftarReminder($tenant)->values()->all() : null,
        ];
    }

    private function bookingMingguIni(Tenant $tenant, ?int $cabangId): int
    {
        return $this->queryBooking($tenant, $cabangId)
            ->whereHas('slot', fn ($q) => $q->whereBetween('tanggal', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]))
            ->count();
    }

    private function tingkatKeterisian(Tenant $tenant, ?int $cabangId): int
    {
        $slot = JadwalSlot::where('tenant_id', $tenant->id)
            ->when($cabangId, fn ($q) => $q->whereHas('lapangan', fn ($q2) => $q2->where('cabang_id', $cabangId)))
            ->whereBetween('tanggal', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ]);

        $total = (clone $slot)->count();

        if ($total === 0) {
            return 0;
        }

        return (int) round((clone $slot)->where('status', 'booked')->count() / $total * 100);
    }

    /**
     * 10 booking terbaru, termasuk yang dibatalkan supaya admin tetap bisa
     * memantau semuanya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function bookingTerbaru(Tenant $tenant, ?int $cabangId): array
    {
        return $this->baseQuery($tenant, $cabangId)
            ->with(['slot.lapangan', 'customer'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'kode' => $booking->kode_booking,
                'customer' => $booking->customer->nama,
                'telepon' => $booking->customer->no_telepon,
                'waUrl' => 'https://wa.me/'.preg_replace('/[^0-9]/', '', (string) $booking->customer->no_telepon),
                'lapangan' => $booking->slot->lapangan->nama,
                'jadwal' => $booking->slot->tanggal->format('d/m/Y').', '.substr($booking->slot->jam_mulai, 0, 5),
                'status' => $booking->status_booking,
                'total' => (int) $booking->total_bayar,
                'bisaDikonfirmasi' => $booking->status_booking === 'menunggu',
                'bisaDibatalkan' => in_array($booking->status_booking, ['menunggu', 'dikonfirmasi'], true),
                'konfirmasiUrl' => route('admin.booking.confirm', $booking),
                'batalkanUrl' => route('admin.booking.cancel', $booking),
            ])
            ->all();
    }

    /**
     * Ranking cabang berdasarkan pendapatan.
     *
     * @return array<int, array{nama: string, totalBooking: int, pendapatan: int, keterisian: int}>
     */
    private function perbandinganCabang(Tenant $tenant): array
    {
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
                    'nama' => $cabang->nama_cabang,
                    'totalBooking' => (clone $bookingQuery)->count(),
                    'pendapatan' => (int) (clone $bookingQuery)->whereIn('status_booking', ['dikonfirmasi', 'selesai'])->sum('total_bayar'),
                    'keterisian' => $totalSlot > 0 ? (int) round($terisi / $totalSlot * 100) : 0,
                ];
            })
            ->sortByDesc('pendapatan')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, nama: string, email: string, role: string, ubahRoleUrl: string}>
     */
    private function daftarStaf(Tenant $tenant): array
    {
        return Staf::where('tenant_id', $tenant->id)
            ->with('user')
            ->get()
            ->map(fn (Staf $staf) => [
                'id' => $staf->id,
                'nama' => $staf->user->name,
                'email' => $staf->user->email,
                'role' => $staf->role,
                'ubahRoleUrl' => route('admin.staf.role', $staf->id),
            ])
            ->all();
    }

    /**
     * Jumlah member dan persen diskon untuk tiap tier membership.
     *
     * @return array<int, array{tier: string, label: string, jumlah: int, persen: int}>
     */
    private function ringkasanMembership(Tenant $tenant): array
    {
        return collect(['bronze', 'silver', 'gold'])->map(fn (string $tier) => [
            'tier' => $tier,
            'label' => ucfirst($tier),
            'jumlah' => Membership::where('tenant_id', $tenant->id)->where('tier', $tier)->count(),
            'persen' => Membership::persenDiskonUntukTier($tier),
        ])->all();
    }

    /**
     * Gabungan booking yang reminder-nya masih terjadwal (belum ada baris
     * reminder_log) dengan reminder yang sudah tercatat di reminder_log,
     * dikelompokkan berdasar status: perlu_dikirim (menunggu dan waktu_kirim
     * sudah lewat, staf perlu kirim manual), terjadwal, terkirim/gagal.
     * Lihat references/notifikasi-whatsapp.md.
     *
     * @return Collection<int, array{id: ?int, nama: string, lapangan: string, waktu: string, status: string, waUrl: ?string, tandaiUrl: ?string}>
     */
    private function daftarReminder(Tenant $tenant): Collection
    {
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
                'lapangan' => $booking->slot->lapangan->nama,
                'waktu' => $booking->slot->tanggal->format('d/m/Y').', '.substr($booking->slot->jam_mulai, 0, 5),
                'status' => 'terjadwal',
                'waUrl' => null,
                'tandaiUrl' => null,
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
     * @return array{id: int, nama: string, lapangan: string, waktu: string, status: string, waUrl: ?string, tandaiUrl: ?string}
     */
    private function formatReminderLog(ReminderLog $log, string $status): array
    {
        $perluDikirim = $status === 'perlu_dikirim';

        return [
            'id' => $log->id,
            'nama' => $log->booking->customer->nama,
            'lapangan' => $log->booking->slot->lapangan->nama,
            'waktu' => $log->booking->slot->tanggal->format('d/m/Y').', '.substr($log->booking->slot->jam_mulai, 0, 5),
            'status' => $status,
            'waUrl' => $perluDikirim
                ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', (string) $log->booking->customer->no_telepon).'?text='.urlencode((string) $log->pesan)
                : null,
            'tandaiUrl' => $perluDikirim ? route('admin.reminder.terkirim', $log->id) : null,
        ];
    }

    private function baseQuery(Tenant $tenant, ?int $cabangId): Builder
    {
        return Booking::where('tenant_id', $tenant->id)
            ->when($cabangId, fn ($q) => $q->whereHas('slot.lapangan', fn ($q2) => $q2->where('cabang_id', $cabangId)));
    }

    private function queryBooking(Tenant $tenant, ?int $cabangId): Builder
    {
        return $this->baseQuery($tenant, $cabangId)->where('status_booking', '!=', 'dibatalkan');
    }
}
