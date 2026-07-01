<?php

namespace App\Http\Controllers;

use App\Jobs\KirimNotifikasiWhatsApp;
use App\Models\Booking;
use App\Models\JadwalSlot;
use App\Models\Pembayaran;
use App\Models\ReminderLog;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranController extends Controller
{
    /**
     * Endpoint webhook dari payment gateway (Midtrans/Xendit). Tidak lewat
     * middleware IdentifikasiTenant karena yang memanggil adalah server
     * gateway, bukan browser customer, jadi tenant_id diambil dari data
     * booking yang tersimpan, bukan dari domain.
     */
    public function webhook(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kode_transaksi_gateway' => ['required', 'string'],
            'kode_booking' => ['required', 'string'],
            'status' => ['required', 'in:sukses,gagal,pending'],
            'metode' => ['required', 'in:qris,ewallet,va,manual'],
            'jumlah' => ['required', 'integer'],
        ]);

        return DB::transaction(function () use ($data) {
            $booking = Booking::withoutGlobalScopes()
                ->where('kode_booking', $data['kode_booking'])
                ->lockForUpdate()
                ->firstOrFail();

            Pembayaran::create([
                'tenant_id' => $booking->tenant_id,
                'booking_id' => $booking->id,
                'metode' => $data['metode'],
                'jumlah' => $data['jumlah'],
                'status' => $data['status'],
                'kode_transaksi_gateway' => $data['kode_transaksi_gateway'],
                'waktu_bayar' => $data['status'] === 'sukses' ? now() : null,
            ]);

            if ($data['status'] === 'sukses') {
                $this->konfirmasiBooking($booking);
            } elseif ($data['status'] === 'gagal') {
                $this->batalkanBooking($booking);
            }

            return response()->json(['message' => 'Webhook diterima.']);
        });
    }

    private function konfirmasiBooking(Booking $booking): void
    {
        $booking->update(['status_booking' => 'dikonfirmasi']);

        $slot = JadwalSlot::withoutGlobalScopes()
            ->where('id', $booking->slot_id)
            ->lockForUpdate()
            ->first();

        $slot?->update(['status' => 'booked', 'hold_sampai' => null]);

        $tenant = Tenant::find($booking->tenant_id);

        if ($slot && $tenant?->punyaFitur('reminder_otomatis')) {
            $waktuMain = Carbon::parse($slot->tanggal->format('Y-m-d').' '.$slot->jam_mulai);

            ReminderLog::create([
                'tenant_id' => $booking->tenant_id,
                'booking_id' => $booking->id,
                'waktu_kirim' => $waktuMain->subHours(2),
                'pesan' => "Reminder: jadwal main kamu di {$tenant->nama_bisnis} kurang dari 2 jam lagi.",
            ]);
        }

        KirimNotifikasiWhatsApp::dispatch($booking);
    }

    private function batalkanBooking(Booking $booking): void
    {
        $booking->update(['status_booking' => 'dibatalkan']);

        $slot = JadwalSlot::withoutGlobalScopes()
            ->where('id', $booking->slot_id)
            ->lockForUpdate()
            ->first();

        $slot?->update(['status' => 'kosong', 'hold_sampai' => null]);
    }
}
