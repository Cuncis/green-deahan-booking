<?php

namespace App\Listeners;

use App\Services\PeringatanErrorService;
use Illuminate\Log\Events\MessageLogged;

/**
 * Setiap pesan log melewati sini; layanan memutuskan apakah perlu peringatan.
 */
class KirimPeringatanErrorBaru
{
    public function __construct(private readonly PeringatanErrorService $peringatan) {}

    public function handle(MessageLogged $event): void
    {
        $this->peringatan->tangani($event);
    }
}
