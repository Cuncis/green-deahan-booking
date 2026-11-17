<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Gagal bicara dengan API Biteship. Pesannya aman ditampilkan atau dicatat:
 * tidak pernah berisi API key atau header request.
 */
class BiteshipException extends RuntimeException {}
