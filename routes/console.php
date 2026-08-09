<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('booking:lepas-slot-kadaluarsa')->everyMinute();
Schedule::command('tenant:kirim-tagihan-perpanjangan')->daily();
Schedule::command('tenant:nonaktifkan-tenant-kadaluarsa')->daily();
