<?php

namespace App\Http\Controllers;

use App\Jobs\KirimNotifikasiWhatsApp;
use App\Models\Booking;
use App\Models\JadwalSlot;
use App\Models\Pembayaran;
use App\Models\ReminderLog;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Services\TenantActivationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PembayaranController extends Controller
{
    public function __construct(private readonly TenantActivationService $tenantActivationService) {}

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

        if ($data['tipe'] === 'langganan_tenant') {
            return $this->prosesWebhookLangganan($data);
        }

        return DB::transaction(function () use ($data) {
            $booking = Booking::withoutGlobalScopes()
                ->where('kode_booking', $data['kode'])
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
     * Aktivasi tenant otomatis begitu invoice langganan dibayar (lihat
     * PaymentService::createLanggananTransaction()), pengganti aktivasi
     * manual lewat `php artisan tenant:activate`. dibayar_at dicek SETELAH
     * lockForUpdate() (bukan sebelumnya) supaya webhook yang dikirim ulang
     * oleh Mayar tidak memperpanjang tanggal_berakhir dua kali, lihat
     * references/anti-double-booking.md untuk pola lock-lalu-cek yang sama.
     */
    private function prosesWebhookLangganan(array $data): JsonResponse
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::where('kode_pendaftaran', $data['kode'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($tenant->dibayar_at === null && $data['status'] === 'sukses') {
                $tenant = $this->tenantActivationService->aktifkan($tenant);
                $tenant->update(['dibayar_at' => now()]);

                if ($tenant->email_admin) {
                    $invitation = TenantInvitation::buatUntuk($tenant, $tenant->email_admin);
                    $invitation->kirimEmail();
                }
            }

            return response()->json(['message' => 'Webhook diterima.']);
        });
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
     * raw_response_gateway/tenant di baris terkait menyimpan payload
     * mentahnya untuk debugging, tinggal tambah pemetaannya di sini.
     *
     * extraData.tipe membedakan invoice booking (dibuat
     * PaymentService::buildParams()) dari invoice langganan tenant (dibuat
     * PaymentService::buildParamsLangganan()), default ke 'booking' untuk
     * invoice lama yang dibuat sebelum field ini ada. metode pembayaran
     * cuma relevan untuk booking (disimpan di Pembayaran::metode), jadi
     * TIDAK digatekan untuk tipe langganan_tenant supaya webhook
     * pembayaran tenant tidak ditolak gara-gara Mayar kirim paymentMethod
     * yang belum ada di pemetaan booking (mis. transfer bank biasa/kartu
     * kredit yang tidak relevan buat booking tapi valid buat langganan).
     *
     * @return array{tipe: string, kode: string, status: string, metode: ?string, jumlah: int, kode_transaksi_gateway: string, raw: array<string, mixed>}|null
     */
    private function parseMayarPayload(Request $request): ?array
    {
        $tipe = (string) $request->input('data.extraData.tipe', 'booking');

        if (! in_array($tipe, ['booking', 'langganan_tenant'], true)) {
            return null;
        }

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

        $kode = (string) $request->input('data.extraData.noCustomer');
        $jumlah = $request->input('data.amount');

        if ($status === null || $kode === '' || $jumlah === null) {
            return null;
        }

        if ($tipe === 'booking' && $metode === null) {
            return null;
        }

        return [
            'tipe' => $tipe,
            'kode' => $kode,
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
