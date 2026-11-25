<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Tarif ongkir tidak bisa diambil dari penyedia manapun (RajaOngkir, Biteship) atau
 * data asal pengiriman belum lengkap. Pesannya aman ditampilkan atau dicatat: tidak
 * pernah berisi API key atau header request.
 */
class OngkirGagalException extends RuntimeException {}
