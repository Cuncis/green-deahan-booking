<?php

namespace App\Services;

use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Slot di halaman booking customer: membuat slot harian otomatis, menahan
 * (hold) slot 10 menit, dan melepasnya lagi. Setiap perubahan status
 * memakai lockForUpdate() di dalam DB::transaction(), lihat
 * references/anti-double-booking.md.
 *
 * Hold dilindungi token acak yang hanya diketahui browser customer yang
 * menahannya. Booking dan pelepasan hold wajib menyertakan token itu.
 * Begitu booking dibuat, token dihapus: slot tetap "hold" sampai
 * pembayaran dikonfirmasi, tapi tidak bisa dipakai booking kedua.
 */
class SlotBookingService
{
    public const MENIT_HOLD = 10;

    /**
     * Jam slot adalah jam lokal lapangan. Aplikasi berjalan di UTC, jadi
     * "sudah lewat" dan "hari ini" dihitung di zona ini (sama dengan
     * KunjunganHalaman::ZONA_WAKTU).
     */
    public const ZONA_WAKTU = 'Asia/Jakarta';

    public static function hariIni(): Carbon
    {
        return now(self::ZONA_WAKTU)->startOfDay();
    }

    /**
     * Supaya customer tidak pernah melihat "belum ada jadwal", slot jam
     * operasional (jam buka/tutup cabang, interval 1 jam) dibuat otomatis
     * saat tanggal itu pertama kali dibuka. Admin tetap bisa mengubah harga
     * atau menonaktifkan slot lewat halaman Jadwal.
     */
    public function pastikanSlotHarian(Lapangan $lapangan, string $tanggal): void
    {
        $sudahAda = JadwalSlot::where('tenant_id', $lapangan->tenant_id)
            ->where('lapangan_id', $lapangan->id)
            ->whereDate('tanggal', $tanggal)
            ->exists();

        if ($sudahAda || ! $lapangan->cabang) {
            return;
        }

        $jamMulai = Carbon::parse($lapangan->cabang->jam_buka);
        $jamTutup = Carbon::parse($lapangan->cabang->jam_tutup);

        while ($jamMulai->copy()->addHour()->lessThanOrEqualTo($jamTutup)) {
            $jamSelesai = $jamMulai->copy()->addHour();

            try {
                JadwalSlot::create([
                    'tenant_id' => $lapangan->tenant_id,
                    'lapangan_id' => $lapangan->id,
                    'tanggal' => $tanggal,
                    'jam_mulai' => $jamMulai->format('H:i'),
                    'jam_selesai' => $jamSelesai->format('H:i'),
                    'harga' => $lapangan->harga_per_jam,
                    'status' => 'kosong',
                ]);
            } catch (QueryException $e) {
                // Dua request membuat slot yang sama bersamaan: constraint unik menolak salah satunya.
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }

            $jamMulai = $jamSelesai;
        }
    }

    /**
     * @return array<int, array{id: int, jam_mulai: string, jam_selesai: string, harga: int, status: string, tersedia: bool}>
     */
    public function daftarSlot(Lapangan $lapangan, string $tanggal): array
    {
        $this->pastikanSlotHarian($lapangan, $tanggal);

        return JadwalSlot::where('tenant_id', $lapangan->tenant_id)
            ->where('lapangan_id', $lapangan->id)
            ->whereDate('tanggal', $tanggal)
            ->orderBy('jam_mulai')
            ->get()
            ->map(fn (JadwalSlot $slot) => [
                'id' => $slot->id,
                'jam_mulai' => substr($slot->jam_mulai, 0, 5),
                'jam_selesai' => substr($slot->jam_selesai, 0, 5),
                'harga' => (int) $slot->harga,
                'status' => $this->statusPublik($slot),
                'tersedia' => $this->bisaDitahan($slot),
            ])
            ->all();
    }

    /**
     * Tahan slot untuk satu customer. Hold sebelumnya milik customer yang
     * sama (dibuktikan dengan tokennya) dilepas dulu, supaya ganti jam tidak
     * menyandera slot lama sampai 10 menit habis.
     *
     * @return array{slot: JadwalSlot, token: string}|null null kalau slot sudah diambil orang lain
     */
    public function tahan(Tenant $tenant, int $slotId, ?int $slotSebelumnya = null, ?string $tokenSebelumnya = null): ?array
    {
        return DB::transaction(function () use ($tenant, $slotId, $slotSebelumnya, $tokenSebelumnya) {
            if ($slotSebelumnya && $slotSebelumnya !== $slotId && $tokenSebelumnya) {
                $this->lepasDalamTransaksi($tenant, $slotSebelumnya, $tokenSebelumnya);
            }

            $slot = JadwalSlot::where('tenant_id', $tenant->id)
                ->where('id', $slotId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->bisaDitahan($slot)) {
                return null;
            }

            $token = Str::random(40);
            $slot->update([
                'status' => 'hold',
                'hold_sampai' => now()->addMinutes(self::MENIT_HOLD),
                'hold_token' => $token,
            ]);

            return ['slot' => $slot, 'token' => $token];
        });
    }

    /**
     * Lepas hold kalau token cocok. Slot yang booking-nya sudah dibuat
     * (token sudah dihapus) tidak bisa dilepas dari sini.
     */
    public function lepas(Tenant $tenant, int $slotId, string $token): bool
    {
        return DB::transaction(fn () => $this->lepasDalamTransaksi($tenant, $slotId, $token));
    }

    public static function tokenCocok(JadwalSlot $slot, ?string $token): bool
    {
        return $slot->hold_token !== null && $token !== null && hash_equals($slot->hold_token, $token);
    }

    private function lepasDalamTransaksi(Tenant $tenant, int $slotId, string $token): bool
    {
        $slot = JadwalSlot::where('tenant_id', $tenant->id)
            ->where('id', $slotId)
            ->lockForUpdate()
            ->first();

        if (! $slot || $slot->status !== 'hold' || ! self::tokenCocok($slot, $token)) {
            return false;
        }

        $slot->update(['status' => 'kosong', 'hold_sampai' => null, 'hold_token' => null]);

        return true;
    }

    /**
     * Kosong dan belum lewat jam mulainya. Hold yang sudah kadaluarsa dan
     * belum jadi booking (token masih ada) dianggap kosong, tanpa menunggu
     * command booking:lepas-slot-kadaluarsa yang jalan tiap menit.
     */
    private function bisaDitahan(JadwalSlot $slot): bool
    {
        if ($this->sudahLewat($slot)) {
            return false;
        }

        if ($slot->status === 'kosong') {
            return true;
        }

        return $slot->status === 'hold'
            && $slot->hold_token !== null
            && $slot->hold_sampai !== null
            && $slot->hold_sampai->isPast();
    }

    /**
     * Status untuk customer: tidak membedakan "ditahan" dan "dibooking",
     * cukup tersedia atau tidak.
     */
    private function statusPublik(JadwalSlot $slot): string
    {
        return match (true) {
            $this->bisaDitahan($slot) => 'tersedia',
            $slot->status === 'nonaktif' => 'tutup',
            in_array($slot->status, ['kosong', 'hold'], true) && $this->sudahLewat($slot) => 'lewat',
            default => 'terisi',
        };
    }

    private function sudahLewat(JadwalSlot $slot): bool
    {
        return Carbon::parse($slot->tanggal->toDateString().' '.$slot->jam_mulai, self::ZONA_WAKTU)->isPast();
    }
}
