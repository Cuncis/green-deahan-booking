<?php

use App\Http\Controllers\Admin\BookingAdminController;
use App\Http\Controllers\Admin\JadwalAdminController;
use App\Http\Controllers\Admin\LapanganAdminController;
use App\Http\Controllers\Admin\SettingsAdminController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\GaleriController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Superadmin\ArtikelAdminController;
use App\Http\Controllers\Superadmin\GaleriAdminController;
use App\Http\Controllers\Superadmin\SuperadminController;
use App\Http\Controllers\TenantRegistrationController;
use App\Http\Middleware\IdentifikasiTenant;
use App\Models\Lapangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Situs korporat platform (greendeahan.com), bukan booking tenant manapun.
// Harus didaftarkan SEBELUM rute '/' generik di bawah dan dibatasi lewat
// domain() supaya subdomain tenant (xxx.greendeahan.com) dan custom domain
// klien tetap jatuh ke rute booking seperti biasa, cuma domain utama
// platform yang dialihkan ke sini. Dikecualikan dari IdentifikasiTenant
// karena greendeahan.com sendiri bukan tenant (sama seperti /harga, /daftar).
// Semua halaman situs korporat yang sudah dipindah dari
// green-deahan-wpnuxt (Nuxt) didaftarkan di closure ini per domain. Konten
// galeri & blog dikelola lewat /superadmin, lihat Superadmin\GaleriAdminController
// dan Superadmin\ArtikelAdminController.
$situsKorporat = function () {
    Route::get('/', fn () => view('pages.home'))->name('home');
    // Halaman yang dibangun dengan Inertia + React, lihat resources/js/pages/.
    // Halaman lain semua Blade + Livewire + Alpine.
    Route::get('/v2', fn () => Inertia::render('V2Home'))->name('home.v2');
    Route::get('/v2/konsep', fn () => Inertia::render('V2Konsep'))->name('home.v2.konsep');
    Route::get('/konsep', fn () => view('pages.konsep'))->name('konsep');
    Route::get('/galeri', [GaleriController::class, 'index'])->name('galeri');
    Route::get('/v2/galeri', [GaleriController::class, 'v2'])->name('home.v2.galeri');
    Route::get('/blog', [BlogController::class, 'index'])->name('blog');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
    Route::get('/kontak', fn () => view('pages.kontak'))->name('kontak');

    // Paket berlangganan situs booking, cross-sell dari layanan konstruksi
    // lapangan ke produk SaaS booking. CTA per paket mengarah ke /daftar
    // (TenantRegistrationController), yang sengaja TIDAK dibatasi domain
    // supaya tetap bisa dibuka langsung dari luar situs korporat.
    Route::get('/harga', fn () => view('pages.pricing'))->name('pricing');
};

Route::withoutMiddleware(IdentifikasiTenant::class)->domain('greendeahan.com')->group($situsKorporat);
Route::withoutMiddleware(IdentifikasiTenant::class)->domain('www.greendeahan.com')->name('www.')->group($situsKorporat);

// Preview situs korporat lokal tanpa mengganggu tenant 'localhost' yang
// sudah dipakai TestCase & seeder untuk uji coba booking (lihat
// tests/TestCase.php). *.localhost otomatis resolve ke 127.0.0.1 di browser
// modern (RFC 6761), jadi tidak perlu edit /etc/hosts. Hanya terdaftar di
// lingkungan local, tidak akan pernah ada di production.
if (app()->environment('local')) {
    Route::withoutMiddleware(IdentifikasiTenant::class)
        ->domain('greendeahan.localhost')
        ->name('local.')
        ->group($situsKorporat);
}

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

Route::middleware('guest')->group(function () {
    Route::get('/invite/{token}', [InvitationController::class, 'showInvite'])->name('invite.show');
    Route::post('/invite/{token}', [InvitationController::class, 'acceptInvite'])->name('invite.accept');
});

// Halaman pendaftaran tenant baru diakses dari domain utama platform,
// bukan domain tenant manapun, jadi sengaja dikeluarkan dari
// IdentifikasiTenant sama seperti rute superadmin. Sengaja TIDAK dibatasi
// domain() (beda dari $situsKorporat) supaya link "Pilih Paket" tetap
// bisa dibuka langsung dari mana saja, bukan cuma dari greendeahan.com.
Route::withoutMiddleware(IdentifikasiTenant::class)->group(function () {
    Route::get('/daftar', [TenantRegistrationController::class, 'show'])->name('daftar.show');
    Route::post('/daftar', [TenantRegistrationController::class, 'store'])->name('daftar.store');
    Route::get('/daftar/sukses', [TenantRegistrationController::class, 'sukses'])->name('daftar.sukses');
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

        // Konten situs korporat (/galeri, /blog di domain greendeahan.com),
        // bukan data tenant, lihat GaleriAdminController & ArtikelAdminController.
        Route::get('/galeri', [GaleriAdminController::class, 'index'])->name('galeri');
        Route::get('/galeri/tambah', [GaleriAdminController::class, 'create'])->name('galeri.create');
        Route::post('/galeri', [GaleriAdminController::class, 'store'])->name('galeri.store');
        Route::get('/galeri/{galeri}/edit', [GaleriAdminController::class, 'edit'])->name('galeri.edit');
        Route::put('/galeri/{galeri}', [GaleriAdminController::class, 'update'])->name('galeri.update');
        Route::delete('/galeri/{galeri}', [GaleriAdminController::class, 'destroy'])->name('galeri.destroy');

        Route::get('/artikel', [ArtikelAdminController::class, 'index'])->name('artikel');
        Route::get('/artikel/tulis', [ArtikelAdminController::class, 'create'])->name('artikel.create');
        Route::post('/artikel', [ArtikelAdminController::class, 'store'])->name('artikel.store');
        Route::get('/artikel/{artikel}/edit', [ArtikelAdminController::class, 'edit'])->name('artikel.edit');
        Route::put('/artikel/{artikel}', [ArtikelAdminController::class, 'update'])->name('artikel.update');
        Route::delete('/artikel/{artikel}', [ArtikelAdminController::class, 'destroy'])->name('artikel.destroy');
    });

Route::middleware(['auth', 'verified', 'check.tenant.staf'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('pages.admin.dashboard', [
            'tenant' => app('tenant'),
        ]);
    })->name('dashboard');

    Route::get('/laporan', function () {
        $tenant = app('tenant');
        abort_unless($tenant->punyaFitur('laporan_pendapatan'), 404);

        return view('pages.admin.laporan', ['tenant' => $tenant]);
    })->name('laporan');

    Route::get('/promo', function () {
        $tenant = app('tenant');
        abort_unless($tenant->punyaFitur('kode_promo'), 404);

        return view('pages.admin.promo', ['tenant' => $tenant]);
    })->name('promo');

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
    Route::post('/lapangan/minta-upgrade', [LapanganAdminController::class, 'mintaUpgrade'])->name('lapangan.minta-upgrade');

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
