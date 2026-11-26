<?php

use App\Http\Controllers\Admin\BookingAdminController;
use App\Http\Controllers\Admin\DashboardAdminController;
use App\Http\Controllers\Admin\JadwalAdminController;
use App\Http\Controllers\Admin\LapanganAdminController;
use App\Http\Controllers\Admin\LaporanAdminController;
use App\Http\Controllers\Admin\PromoAdminController;
use App\Http\Controllers\Admin\SettingsAdminController;
use App\Http\Controllers\Admin\StafAdminController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\GaleriController;
use App\Http\Controllers\HalamanBookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SistemBookingController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Superadmin\ArtikelAdminController;
use App\Http\Controllers\Superadmin\BannerPromoController;
use App\Http\Controllers\Superadmin\CatatanPromosiController;
use App\Http\Controllers\Superadmin\GaleriAdminController;
use App\Http\Controllers\Superadmin\LogErrorController;
use App\Http\Controllers\Superadmin\MediaAdminController;
use App\Http\Controllers\Superadmin\NotifikasiController;
use App\Http\Controllers\Superadmin\PengaturanNotifikasiController;
use App\Http\Controllers\Superadmin\SeoAdminController;
use App\Http\Controllers\Superadmin\SuperadminController;
use App\Http\Controllers\Superadmin\Toko\PengaturanOngkirController;
use App\Http\Controllers\Superadmin\Toko\PesananTokoAdminController;
use App\Http\Controllers\Superadmin\Toko\ProdukTokoController;
use App\Http\Controllers\Superadmin\Toko\PromoTokoController;
use App\Http\Controllers\Superadmin\Toko\SupplierTokoController;
use App\Http\Controllers\TenantRegistrationController;
use App\Http\Controllers\Toko\CheckoutController;
use App\Http\Controllers\Toko\KeranjangController;
use App\Http\Controllers\Toko\PesananTokoController;
use App\Http\Controllers\Toko\TokoController;
use App\Http\Controllers\Toko\WilayahController;
use App\Http\Middleware\CatatKunjungan;
use App\Http\Middleware\IdentifikasiTenant;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Situs korporat platform (greendeahan.com), bukan booking tenant manapun.
// Harus didaftarkan SEBELUM rute '/' generik di bawah dan dibatasi lewat
// domain() supaya subdomain tenant (xxx.greendeahan.com) dan custom domain
// klien tetap jatuh ke rute booking seperti biasa, cuma domain utama
// platform yang dialihkan ke sini. Dikecualikan dari IdentifikasiTenant
// karena greendeahan.com sendiri bukan tenant (sama seperti /daftar).
// Semua halaman situs korporat yang sudah dipindah dari
// green-deahan-wpnuxt (Nuxt) didaftarkan di closure ini per domain. Konten
// galeri & blog dikelola lewat /superadmin, lihat Superadmin\GaleriAdminController
// dan Superadmin\ArtikelAdminController.
$situsKorporat = function () {
    // Homepage, Tentang Kami, Kontak, Galeri dan Blog dibangun dengan Inertia + React
    // (resources/js/pages/), porting dari template Fuzio. Prop `meta`
    // dipakai resources/views/app.blade.php untuk <title> dan meta
    // description. Homepage & Kontak Blade lama tetap bisa dibuka di /v2 dan
    // /v2/kontak (noindex).
    // Halaman situs korporat lain masih Blade + Alpine.
    Route::get('/', fn () => Inertia::render('Home', [
        'meta' => [
            'title' => 'Green Deahan Sport - Jasa Pembuatan Lapangan Se-Indonesia',
            'description' => 'Jasa pembuatan lapangan futsal, mini soccer, padel, dan badminton sejak 2010. Survey, desain, konstruksi, hingga serah terima. Melayani seluruh Indonesia.',
        ],
    ]))->name('home');
    Route::get('/v2', fn () => view('pages.home'))->name('home.v2');
    Route::get('/tentang-kami', fn () => Inertia::render('TentangKami', [
        'meta' => [
            'title' => 'Tentang Kami - Green Deahan Sport',
            'description' => 'Green Deahan Sport, jasa pembuatan lapangan futsal, mini soccer, padel, dan badminton profesional sejak 2010. Melayani seluruh Indonesia.',
        ],
    ]))->name('tentang_kami');
    // Halaman Konsep sudah diganti Tentang Kami, 301 supaya link lama dan
    // posisi di Google pindah ke halaman baru.
    Route::permanentRedirect('/konsep', '/tentang-kami');
    // Layanan (dropdown di menu).
    Route::get('/bangun-lapangan', fn () => Inertia::render('BangunLapangan', [
        'meta' => [
            'title' => 'Jasa Bangun Lapangan Futsal & Padel - Green Deahan Sport',
            'description' => 'Jasa pembuatan lapangan futsal, mini soccer, padel, dan badminton sejak 2010. Survey, desain, perizinan, konstruksi, sampai serah terima. Minta penawaran.',
        ],
    ]))->name('bangun-lapangan');
    Route::get('/perawatan', fn () => Inertia::render('Perawatan', [
        'meta' => [
            'title' => 'Perawatan & Renovasi Lapangan Olahraga - Green Deahan Sport',
            'description' => 'Perawatan berkala, perbaikan kerusakan, dan renovasi untuk lapangan futsal, mini soccer, padel, dan badminton yang dibangun Green Deahan. Pengecekan gratis.',
        ],
    ]))->name('perawatan');
    Route::get('/upgrade-lapangan', fn () => Inertia::render('UpgradeLapangan', [
        'meta' => [
            'title' => 'Upgrade Lapangan: Lampu, Atap, Jaring - Green Deahan Sport',
            'description' => 'Upgrade lapangan futsal, mini soccer, padel, dan badminton: lampu LED, atap, jaring, ganti permukaan, hingga ubah olahraga. Untuk semua lapangan, cek gratis.',
        ],
    ]))->name('upgrade-lapangan');
    Route::get('/sistem-booking', SistemBookingController::class)->name('sistem-booking');
    Route::get('/galeri', [GaleriController::class, 'index'])->name('galeri');
    // Desain Galeri Blade lama, noindex (lihat pages/galeri.blade.php).
    Route::get('/v2/galeri', [GaleriController::class, 'indexV2'])->name('galeri.v2');
    Route::get('/blog', [BlogController::class, 'index'])->name('blog');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
    // Desain Blog Blade lama, noindex (lihat pages/blog*.blade.php).
    Route::get('/v2/blog', [BlogController::class, 'indexV2'])->name('blog.v2');
    Route::get('/v2/blog/{slug}', [BlogController::class, 'showV2'])->name('blog.v2.show');
    Route::get('/kontak', fn () => Inertia::render('Kontak', [
        'meta' => [
            'title' => 'Kontak Kami - Green Deahan Sport',
            'description' => 'Hubungi Green Deahan Sport untuk minta penawaran pembuatan lapangan futsal, mini soccer, padel, dan badminton. Tim kami respon dalam 1 jam.',
        ],
    ]))->name('kontak');
    // Halaman Kontak Blade lama, noindex (lihat pages/kontak.blade.php).
    Route::get('/v2/kontak', fn () => view('pages.kontak'))->name('kontak.v2');

    // Halaman harga lama digantikan bagian paket di /sistem-booking. CTA per
    // paket di sana mengarah ke /daftar (TenantRegistrationController).
    Route::permanentRedirect('/harga', '/sistem-booking#paket');

    // Halaman legal, isi di resources/js/data/legal.ts.
    foreach ([
        'kebijakan-privasi' => ['Kebijakan Privasi', 'Data pribadi yang dikumpulkan Green Deahan Sport, penggunaannya, pihak penerima, dan hak Anda sesuai UU Pelindungan Data Pribadi.'],
        'syarat-ketentuan' => ['Syarat & Ketentuan', 'Syarat dan ketentuan penggunaan situs, toko online, pembayaran, dan Sistem Booking Green Deahan Sport.'],
        'pengiriman-pengembalian' => ['Pengiriman & Pengembalian', 'Ketentuan pengiriman, komplain barang, penukaran, dan pengembalian dana pesanan toko online Green Deahan Sport.'],
        'kebijakan-cookie' => ['Kebijakan Cookie', 'Cookie dan penyimpanan browser yang dipakai situs Green Deahan Sport serta cara mengaturnya.'],
    ] as $slug => [$judul, $deskripsi]) {
        Route::get('/'.$slug, fn () => Inertia::render('Legal', [
            'slug' => $slug,
            'meta' => ['title' => $judul.' - Green Deahan Sport', 'description' => $deskripsi],
        ]))->name('legal.'.$slug);
    }

    Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

    // Toko online (dropship), lihat App\Http\Controllers\Toko. Keranjang di
    // session, harga selalu dihitung ulang di server, bayar lewat Mayar.
    Route::get('/checkout/ongkir', [CheckoutController::class, 'ongkir'])->middleware('throttle:ongkir-toko')->name('checkout.ongkir');
    Route::get('/checkout/promo', [CheckoutController::class, 'promo'])->middleware('throttle:promo-toko')->name('checkout.promo');
    Route::get('/wilayah', [WilayahController::class, 'index'])->middleware('throttle:120,1')->name('wilayah');
    Route::get('/toko', [TokoController::class, 'index'])->name('toko');
    Route::get('/toko/{slug}', [TokoController::class, 'show'])->name('toko.produk');
    Route::get('/keranjang', [KeranjangController::class, 'index'])->name('keranjang');
    Route::get('/keranjang/ringkas', [KeranjangController::class, 'ringkas'])->name('keranjang.ringkas');
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/keranjang', [KeranjangController::class, 'store'])->name('keranjang.tambah');
        Route::patch('/keranjang/{kunci}', [KeranjangController::class, 'update'])->where('kunci', '\d+(:\d+)?')->name('keranjang.ubah');
        Route::delete('/keranjang/{kunci}', [KeranjangController::class, 'destroy'])->where('kunci', '\d+(:\d+)?')->name('keranjang.hapus');
    });
    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout-toko')->name('checkout.store');
    Route::get('/pesanan/{code}', [PesananTokoController::class, 'show'])->name('toko.pesanan');
};

Route::withoutMiddleware(IdentifikasiTenant::class)->middleware(CatatKunjungan::class)->domain('greendeahan.com')->group($situsKorporat);
Route::withoutMiddleware(IdentifikasiTenant::class)->middleware(CatatKunjungan::class)->domain('www.greendeahan.com')->name('www.')->group($situsKorporat);

// Preview situs korporat lokal tanpa mengganggu tenant 'localhost' yang
// sudah dipakai TestCase & seeder untuk uji coba booking (lihat
// tests/TestCase.php). *.localhost otomatis resolve ke 127.0.0.1 di browser
// modern (RFC 6761), jadi tidak perlu edit /etc/hosts. Hanya terdaftar di
// lingkungan local, tidak akan pernah ada di production.
if (app()->environment('local')) {
    Route::withoutMiddleware(IdentifikasiTenant::class)
        ->middleware(CatatKunjungan::class)
        ->domain('greendeahan.localhost')
        ->name('local.')
        ->group($situsKorporat);
}

Route::get('/', [HalamanBookingController::class, 'index'])->name('booking.index');

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

        // Banner pop-up promo situs korporat.
        Route::get('/banner-promo', [BannerPromoController::class, 'index'])->name('banner');
        Route::post('/banner-promo', [BannerPromoController::class, 'store'])->name('banner.store');
        Route::put('/banner-promo/{banner}', [BannerPromoController::class, 'update'])->name('banner.update');
        Route::delete('/banner-promo/{banner}', [BannerPromoController::class, 'destroy'])->name('banner.destroy');

        // Catatan promosi dan iklan (rencana Google Ads, prompt gambar).
        Route::get('/catatan-promosi', [CatatanPromosiController::class, 'index'])->name('catatan');
        Route::post('/catatan-promosi', [CatatanPromosiController::class, 'store'])->name('catatan.store');
        Route::put('/catatan-promosi/{catatan}', [CatatanPromosiController::class, 'update'])->name('catatan.update');
        Route::delete('/catatan-promosi/{catatan}', [CatatanPromosiController::class, 'destroy'])->name('catatan.destroy');

        // Pustaka media: semua aset gambar lintas tenant dan situs korporat.
        Route::get('/media', [MediaAdminController::class, 'index'])->name('media');
        Route::get('/media/daftar', [MediaAdminController::class, 'daftar'])->name('media.daftar');
        Route::post('/media', [MediaAdminController::class, 'store'])->name('media.store');
        Route::delete('/media', [MediaAdminController::class, 'destroy'])->name('media.destroy');

        // Monitoring: log error, penerima email error, dan lonceng notifikasi.
        Route::get('/log-error', [LogErrorController::class, 'index'])->name('log-error');
        Route::get('/pengaturan/notifikasi', [PengaturanNotifikasiController::class, 'edit'])->name('notifikasi.pengaturan');
        Route::put('/pengaturan/notifikasi', [PengaturanNotifikasiController::class, 'update'])->name('notifikasi.simpan');
        Route::post('/pengaturan/notifikasi/uji', [PengaturanNotifikasiController::class, 'uji'])->name('notifikasi.uji');
        Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
        Route::post('/notifikasi/{id}/buka', [NotifikasiController::class, 'buka'])->name('notifikasi.buka');

        Route::get('/pengaturan-seo', [SeoAdminController::class, 'edit'])->name('seo');
        Route::put('/pengaturan-seo', [SeoAdminController::class, 'update'])->name('seo.update');

        Route::get('/artikel', [ArtikelAdminController::class, 'index'])->name('artikel');
        Route::get('/artikel/tulis', [ArtikelAdminController::class, 'create'])->name('artikel.create');
        Route::post('/artikel', [ArtikelAdminController::class, 'store'])->name('artikel.store');
        Route::get('/artikel/{artikel}/edit', [ArtikelAdminController::class, 'edit'])->name('artikel.edit');
        Route::put('/artikel/{artikel}', [ArtikelAdminController::class, 'update'])->name('artikel.update');
        Route::delete('/artikel/{artikel}', [ArtikelAdminController::class, 'destroy'])->name('artikel.destroy');

        // Toko online (dropship) di situs korporat.
        Route::prefix('toko')->name('toko.')->group(function () {
            Route::get('/pesanan', [PesananTokoAdminController::class, 'index'])->name('pesanan');
            Route::get('/pesanan/{order}', [PesananTokoAdminController::class, 'show'])->name('pesanan.show');
            Route::post('/pesanan/{order}/status', [PesananTokoAdminController::class, 'ubahStatus'])->name('pesanan.status');
            Route::post('/pesanan/{order}/resi', [PesananTokoAdminController::class, 'tambahResi'])->name('pesanan.resi.tambah');
            Route::delete('/pesanan/{order}/resi/{shipment}', [PesananTokoAdminController::class, 'hapusResi'])->name('pesanan.resi.hapus');
            Route::post('/pesanan/{order}/pesan', [PesananTokoAdminController::class, 'kirimPesan'])->name('pesanan.pesan');
            Route::post('/pesanan/{order}/refund', [PesananTokoAdminController::class, 'refundSelesai'])->name('pesanan.refund');
            Route::put('/pesanan/{order}/catatan', [PesananTokoAdminController::class, 'simpanCatatan'])->name('pesanan.catatan');

            Route::get('/produk', [ProdukTokoController::class, 'index'])->name('produk');
            Route::get('/produk/tambah', [ProdukTokoController::class, 'create'])->name('produk.create');
            Route::post('/produk/impor', [ProdukTokoController::class, 'impor'])->middleware('throttle:20,1')->name('produk.impor');
            Route::post('/produk', [ProdukTokoController::class, 'store'])->name('produk.store');
            Route::get('/produk/{product}/edit', [ProdukTokoController::class, 'edit'])->name('produk.edit');
            Route::put('/produk/{product}', [ProdukTokoController::class, 'update'])->name('produk.update');
            Route::delete('/produk/{product}', [ProdukTokoController::class, 'destroy'])->name('produk.destroy');

            Route::get('/supplier', [SupplierTokoController::class, 'index'])->name('supplier');
            Route::post('/supplier', [SupplierTokoController::class, 'store'])->name('supplier.store');
            Route::put('/supplier/{supplier}', [SupplierTokoController::class, 'update'])->name('supplier.update');
            Route::delete('/supplier/{supplier}', [SupplierTokoController::class, 'destroy'])->name('supplier.destroy');

            Route::get('/ongkir', [PengaturanOngkirController::class, 'edit'])->name('ongkir');
            Route::put('/ongkir', [PengaturanOngkirController::class, 'update'])->name('ongkir.simpan');
            Route::post('/ongkir/uji', [PengaturanOngkirController::class, 'uji'])->middleware('throttle:10,1')->name('ongkir.uji');
            Route::post('/ongkir/coba-lagi', [PengaturanOngkirController::class, 'cobaLagi'])->name('ongkir.coba-lagi');
            Route::post('/ongkir/setel-ulang', [PengaturanOngkirController::class, 'setelUlang'])->name('ongkir.setel-ulang');

            Route::get('/promo', [PromoTokoController::class, 'index'])->name('promo');
            Route::post('/promo', [PromoTokoController::class, 'store'])->name('promo.store');
            Route::put('/promo/{promo}', [PromoTokoController::class, 'update'])->name('promo.update');
            Route::delete('/promo/{promo}', [PromoTokoController::class, 'destroy'])->name('promo.destroy');
        });
    });

Route::middleware(['auth', 'verified', 'check.tenant.staf'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardAdminController::class, 'index'])->name('dashboard');
    Route::post('/reminder/{reminderLog}/terkirim', [DashboardAdminController::class, 'tandaiReminderTerkirim'])
        ->whereNumber('reminderLog')->name('reminder.terkirim');
    Route::post('/staf', [StafAdminController::class, 'store'])->name('staf.store');
    Route::put('/staf/{staf}/role', [StafAdminController::class, 'ubahRole'])->whereNumber('staf')->name('staf.role');

    Route::get('/laporan', [LaporanAdminController::class, 'index'])->name('laporan');
    Route::get('/promo', [PromoAdminController::class, 'index'])->name('promo');
    Route::post('/promo', [PromoAdminController::class, 'store'])->name('promo.store');

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
    Route::post('/jadwal/generate', [JadwalAdminController::class, 'generate'])->name('jadwal.generate');
    Route::put('/jadwal/slot/{slot}/harga', [JadwalAdminController::class, 'ubahHarga'])->whereNumber('slot')->name('jadwal.harga');
    Route::post('/jadwal/slot/{slot}/status', [JadwalAdminController::class, 'toggleStatus'])->whereNumber('slot')->name('jadwal.status');

    Route::get('/pengaturan', [SettingsAdminController::class, 'show'])->name('pengaturan');
    Route::put('/pengaturan', [SettingsAdminController::class, 'update'])->name('pengaturan.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
