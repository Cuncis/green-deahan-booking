<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\JadwalSlot;
use App\Models\KodePromo;
use App\Models\Lapangan;
use App\Models\Membership;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Ambil daftar jadwal_slot milik satu lapangan pada tanggal tertentu.
     */
    public function lihatSlot(Request $request, int $lapanganId): JsonResponse
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'tanggal' => ['required', 'date'],
        ]);

        $slot = JadwalSlot::where('tenant_id', $tenant->id)
            ->where('lapangan_id', $lapanganId)
            ->whereDate('tanggal', $data['tanggal'])
            ->orderBy('jam_mulai')
            ->get();

        return response()->json(['data' => $slot]);
    }

    /**
     * Hold slot selama 10 menit supaya customer bisa menyelesaikan booking.
     */
    public function holdSlot(Request $request, int $slotId): JsonResponse
    {
        $tenant = app('tenant');

        return DB::transaction(function () use ($tenant, $slotId) {
            $slot = JadwalSlot::where('tenant_id', $tenant->id)
                ->where('id', $slotId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($slot->status !== 'kosong') {
                return response()->json([
                    'message' => 'Slot ini baru saja diambil orang lain.',
                ], 409);
            }

            $slot->update([
                'status' => 'hold',
                'hold_sampai' => now()->addMinutes(10),
            ]);

            return response()->json([
                'message' => 'Slot berhasil di-hold.',
                'data' => $slot,
            ]);
        });
    }

    /**
     * Dipoll dari browser setelah booking dibuat, supaya modal instruksi
     * pembayaran bisa otomatis update begitu webhook Midtrans/Xendit
     * mengonfirmasi status_booking (lihat PembayaranController::webhook()).
     * Tidak ada cara lain browser tahu webhook sudah diproses selain polling.
     */
    public function cekStatusBooking(Request $request, string $kodeBooking): JsonResponse
    {
        $tenant = app('tenant');

        $booking = Booking::where('tenant_id', $tenant->id)
            ->where('kode_booking', $kodeBooking)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'status_booking' => $booking->status_booking,
            ],
        ]);
    }

    /**
     * Cek status membership customer berdasarkan nomor WhatsApp, untuk banner
     * harga member di halaman booking. Hanya berguna kalau fitur sistem_membership aktif.
     */
    public function cekMembership(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'no_telepon' => ['required', 'string'],
            'lapangan_id' => ['required', 'integer'],
        ]);

        if (! $tenant->punyaFitur('sistem_membership')) {
            return response()->json(['data' => null]);
        }

        $customer = Customer::where('no_telepon', $data['no_telepon'])->first();

        $membership = $customer
            ? Membership::where('tenant_id', $tenant->id)->where('customer_id', $customer->id)->first()
            : null;

        if (! $membership) {
            return response()->json(['data' => null]);
        }

        $lapangan = Lapangan::where('tenant_id', $tenant->id)->findOrFail($data['lapangan_id']);

        return response()->json([
            'data' => [
                'tier' => ucfirst($membership->tier),
                'harga_member' => $membership->hargaMember($lapangan->harga_per_jam),
            ],
        ]);
    }

    /**
     * Buat booking dari slot yang sudah di-hold.
     */
    public function buatBooking(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'slot_id' => ['required', 'integer'],
            'nama' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'kode_promo' => ['nullable', 'string'],
            'tipe_pembayaran' => ['required', 'in:dp,lunas'],
            'metode_pembayaran' => ['required', 'in:qris,va,ewallet'],
            'reminder_aktif' => ['sometimes', 'boolean'],
        ]);

        $booking = DB::transaction(function () use ($tenant, $data) {
            $slot = JadwalSlot::where('tenant_id', $tenant->id)
                ->where('id', $data['slot_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($slot->status !== 'hold') {
                return null;
            }

            $customer = Customer::firstOrCreate(
                ['no_telepon' => $data['whatsapp']],
                ['nama' => $data['nama']],
            );

            $hargaNormal = $slot->harga;
            $diskonJumlah = 0;
            $kodePromo = null;

            if (! empty($data['kode_promo']) && $tenant->punyaFitur('kode_promo')) {
                $kodePromo = KodePromo::where('tenant_id', $tenant->id)
                    ->where('kode', $data['kode_promo'])
                    ->where('status_aktif', true)
                    ->whereDate('tanggal_mulai', '<=', now())
                    ->whereDate('tanggal_berakhir', '>=', now())
                    ->first();

                if ($kodePromo) {
                    $diskonJumlah = $kodePromo->tipe_diskon === 'persen'
                        ? (int) round($hargaNormal * $kodePromo->nilai / 100)
                        : $kodePromo->nilai;

                    $diskonJumlah = min($diskonJumlah, $hargaNormal);
                }
            }

            $totalSetelahDiskon = $hargaNormal - $diskonJumlah;

            $totalBayar = $data['tipe_pembayaran'] === 'dp'
                ? (int) round($totalSetelahDiskon * 0.5)
                : $totalSetelahDiskon;

            return Booking::create([
                'tenant_id' => $tenant->id,
                'slot_id' => $slot->id,
                'customer_id' => $customer->id,
                'kode_promo_id' => $kodePromo?->id,
                'kode_booking' => Booking::generateKodeBooking(),
                'harga_normal' => $hargaNormal,
                'diskon_jumlah' => $diskonJumlah,
                'total_bayar' => $totalBayar,
                'tipe_pembayaran' => $data['tipe_pembayaran'],
                'status_booking' => 'menunggu',
                'reminder_aktif' => $tenant->punyaFitur('reminder_otomatis') && ($data['reminder_aktif'] ?? false),
            ]);
        });

        if (! $booking) {
            return response()->json([
                'message' => 'Slot ini sudah tidak bisa dibooking.',
            ], 409);
        }

        $responseData = [
            'message' => 'Booking berhasil dibuat.',
            'data' => $booking,
        ];

        // Panggilan ke Midtrans sengaja di LUAR DB::transaction() di atas,
        // supaya lock row jadwal_slot tidak ketahan selama menunggu network
        // I/O ke gateway pembayaran.
        try {
            $responseData['pembayaran'] = app(PaymentService::class)
                ->createTransaction($booking, $data['metode_pembayaran']);
        } catch (\Throwable $e) {
            report($e);
            $responseData['pembayaran_error'] = 'Booking berhasil dibuat, tapi transaksi pembayaran online gagal dibuat. Hubungi admin untuk bantuan.';
        }

        return response()->json($responseData, 201);
    }
}
