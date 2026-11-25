<?php

namespace App\Services;

use App\Exceptions\OngkirGagalException;
use App\Exceptions\RajaOngkirException;
use Illuminate\Support\Facades\Log;

/**
 * Memilih penyedia tarif ongkir toko: RajaOngkir lebih dulu (gratis sampai batas
 * harian), lalu Biteship (berbayar per permintaan) kalau RajaOngkir belum aktif,
 * batas hariannya habis, atau sedang gagal. Hasil keduanya berbentuk sama.
 */
class PenyediaOngkirToko
{
    public function __construct(
        private readonly RajaOngkirService $rajaOngkir,
        private readonly BiteshipService $biteship,
    ) {}

    /**
     * Ada penyedia yang bisa dipakai. Kalau false, checkout memakai ongkir flat.
     */
    public function aktif(): bool
    {
        return $this->rajaOngkir->aktif() || $this->biteship->aktif();
    }

    /**
     * @param  array<int, array{name: string, value: int, weight: int, quantity: int}>  $items
     * @return array<int, array{courier_code: string, courier_name: string, service_code: string, service_name: string, duration: string, duration_min: int|null, duration_max: int|null, duration_unit: string, price: int}>
     *
     * @throws OngkirGagalException
     */
    public function tarif(string $kodePosAsal, string $kodePosTujuan, array $items): array
    {
        if ($this->rajaOngkir->aktif()) {
            try {
                return $this->rajaOngkir->tarif($kodePosAsal, $kodePosTujuan, $items);
            } catch (RajaOngkirException $galat) {
                if (! $this->biteship->aktif()) {
                    throw $galat;
                }

                // Batas harian habis itu wajar (sehari sekali); gangguan lain perlu dilihat di log.
                Log::log($galat->kuotaHabis ? 'notice' : 'warning', 'Ongkir: beralih ke Biteship. '.$galat->getMessage());
            }
        }

        return $this->biteship->tarif($kodePosAsal, $kodePosTujuan, $items);
    }
}
