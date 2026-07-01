<?php

use App\Http\Controllers\ProfileController;
use App\Models\Lapangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $tenant = app('tenant');

    $lapangan = Lapangan::with('cabang')
        ->where('tenant_id', $tenant->id)
        ->where('status_aktif', true)
        ->get();

    $lapanganAktif = $lapangan->firstWhere('id', (int) $request->query('lapangan'))
        ?? $lapangan->first();

    return view('pages.booking', [
        'tenant' => $tenant,
        'lapangan' => $lapangan,
        'lapanganAktif' => $lapanganAktif,
    ]);
})->name('booking.index');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('pages.admin.dashboard', [
            'tenant' => app('tenant'),
        ]);
    })->name('dashboard');

    Route::get('/laporan', function () {
        $tenant = app('tenant');

        abort_unless($tenant->punyaFitur('laporan_pendapatan'), 403, 'Fitur laporan pendapatan tidak tersedia untuk paket Anda.');

        return view('pages.admin.laporan', [
            'tenant' => $tenant,
        ]);
    })->name('laporan');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
