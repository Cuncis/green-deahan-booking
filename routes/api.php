<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\PembayaranController;
use App\Http\Middleware\IdentifikasiTenant;
use Illuminate\Support\Facades\Route;

Route::middleware(IdentifikasiTenant::class)->group(function () {
    Route::get('/booking/lapangan/{lapanganId}/slot', [BookingController::class, 'lihatSlot'])
        ->middleware('throttle:booking-slot')
        ->name('booking.lihat_slot');
    Route::post('/booking/slot/{slotId}/hold', [BookingController::class, 'holdSlot'])
        ->middleware('throttle:booking-hold')
        ->name('booking.hold_slot');
    Route::post('/booking/cek-membership', [BookingController::class, 'cekMembership'])->name('booking.cek_membership');
    Route::post('/booking', [BookingController::class, 'buatBooking'])
        ->middleware('throttle:booking-store')
        ->name('booking.buat');
    Route::get('/booking/{kodeBooking}/status', [BookingController::class, 'cekStatusBooking'])
        ->middleware('throttle:booking-slot')
        ->name('booking.status');
});

// Dipanggil server payment gateway (Midtrans/Xendit), bukan browser customer,
// jadi tidak lewat IdentifikasiTenant. Lihat references/multi-tenant.md.
Route::post('/webhook/pembayaran', [PembayaranController::class, 'webhook'])->name('webhook.pembayaran');
