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
     * Endpoint webhook dari Mayar. Tidak lewat middleware IdentifikasiTenant
     * karena yang memanggil adalah server Mayar, bukan browser customer,
     * jadi tenant_id diambil dari data booking yang tersimpan, bukan dari
     * domain. Lihat references/multi-tenant.md.
     */
    public function webhook(Request $request): JsonResponse
    {
        if (! $this->verifyMayarToken($request)) {
            return $this->tolakWebhook($request);
        }

        $data = $this->parseMayarPayload($request);

        if (! $data) {
            return $this->tolakWebhook($request);
        }

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
                'raw_response_gateway' => $data['raw'],
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
     * Terjemahkan notifikasi Mayar ke skema internal yang dipakai
     * konfirmasiBooking()/batalkanBooking() di atas. Return null kalau
     * status atau metode pembayaran belum pernah dipetakan (bukan berarti
     * invalid, tapi lebih aman ditolak daripada salah proses).
     *
     * Mayar TIDAK mendokumentasikan bentuk payload webhook secara lengkap
     * (lihat https://docs.mayar.id/integration/webhook), jadi status
     * dipetakan dari nilai yang dikonfirmasi dokumentasi invoice/detail
     * (unpaid/paid/expired, lihat https://docs.mayar.id/api-reference/invoice/detail)
     * ditambah variasi yang dipakai contoh notifikasi payment.received
     * (SUCCESS). Kalau di sandbox nanti ternyata field/nilai aslinya beda,
     * raw_response_gateway di baris pembayaran menyimpan payload mentahnya
     * untuk debugging, tinggal tambah pemetaannya di sini.
     *
     * @return array{kode_booking: string, status: string, metode: string, jumlah: int, kode_transaksi_gateway: string, raw: array<string, mixed>}|null
     */
    private function parseMayarPayload(Request $request): ?array
    {
        $status = match (strtoupper((string) $request->input('data.status'))) {
            'PAID', 'SUCCESS', 'SETTLED' => 'sukses',
            'EXPIRED', 'FAILED', 'CANCELLED', 'CANCELED' => 'gagal',
            'UNPAID', 'PENDING', 'CREATED' => 'pending',
            default => null,
        };

        $metode = match (strtoupper((string) $request->input('data.paymentMethod'))) {
            'QRIS' => 'qris',
            'VA', 'VIRTUAL_ACCOUNT', 'BANK_TRANSFER' => 'va',
            'EWALLET', 'GOPAY', 'OVO', 'DANA', 'SHOPEEPAY', 'LINKAJA' => 'ewallet',
            default => null,
        };

        $kodeBooking = (string) $request->input('data.extraData.noCustomer');
        $jumlah = $request->input('data.amount');

        if ($status === null || $metode === null || $kodeBooking === '' || $jumlah === null) {
            return null;
        }

        return [
            'kode_booking' => $kodeBooking,
            'status' => $status,
            'metode' => $metode,
            'jumlah' => (int) round((float) $jumlah),
            'kode_transaksi_gateway' => (string) $request->input('data.transactionId', $request->input('data.id')),
            'raw' => (array) $request->input('data'),
        ];
    }

    /**
     * Verifikasi webhook Mayar: shared secret dikirim sebagai query string
     * di URL webhook yang didaftarkan sendiri di dashboard Mayar
     * (.../api/webhook/pembayaran?token=MAYAR_WEBHOOK_TOKEN), bukan lewat
     * signature header seperti Midtrans/Xendit. Mayar tidak mendokumentasikan
     * mekanisme signing lain untuk webhook-nya.
     */
    private function verifyMayarToken(Request $request): bool
    {
        $expectedToken = config('services.mayar.webhook_token');
        $token = (string) $request->query('token');

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
