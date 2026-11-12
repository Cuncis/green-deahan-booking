<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Gagal bicara dengan API Mayar. Pesannya aman ditampilkan atau dicatat:
 * tidak pernah berisi API key atau header request.
 */
class MayarException extends RuntimeException {}
