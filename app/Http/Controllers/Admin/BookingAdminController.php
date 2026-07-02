<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\KirimNotifikasiWhatsApp;
use App\Models\Booking;
use App\Models\JadwalSlot;
use App\Models\ReminderLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingAdminController extends Controller
{
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
