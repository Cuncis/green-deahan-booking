<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Services\HargaBookingService;
use App\Services\PaymentService;
use App\Services\SlotBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * API halaman booking customer. Aturan slot dan harga ada di
 * SlotBookingService dan HargaBookingService, controller ini hanya
 * memvalidasi input dan membentuk response.
 */
class BookingController extends Controller
{
    /**
     * Berapa hari ke depan customer boleh membuka jadwal. Membatasi slot
     * yang dibuat otomatis oleh SlotBookingService::pastikanSlotHarian().
     */
    public const HARI_KE_DEPAN = 30;

    public function __construct(
        private readonly SlotBookingService $slotBooking,
        private readonly HargaBookingService $hargaBooking,
    ) {}

    /**
     * Daftar slot satu lapangan pada satu tanggal. Slot hari itu dibuat
     * otomatis kalau belum ada.
     */
    public function lihatSlot(Request $request, int $lapanganId): JsonResponse
    {
        $tenant = app('tenant');
        $hariIni = SlotBookingService::hariIni();

        $data = $request->validate([
            'tanggal' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$hariIni->toDateString(),
                'before_or_equal:'.$hariIni->copy()->addDays(self::HARI_KE_DEPAN)->toDateString(),
            ],
        ]);

        $lapangan = Lapangan::where('tenant_id', $tenant->id)
            ->where('status_aktif', true)
            ->with('cabang')
            ->findOrFail($lapanganId);

        return response()->json(['data' => $this->slotBooking->daftarSlot($lapangan, $data['tanggal'])]);
    }

    /**
     * Tahan slot 10 menit. Customer yang ganti jam mengirim slot dan token
     * sebelumnya supaya slot lama langsung dilepas.
     */
    public function holdSlot(Request $request, int $slotId): JsonResponse
    {
        $data = $request->validate([
            'slot_sebelumnya' => ['nullable', 'integer'],
            'token_sebelumnya' => ['nullable', 'string', 'max:64'],
        ]);

        $hasil = $this->slotBooking->tahan(app('tenant'), $slotId, $data['slot_sebelumnya'] ?? null, $data['token_sebelumnya'] ?? null);

        if (! $hasil) {
            return response()->json([
                'message' => 'Slot ini baru saja diambil orang lain, silakan pilih jam lain.',
            ], 409);
        }

        $slot = $hasil['slot'];

        return response()->json([
            'message' => 'Slot berhasil di-hold.',
            'data' => [
                'slot_id' => $slot->id,
                'tanggal' => $slot->tanggal->toDateString(),
                'jam_mulai' => substr($slot->jam_mulai, 0, 5),
                'jam_selesai' => substr($slot->jam_selesai, 0, 5),
                'harga' => (int) $slot->harga,
                'hold_token' => $hasil['token'],
                'hold_sampai' => $slot->hold_sampai->toIso8601String(),
            ],
        ]);
    }

    public function lepasHold(Request $request, int $slotId): JsonResponse
    {
        $data = $request->validate([
            'hold_token' => ['required', 'string', 'max:64'],
        ]);

        $dilepas = $this->slotBooking->lepas(app('tenant'), $slotId, $data['hold_token']);

        return response()->json(['data' => ['dilepas' => $dilepas]]);
    }

    /**
     * Ringkasan harga untuk slot terpilih: diskon member, promo, dan DP.
     * Memakai perhitungan yang sama persis dengan buatBooking().
     */
    public function hitungHarga(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'slot_id' => ['required', 'integer'],
            'kode_promo' => ['nullable', 'string', 'max:50'],
            'tipe_pembayaran' => ['required', Rule::in($this->hargaBooking->tipePembayaranTersedia($tenant))],
            'no_telepon' => ['nullable', 'string', 'max:20'],
        ]);

        $slot = JadwalSlot::where('tenant_id', $tenant->id)->findOrFail($data['slot_id']);
        $harga = $this->hargaBooking->hitung($tenant, (int) $slot->harga, $data['kode_promo'] ?? null, $data['tipe_pembayaran'], $data['no_telepon'] ?? null);

        return response()->json(['data' => $this->ringkasanPublik($harga)]);
    }

    /**
     * Dipoll dari browser setelah booking dibuat, supaya status pembayaran
     * otomatis berubah begitu webhook Mayar mengonfirmasi booking (lihat
     * PembayaranController::webhook()).
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
                'total_bayar' => $booking->total_bayar,
            ],
        ]);
    }

    /**
     * Status membership customer berdasarkan nomor WhatsApp, untuk banner
     * harga member. Hanya berisi data kalau fitur sistem_membership aktif.
     */
    public function cekMembership(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'no_telepon' => ['required', 'string', 'max:20'],
            'lapangan_id' => ['required', 'integer'],
        ]);

        $membership = $this->hargaBooking->cariMembership($tenant, $data['no_telepon']);

        if (! $membership) {
            return response()->json(['data' => null]);
        }

        $lapangan = Lapangan::where('tenant_id', $tenant->id)->findOrFail($data['lapangan_id']);

        return response()->json([
            'data' => [
                'tier' => ucfirst($membership->tier),
                'persen_diskon' => $membership->persenDiskon(),
                'harga_member' => $membership->hargaMember((int) $lapangan->harga_per_jam),
            ],
        ]);
    }

    /**
     * Buat booking dari slot yang sedang ditahan customer ini (dibuktikan
     * dengan hold_token). Token dihapus setelah booking dibuat supaya hold
     * yang sama tidak bisa menghasilkan booking kedua.
     */
    public function buatBooking(Request $request): JsonResponse
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'slot_id' => ['required', 'integer'],
            'hold_token' => ['required', 'string', 'max:64'],
            'nama' => ['required', 'string', 'max:255'],
            'whatsapp' => [
                'required', 'string', 'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $jumlahAngka = strlen(HargaBookingService::normalisasiTelepon((string) $value));

                    if ($jumlahAngka < 9 || $jumlahAngka > 15) {
                        $fail('Nomor WhatsApp harus berisi 9 sampai 15 angka.');
                    }
                },
            ],
            'kode_promo' => ['nullable', 'string', 'max:50'],
            'tipe_pembayaran' => ['required', Rule::in($this->hargaBooking->tipePembayaranTersedia($tenant))],
            'reminder_aktif' => ['sometimes', 'boolean'],
        ], [
            'tipe_pembayaran.in' => 'Pilihan pembayaran ini tidak tersedia.',
        ]);

        $lapanganId = null;
        $pesanGagal = 'Slot ini sudah tidak bisa dibooking.';

        $booking = DB::transaction(function () use ($tenant, $data, &$lapanganId, &$pesanGagal) {
            $slot = JadwalSlot::where('tenant_id', $tenant->id)
                ->where('id', $data['slot_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($slot->status !== 'hold' || ! SlotBookingService::tokenCocok($slot, $data['hold_token'])) {
                return null;
            }

            if ($slot->hold_sampai === null || $slot->hold_sampai->isPast()) {
                $pesanGagal = 'Waktu tahan slot sudah habis, silakan pilih jam lagi.';

                return null;
            }

            $lapanganId = $slot->lapangan_id;
            $noTelepon = HargaBookingService::normalisasiTelepon($data['whatsapp']);

            $harga = $this->hargaBooking->hitung($tenant, (int) $slot->harga, $data['kode_promo'] ?? null, $data['tipe_pembayaran'], $noTelepon, kunciPromo: true);

            $customer = Customer::whereIn('no_telepon', array_unique([trim($data['whatsapp']), $noTelepon]))->first()
                ?? Customer::create(['no_telepon' => $noTelepon, 'nama' => $data['nama']]);

            $slot->update(['hold_token' => null]);

            return Booking::create([
                'tenant_id' => $tenant->id,
                'slot_id' => $slot->id,
                'customer_id' => $customer->id,
                'kode_promo_id' => $harga['kode_promo']?->id,
                'membership_id' => $harga['membership']?->id,
                'kode_booking' => Booking::generateKodeBooking(),
                'harga_normal' => $harga['harga_normal'],
                'diskon_jumlah' => $harga['diskon_jumlah'],
                'total_bayar' => $harga['total_bayar'],
                'tipe_pembayaran' => $data['tipe_pembayaran'],
                'status_booking' => 'menunggu',
                'reminder_aktif' => $tenant->punyaFitur('reminder_otomatis') && ($data['reminder_aktif'] ?? false),
            ]);
        });

        if (! $booking) {
            return response()->json(['message' => $pesanGagal], 409);
        }

        $responseData = [
            'message' => 'Booking berhasil dibuat.',
            'data' => $booking,
        ];

        // Panggilan ke Mayar sengaja di LUAR DB::transaction() di atas,
        // supaya lock row jadwal_slot tidak ketahan selama menunggu network
        // I/O ke gateway pembayaran.
        try {
            // Customer diarahkan kembali ke halaman booking lapangan ini
            // (dengan kode_booking di query string) setelah selesai di
            // halaman Mayar, supaya halaman booking bisa membuka lagi status
            // pembayaran dan lanjut polling.
            $finishRedirectUrl = route('booking.index', [
                'lapangan' => $lapanganId,
                'kode_booking' => $booking->kode_booking,
            ]);

            $responseData['pembayaran'] = app(PaymentService::class)
                ->createTransaction($booking, $finishRedirectUrl);
        } catch (\Throwable $e) {
            report($e);
            $responseData['pembayaran_error'] = 'Booking berhasil dibuat, tapi transaksi pembayaran online gagal dibuat. Hubungi admin untuk bantuan.';
        }

        return response()->json($responseData, 201);
    }

    /**
     * @param  array<string, mixed>  $harga  hasil HargaBookingService::hitung()
     * @return array{harga_normal: int, member: array{tier: string, persen: int}|null, diskon_member: int, status_promo: string, kode_promo: string|null, diskon_promo: int, total_setelah_diskon: int, tipe_pembayaran: string, total_bayar: int}
     */
    private function ringkasanPublik(array $harga): array
    {
        return [
            'harga_normal' => $harga['harga_normal'],
            'member' => $harga['membership'] ? ['tier' => ucfirst($harga['membership']->tier), 'persen' => $harga['membership']->persenDiskon()] : null,
            'diskon_member' => $harga['diskon_member'],
            'status_promo' => $harga['status_promo'],
            'kode_promo' => $harga['kode_promo']?->kode,
            'diskon_promo' => $harga['diskon_promo'],
            'total_setelah_diskon' => $harga['total_setelah_diskon'],
            'tipe_pembayaran' => $harga['tipe_pembayaran'],
            'total_bayar' => $harga['total_bayar'],
        ];
    }
}
