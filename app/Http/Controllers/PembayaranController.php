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
use Illuminate\Support\Facades\Log;

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
        // Deteksi gateway pengirim lalu verifikasi signature-nya SEBELUM
        // memproses apapun. Xendit selalu kirim header x-callback-token;
        // Midtrans selalu kirim signature_key di body, jadi keduanya tidak
        // akan tumpang tindih satu sama lain.
        if ($request->hasHeader('x-callback-token')) {
            if (! $this->verifyXenditSignature($request)) {
                return $this->tolakWebhook($request);
            }
        } elseif ($request->filled('signature_key')) {
            if (! $this->verifyMidtransSignature($request)) {
                return $this->tolakWebhook($request);
            }
        } else {
            return $this->tolakWebhook($request);
        }

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

    /**
     * Verifikasi signature Midtrans: SHA512(order_id + status_code +
     * gross_amount + server_key) harus sama dengan signature_key yang
     * dikirim. Dokumentasi resmi Midtrans: https://docs.midtrans.com.
     */
    private function verifyMidtransSignature(Request $request): bool
    {
        $serverKey = config('services.midtrans.server_key');

        if (empty($serverKey)) {
            return false;
        }

        $orderId = (string) $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $signatureKey = (string) $request->input('signature_key');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signatureKey);
    }

    /**
     * Verifikasi signature Xendit: header x-callback-token harus sama
     * dengan token verifikasi yang diset di dashboard Xendit.
     */
    private function verifyXenditSignature(Request $request): bool
    {
        $expectedToken = config('services.xendit.callback_token');
        $token = (string) $request->header('x-callback-token');

        if (empty($expectedToken) || $token === '') {
            return false;
        }

        return hash_equals((string) $expectedToken, $token);
    }

    private function tolakWebhook(Request $request): JsonResponse
    {
        Log::warning('Webhook signature invalid', ['ip' => $request->ip()]);

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
