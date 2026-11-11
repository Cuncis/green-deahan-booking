<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\KodePromo;
use App\Models\Membership;
use App\Models\Tenant;

/**
 * Satu-satunya tempat menghitung harga booking customer: harga member,
 * diskon kode promo, lalu DP. Dipakai untuk ringkasan di halaman booking
 * DAN saat booking dibuat, supaya total yang dilihat customer selalu sama
 * dengan total yang ditagih.
 *
 * Urutan: harga slot, dikurangi diskon member (fitur sistem_membership),
 * lalu diskon promo dihitung dari harga setelah diskon member, lalu DP 50%
 * dari total itu kalau customer pilih DP.
 */
class HargaBookingService
{
    public const PERSEN_DP = 50;

    /**
     * @return array<int, string>
     */
    public function tipePembayaranTersedia(Tenant $tenant): array
    {
        return $tenant->punyaFitur('dp_pembayaran') ? ['dp', 'lunas'] : ['lunas'];
    }

    /**
     * Nomor WhatsApp disimpan sebagai angka saja, supaya "0812-3456" dan
     * "08123456" dianggap customer yang sama.
     */
    public static function normalisasiTelepon(string $noTelepon): string
    {
        return preg_replace('/\D/', '', $noTelepon) ?? '';
    }

    public function cariMembership(Tenant $tenant, ?string $noTelepon): ?Membership
    {
        if (! $tenant->punyaFitur('sistem_membership') || $noTelepon === null || trim($noTelepon) === '') {
            return null;
        }

        $customer = Customer::whereIn('no_telepon', array_unique([trim($noTelepon), self::normalisasiTelepon($noTelepon)]))->first();

        return $customer
            ? Membership::where('tenant_id', $tenant->id)->where('customer_id', $customer->id)->first()
            : null;
    }

    /**
     * @param  bool  $kunciPromo  true saat booking dibuat: baris kode promo
     *                            dikunci supaya kuota tidak terlampaui oleh
     *                            dua booking yang masuk bersamaan.
     * @return array{
     *     harga_normal: int,
     *     membership: Membership|null,
     *     diskon_member: int,
     *     kode_promo: KodePromo|null,
     *     status_promo: 'kosong'|'valid'|'tidak_valid'|'habis',
     *     diskon_promo: int,
     *     diskon_jumlah: int,
     *     total_setelah_diskon: int,
     *     tipe_pembayaran: string,
     *     total_bayar: int
     * }
     */
    public function hitung(Tenant $tenant, int $hargaNormal, ?string $kodePromo, string $tipePembayaran, ?string $noTelepon, bool $kunciPromo = false): array
    {
        $membership = $this->cariMembership($tenant, $noTelepon);
        $diskonMember = $membership ? $hargaNormal - $membership->hargaMember($hargaNormal) : 0;
        $hargaSetelahMember = $hargaNormal - $diskonMember;

        [$promo, $statusPromo] = $this->cariPromo($tenant, $kodePromo, $kunciPromo);
        $diskonPromo = 0;

        if ($promo) {
            $diskonPromo = $promo->tipe_diskon === 'persen'
                ? (int) round($hargaSetelahMember * $promo->nilai / 100)
                : (int) $promo->nilai;
            $diskonPromo = min($diskonPromo, $hargaSetelahMember);
        }

        $totalSetelahDiskon = $hargaSetelahMember - $diskonPromo;
        $totalBayar = $tipePembayaran === 'dp'
            ? (int) round($totalSetelahDiskon * self::PERSEN_DP / 100)
            : $totalSetelahDiskon;

        return [
            'harga_normal' => $hargaNormal,
            'membership' => $membership,
            'diskon_member' => $diskonMember,
            'kode_promo' => $promo,
            'status_promo' => $statusPromo,
            'diskon_promo' => $diskonPromo,
            'diskon_jumlah' => $diskonMember + $diskonPromo,
            'total_setelah_diskon' => $totalSetelahDiskon,
            'tipe_pembayaran' => $tipePembayaran,
            'total_bayar' => $totalBayar,
        ];
    }

    /**
     * @return array{0: KodePromo|null, 1: 'kosong'|'valid'|'tidak_valid'|'habis'}
     */
    private function cariPromo(Tenant $tenant, ?string $kode, bool $kunci): array
    {
        $kode = strtoupper(trim((string) $kode));

        if ($kode === '') {
            return [null, 'kosong'];
        }

        if (! $tenant->punyaFitur('kode_promo')) {
            return [null, 'tidak_valid'];
        }

        $promo = KodePromo::where('tenant_id', $tenant->id)
            ->where('kode', $kode)
            ->where('status_aktif', true)
            ->whereDate('tanggal_mulai', '<=', now())
            ->whereDate('tanggal_berakhir', '>=', now())
            ->when($kunci, fn ($q) => $q->lockForUpdate())
            ->first();

        if (! $promo) {
            return [null, 'tidak_valid'];
        }

        if ($promo->kuota !== null && $this->jumlahPemakaian($promo) >= $promo->kuota) {
            return [null, 'habis'];
        }

        return [$promo, 'valid'];
    }

    /**
     * Booking yang dibatalkan tidak dihitung, jadi kuotanya kembali.
     */
    private function jumlahPemakaian(KodePromo $promo): int
    {
        return Booking::where('tenant_id', $promo->tenant_id)
            ->where('kode_promo_id', $promo->id)
            ->where('status_booking', '!=', 'dibatalkan')
            ->count();
    }
}
