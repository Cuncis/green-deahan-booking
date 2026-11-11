<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman Jadwal (React, resources/js/admin/pages/admin/Jadwal.tsx),
 * sebelumnya Livewire KalenderAdmin. Minggu, tanggal, dan lapangan yang
 * dipilih ada di query string (?minggu=&tanggal=&lapangan=).
 */
class JadwalAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('tenant');
        $daftarLapangan = Lapangan::where('tenant_id', $tenant->id)->orderBy('nama')->get(['id', 'nama']);

        $lapanganId = $request->filled('lapangan') && $daftarLapangan->contains('id', (int) $request->query('lapangan'))
            ? (int) $request->query('lapangan')
            : null;
        $mingguAwal = $this->tanggalAtau($request->query('minggu'), now())->startOfWeek();
        $tanggalDipilih = $this->tanggalAtau($request->query('tanggal'), $request->filled('minggu') ? $mingguAwal : now());

        return Inertia::render('admin/Jadwal', [
            'daftarLapangan' => $daftarLapangan->map(fn (Lapangan $lapangan) => ['id' => $lapangan->id, 'nama' => $lapangan->nama])->all(),
            'lapanganId' => $lapanganId,
            'mingguAwal' => $mingguAwal->toDateString(),
            'labelMinggu' => $mingguAwal->locale('id')->translatedFormat('j M').' sampai '.$mingguAwal->copy()->addDays(6)->locale('id')->translatedFormat('j M Y'),
            'tanggalDipilih' => $tanggalDipilih->toDateString(),
            'labelTanggal' => $tanggalDipilih->locale('id')->translatedFormat('l, j F Y'),
            'ringkasanMingguan' => $this->ringkasanMingguan($tenant->id, $lapanganId, $mingguAwal),
            'slot' => $this->slotQuery($tenant->id, $lapanganId)
                ->whereDate('tanggal', $tanggalDipilih->toDateString())
                ->with('lapangan:id,nama')
                ->orderBy('jam_mulai')
                ->get()
                ->map(fn (JadwalSlot $slot) => [
                    'id' => $slot->id,
                    'jam' => substr($slot->jam_mulai, 0, 5).' - '.substr($slot->jam_selesai, 0, 5),
                    'lapangan' => $slot->lapangan->nama,
                    'harga' => (int) $slot->harga,
                    'status' => $slot->status,
                    'bisaDiubahStatus' => in_array($slot->status, ['kosong', 'nonaktif'], true),
                ])
                ->all(),
        ]);
    }

    /**
     * Generate slot otomatis untuk satu lapangan dalam rentang tanggal,
     * memakai jam buka/tutup cabang lapangan itu sebagai batas dan interval
     * pilihan admin sebagai durasi tiap slot. Slot yang tanggal+jam-nya
     * sudah ada dilewati; constraint unik di database
     * (references/anti-double-booking.md) jadi pengaman terakhir.
     */
    public function generate(Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'lapangan_id' => ['required', Rule::exists('lapangan', 'id')->where('tenant_id', $tenant->id)],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'interval_jam' => ['required', 'numeric', 'min:0.5', 'max:6'],
            'harga' => ['required', 'integer', 'min:0'],
        ]);

        $lapangan = Lapangan::where('tenant_id', $tenant->id)->with('cabang')->findOrFail($data['lapangan_id']);

        $intervalMenit = (int) round($data['interval_jam'] * 60);
        $tanggalSelesai = Carbon::parse($data['tanggal_selesai']);
        $dibuat = 0;
        $dilewati = 0;

        for ($tanggal = Carbon::parse($data['tanggal_mulai']); $tanggal->lessThanOrEqualTo($tanggalSelesai); $tanggal->addDay()) {
            $jamMulai = Carbon::parse($lapangan->cabang->jam_buka);
            $batasTutup = Carbon::parse($lapangan->cabang->jam_tutup);

            while ($jamMulai->copy()->addMinutes($intervalMenit)->lessThanOrEqualTo($batasTutup)) {
                $jamSelesaiSlot = $jamMulai->copy()->addMinutes($intervalMenit);

                try {
                    JadwalSlot::create([
                        'tenant_id' => $tenant->id,
                        'lapangan_id' => $lapangan->id,
                        'tanggal' => $tanggal->toDateString(),
                        'jam_mulai' => $jamMulai->format('H:i'),
                        'jam_selesai' => $jamSelesaiSlot->format('H:i'),
                        'harga' => $data['harga'],
                        'status' => 'kosong',
                    ]);
                    $dibuat++;
                } catch (QueryException $e) {
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }

                    $dilewati++;
                }

                $jamMulai = $jamSelesaiSlot;
            }
        }

        return back()->with('success', "{$dibuat} slot berhasil dibuat".($dilewati > 0 ? ", {$dilewati} slot dilewati karena sudah ada." : '.'));
    }

    public function ubahHarga(Request $request, int $slot): RedirectResponse
    {
        $data = $request->validate([
            'harga' => ['required', 'integer', 'min:0'],
        ]);

        $diubah = JadwalSlot::where('tenant_id', app('tenant')->id)
            ->where('id', $slot)
            ->update(['harga' => $data['harga']]);

        abort_if($diubah === 0, 404);

        return back()->with('success', 'Harga slot diperbarui.');
    }

    /**
     * Nonaktifkan atau aktifkan kembali slot kosong. Slot yang sedang
     * di-hold atau sudah booked tidak boleh diubah lewat sini, supaya alur
     * status kosong -> hold -> booked tetap terjaga
     * (references/anti-double-booking.md).
     */
    public function toggleStatus(int $slot): RedirectResponse
    {
        $tenant = app('tenant');

        $statusBaru = DB::transaction(function () use ($tenant, $slot) {
            $jadwal = JadwalSlot::where('tenant_id', $tenant->id)
                ->where('id', $slot)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($jadwal->status, ['kosong', 'nonaktif'], true)) {
                return null;
            }

            $jadwal->update(['status' => $jadwal->status === 'kosong' ? 'nonaktif' : 'kosong']);

            return $jadwal->status;
        });

        if ($statusBaru === null) {
            return back()->with('error', 'Slot yang sudah di-hold atau dibooking tidak bisa dinonaktifkan.');
        }

        return back()->with('success', $statusBaru === 'nonaktif' ? 'Slot dinonaktifkan.' : 'Slot diaktifkan kembali.');
    }

    private function slotQuery(int $tenantId, ?int $lapanganId): Builder
    {
        return JadwalSlot::where('tenant_id', $tenantId)
            ->when($lapanganId, fn ($q) => $q->where('lapangan_id', $lapanganId));
    }

    /**
     * @return array<int, array{tanggal: string, hari: string, tanggalPendek: string, total: int, terisi: int}>
     */
    private function ringkasanMingguan(int $tenantId, ?int $lapanganId, Carbon $mingguAwal): array
    {
        return collect(range(0, 6))
            ->map(fn (int $i) => $mingguAwal->copy()->addDays($i))
            ->map(function (Carbon $tanggal) use ($tenantId, $lapanganId) {
                $query = $this->slotQuery($tenantId, $lapanganId)->whereDate('tanggal', $tanggal->toDateString());

                return [
                    'tanggal' => $tanggal->toDateString(),
                    'hari' => $tanggal->locale('id')->translatedFormat('D'),
                    'tanggalPendek' => $tanggal->format('d/m'),
                    'total' => (clone $query)->count(),
                    'terisi' => (clone $query)->where('status', 'booked')->count(),
                ];
            })
            ->all();
    }

    private function tanggalAtau(mixed $nilai, Carbon $cadangan): Carbon
    {
        if (is_string($nilai) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            try {
                return Carbon::parse($nilai);
            } catch (\Throwable) {
                // tanggal tidak valid, pakai cadangan
            }
        }

        return $cadangan->copy();
    }
}
