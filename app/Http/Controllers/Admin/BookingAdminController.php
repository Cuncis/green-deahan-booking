<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\KirimNotifikasiWhatsApp;
use App\Models\Booking;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\ReminderLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingAdminController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        $bookings = $this->filteredQuery($tenant->id, $request)
            ->with(['slot.lapangan', 'customer'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.bookings.index', [
            'tenant' => $tenant,
            'bookings' => $bookings,
            'daftarLapangan' => Lapangan::where('tenant_id', $tenant->id)->orderBy('nama')->get(),
            'filterStatus' => $request->query('status'),
            'filterTanggal' => $request->query('tanggal'),
            'filterLapangan' => $request->query('lapangan'),
            'cari' => $request->query('cari'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $tenant = app('tenant');

        $bookings = $this->filteredQuery($tenant->id, $request)
            ->with(['slot.lapangan', 'customer'])
            ->latest()
            ->get();

        $namaFile = 'booking-'.$tenant->domain.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Kode Booking', 'Customer', 'No Telepon', 'Lapangan', 'Tanggal', 'Jam Mulai', 'Status', 'Total Bayar']);

            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->kode_booking,
                    $booking->customer->nama,
                    $booking->customer->no_telepon,
                    $booking->slot->lapangan->nama,
                    $booking->slot->tanggal->format('Y-m-d'),
                    substr($booking->slot->jam_mulai, 0, 5),
                    $booking->status_booking,
                    $booking->total_bayar,
                ]);
            }

            fclose($handle);
        }, $namaFile, ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(int $tenantId, Request $request): Builder
    {
        $cari = $request->query('cari');

        return Booking::where('tenant_id', $tenantId)
            ->when($request->filled('status'), fn ($q) => $q->where('status_booking', $request->query('status')))
            ->when($request->filled('tanggal'), fn ($q) => $q->whereHas(
                'slot',
                fn ($q2) => $q2->whereDate('tanggal', $request->query('tanggal')),
            ))
            ->when($request->filled('lapangan'), fn ($q) => $q->whereHas(
                'slot',
                fn ($q2) => $q2->where('lapangan_id', $request->query('lapangan')),
            ))
            ->when($cari, fn ($q) => $q->where(
                fn ($q2) => $q2->where('kode_booking', 'like', "%{$cari}%")
                    ->orWhereHas('customer', fn ($q3) => $q3->where('nama', 'like', "%{$cari}%"))
            ));
    }

    /**
     * Route model binding untuk {booking} berjalan sebelum IdentifikasiTenant
     * mengikat app('tenant'), jadi global scope BelongsToTenant belum aktif
     * saat itu. Karena itu kepemilikan tenant wajib dicek manual di sini,
     * bukan mengandalkan scope.
     */
    public function confirm(Booking $booking): RedirectResponse
    {
        $tenant = app('tenant');

        abort_unless($booking->tenant_id === $tenant->id, 404);

        if ($booking->status_booking !== 'menunggu') {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Booking ini sudah tidak dalam status menunggu konfirmasi.');
        }

        DB::transaction(function () use ($booking, $tenant) {
            $booking->update(['status_booking' => 'dikonfirmasi']);

            $slot = JadwalSlot::where('tenant_id', $tenant->id)
                ->where('id', $booking->slot_id)
                ->lockForUpdate()
                ->first();

            $slot?->update(['status' => 'booked', 'hold_sampai' => null]);

            if ($slot && $tenant->punyaFitur('reminder_otomatis')) {
                $waktuMain = Carbon::parse($slot->tanggal->format('Y-m-d').' '.$slot->jam_mulai);

                ReminderLog::create([
                    'tenant_id' => $tenant->id,
                    'booking_id' => $booking->id,
                    'waktu_kirim' => $waktuMain->subHours(2),
                    'pesan' => "Reminder: jadwal main kamu di {$tenant->nama_bisnis} kurang dari 2 jam lagi.",
                ]);
            }
        });

        KirimNotifikasiWhatsApp::dispatch($booking->fresh(), 'dikonfirmasi');

        return redirect()->route('admin.dashboard')
            ->with('success', "Booking {$booking->kode_booking} berhasil dikonfirmasi.");
    }

    public function cancel(Booking $booking, Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        abort_unless($booking->tenant_id === $tenant->id, 404);

        if (! in_array($booking->status_booking, ['menunggu', 'dikonfirmasi'], true)) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Booking ini sudah tidak bisa dibatalkan.');
        }

        $data = $request->validate([
            'alasan' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($booking, $tenant, $data) {
            $booking->update([
                'status_booking' => 'dibatalkan',
                'alasan_pembatalan' => $data['alasan'] ?? null,
            ]);

            JadwalSlot::where('tenant_id', $tenant->id)
                ->where('id', $booking->slot_id)
                ->lockForUpdate()
                ->first()
                ?->update(['status' => 'kosong', 'hold_sampai' => null]);
        });

        KirimNotifikasiWhatsApp::dispatch($booking->fresh(), 'dibatalkan');

        return redirect()->route('admin.dashboard')
            ->with('success', "Booking {$booking->kode_booking} berhasil dibatalkan.");
    }
}
