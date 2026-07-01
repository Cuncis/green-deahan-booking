<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\PembayaranController;
use App\Http\Middleware\IdentifikasiTenant;
use Illuminate\Support\Facades\Route;

Route::middleware(IdentifikasiTenant::class)->group(function () {
    Route::get('/booking/lapangan/{lapanganId}/slot', [BookingController::class, 'lihatSlot'])->name('booking.lihat_slot');
    Route::post('/booking/slot/{slotId}/hold', [BookingController::class, 'holdSlot'])->name('booking.hold_slot');
    Route::post('/booking', [BookingController::class, 'buatBooking'])->name('booking.buat');
});

// Dipanggil server payment gateway (Midtrans/Xendit), bukan browser customer,
// jadi tidak lewat IdentifikasiTenant. Lihat references/multi-tenant.md.
Route::post('/webhook/pembayaran', [PembayaranController::class, 'webhook'])->name('webhook.pembayaran');
