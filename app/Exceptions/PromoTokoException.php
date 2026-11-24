<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Kode promo toko tidak bisa dipakai. Pesannya ditulis untuk pelanggan dan
 * aman ditampilkan apa adanya.
 */
class PromoTokoException extends RuntimeException {}
