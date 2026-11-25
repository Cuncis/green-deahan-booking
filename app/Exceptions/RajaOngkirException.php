<?php

namespace App\Exceptions;

/**
 * Gagal bicara dengan API RajaOngkir. `kuotaHabis` true berarti batas harian habis
 * (dihitung sendiri atau ditolak RajaOngkir), bukan gangguan biasa.
 */
class RajaOngkirException extends OngkirGagalException
{
    public function __construct(string $message, public readonly bool $kuotaHabis = false)
    {
        parent::__construct($message);
    }
}
