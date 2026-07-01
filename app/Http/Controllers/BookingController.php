<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\JadwalSlot;
use App\Models\KodePromo;
use App\Models\Lapangan;
use App\Models\Membership;
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
            'tipe_pembayaran' => ['required', 'in:manual,dp,lunas'],
            'reminder_aktif' => ['sometimes', 'boolean'],
        ]);

        return DB::transaction(function () use ($tenant, $data) {
            $slot = JadwalSlot::where('tenant_id', $tenant->id)
                ->where('id', $data['slot_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($slot->status !== 'hold') {
                return response()->json([
                    'message' => 'Slot ini sudah tidak bisa dibooking.',
                ], 409);
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

            $booking = Booking::create([
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

            return response()->json([
                'message' => 'Booking berhasil dibuat.',
                'data' => $booking,
            ], 201);
        });
    }
}
