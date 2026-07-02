<?php

use App\Http\Controllers\Admin\BookingAdminController;
use App\Http\Controllers\Admin\JadwalAdminController;
use App\Http\Controllers\Admin\LapanganAdminController;
use App\Http\Controllers\Admin\SettingsAdminController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Superadmin\SuperadminController;
use App\Http\Middleware\IdentifikasiTenant;
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

Route::middleware('guest')->group(function () {
    Route::get('/invite/{token}', [InvitationController::class, 'showInvite'])->name('invite.show');
    Route::post('/invite/{token}', [InvitationController::class, 'acceptInvite'])->name('invite.accept');
});

// Superadmin platform: independen dari tenant manapun, jadi sengaja
// dikeluarkan dari IdentifikasiTenant (yang biasanya wajib di semua route
// 'web' lewat bootstrap/app.php). Tanpa ini, superadmin tidak akan pernah
// bisa diakses lewat domain yang bukan domain tenant manapun.
Route::middleware(['auth', 'verified', 'check.superadmin'])
    ->withoutMiddleware(IdentifikasiTenant::class)
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/', [SuperadminController::class, 'dashboard'])->name('dashboard');
        Route::get('/tenants', [SuperadminController::class, 'tenants'])->name('tenants');
        Route::get('/tenants/buat', [SuperadminController::class, 'create'])->name('tenants.create');
        Route::post('/tenants', [SuperadminController::class, 'store'])->name('tenants.store');
        Route::post('/tenants/{tenant}/aktifkan', [SuperadminController::class, 'activate'])->name('tenants.activate');
        Route::post('/tenants/{tenant}/nonaktifkan', [SuperadminController::class, 'deactivate'])->name('tenants.deactivate');
        Route::post('/tenants/{tenant}/invite', [SuperadminController::class, 'inviteOwner'])->name('tenants.invite');
        Route::get('/tenants/{tenant}', [SuperadminController::class, 'show'])->name('tenants.show');
        Route::post('/tenants/{tenant}/custom-domain', [SuperadminController::class, 'setCustomDomain'])->name('tenants.custom-domain.store');
        Route::delete('/tenants/{tenant}/custom-domain', [SuperadminController::class, 'removeCustomDomain'])->name('tenants.custom-domain.destroy');
    });

Route::middleware(['auth', 'verified', 'check.tenant.staf'])->prefix('admin')->name('admin.')->group(function () {
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

    Route::get('/booking', [BookingAdminController::class, 'index'])->name('booking');
    Route::get('/booking/export', [BookingAdminController::class, 'export'])->name('booking.export');
    Route::post('/booking/{booking}/confirm', [BookingAdminController::class, 'confirm'])->name('booking.confirm');
    Route::post('/booking/{booking}/cancel', [BookingAdminController::class, 'cancel'])->name('booking.cancel');

    Route::get('/lapangan', [LapanganAdminController::class, 'index'])->name('lapangan');
    Route::get('/lapangan/tambah', [LapanganAdminController::class, 'create'])->name('lapangan.create');
    Route::post('/lapangan', [LapanganAdminController::class, 'store'])->name('lapangan.store');
    Route::get('/lapangan/{lapangan}/edit', [LapanganAdminController::class, 'edit'])->name('lapangan.edit');
    Route::put('/lapangan/{lapangan}', [LapanganAdminController::class, 'update'])->name('lapangan.update');
    Route::delete('/lapangan/{lapangan}', [LapanganAdminController::class, 'destroy'])->name('lapangan.destroy');

    Route::get('/jadwal', [JadwalAdminController::class, 'index'])->name('jadwal');

    Route::get('/pengaturan', [SettingsAdminController::class, 'show'])->name('pengaturan');
    Route::put('/pengaturan', [SettingsAdminController::class, 'update'])->name('pengaturan.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
