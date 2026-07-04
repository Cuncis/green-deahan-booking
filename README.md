# Green Deahan Sport, Booking System

Aplikasi Laravel multi-tenant untuk bisnis booking lapangan olahraga (futsal, padel, badminton, tennis, mini soccer). Satu codebase melayani banyak klien (tenant), masing-masing dengan subdomain atau custom domain sendiri, dan tiga tingkat paket berlangganan: **Basic**, **Pro**, **Premium**.

Repo ini privat dan hanya untuk internal tim Green Deahan.

## Stack

- **Backend:** Laravel 13, PHP 8.4, MySQL
- **Frontend:** Blade + Livewire 4 (komponen interaktif seperti kalender dan slot booking) + Alpine.js (interaksi ringan tanpa round-trip server)
- **Styling:** Tailwind CSS murni, tanpa DaisyUI/Bootstrap/UI kit lain. Semua komponen dibangun custom dari design token brand.
- **Pembayaran:** Midtrans (`midtrans/midtrans-php`)
- **Notifikasi:** WhatsApp lewat link `wa.me` (lihat `app/Jobs/KirimNotifikasiWhatsApp.php`)

Konvensi lengkap penamaan, arsitektur multi-tenant, anti double-booking, fitur per paket, dan design token ada di `.claude/skills/green-deahan-booking/`. Ini sumber kebenaran untuk konvensi project, wajib dibaca sebelum menyentuh area terkait.

## Arsitektur Multi-Tenant

Setiap request masuk lewat middleware `App\Http\Middleware\IdentifikasiTenant`, yang mencocokkan hostname request ke kolom `domain` atau `custom_domain` di tabel `tenants`. Tenant yang cocok (dan aktif) di-bind ke `app('tenant')` untuk dipakai di seluruh controller/view request itu.

Rute yang **bukan** milik tenant manapun (situs korporat di `greendeahan.com`, halaman pendaftaran `/daftar`, dan `/superadmin`) sengaja dikecualikan dari middleware ini lewat `withoutMiddleware(IdentifikasiTenant::class)`, lihat `routes/web.php`.

Setiap query yang menyentuh data milik tenant **wajib** difilter `tenant_id`. Detail dan contoh ada di `.claude/skills/green-deahan-booking/references/multi-tenant.md`.

## Fitur per Paket

Fitur yang aktif untuk satu tenant disimpan di tabel `tenant_fitur`, bukan di-hardcode. Cek fitur lewat `$tenant->punyaFitur('nama_fitur')` sebelum merender apapun yang bukan fitur dasar (booking online, notifikasi WA). Preset fitur per paket ada di `App\Models\TenantFitur::presetUntukPaket()`.

Daftar fitur lengkap dan paket pemiliknya ada di `.claude/skills/green-deahan-booking/references/fitur-per-paket.md`.

## Anti Double-Booking

Operasi yang mengubah status `jadwal_slot` wajib pakai `DB::transaction()` + `lockForUpdate()` supaya dua customer tidak bisa mengambil slot yang sama secara bersamaan. Slot yang di-hold tapi tidak diselesaikan pembayarannya otomatis dilepas lewat scheduled command `booking:lepas-slot-kadaluarsa` (jalan tiap menit).

Detail mekanisme ada di `.claude/skills/green-deahan-booking/references/anti-double-booking.md`.

## Setup Lokal

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate --seed
```

Atau pakai script bawaan:

```bash
composer run setup
```

Jalankan server dev (Laravel, queue listener, log viewer Pail, dan Vite sekaligus):

```bash
composer run dev
```

Domain tenant lokal pakai `*.localhost` (auto-resolve ke `127.0.0.1` di browser modern, tidak perlu edit `/etc/hosts`). Situs korporat lokal ada di `greendeahan.localhost`.

## Perintah Artisan Kustom

| Perintah | Fungsi |
|---|---|
| `tenant:list` | Tampilkan tabel semua tenant terdaftar |
| `tenant:activate {tenant}` | Aktifkan tenant (perpanjang masa aktif), opsional ganti paket |
| `tenant:deactivate {tenant}` | Nonaktifkan tenant |
| `tenant:invite {tenant}` | Buat link undangan untuk staf tenant |
| `booking:lepas-slot-kadaluarsa` | Lepas slot hold yang sudah lewat batas waktu, jalan tiap menit lewat scheduler |

## Testing

```bash
php artisan test --compact
```

Semua perubahan wajib disertai test (feature test lebih diutamakan daripada unit test). Filter test tertentu:

```bash
php artisan test --compact --filter=namaTest
```

## Code Style

```bash
vendor/bin/pint --dirty --format agent
```

Jalankan sebelum commit kalau ada file PHP yang diubah.

## Deployment (Production)

### Deploy Update Terbaru

Setiap kali ada perubahan yang perlu dinaikkan ke production, jalankan `./deploy.sh` di server (lihat bagian "Ownership Git di Server" di bawah kalau kena error dubious ownership). Kalau belum ada `deploy.sh` atau mau jalankan manual:

```bash
cd /var/www/green-deahan-booking
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart green-deahan-queue:* green-deahan-scheduler:*
```

`migrate --force` wajib dijalankan tiap deploy yang bawa migration baru, Laravel menolak jalan migrate di production tanpa flag ini. Kalau migration itu mengubah kolom/tabel (drop column, ubah enum, dst), backup database dulu sebelum `migrate --force` (`mysqldump`), karena migration seperti itu susah di-rollback bersih kalau sudah ada data baru masuk setelah deploy.

**Catatan untuk rilis "hapus transfer manual" (Juli 2026):** rilis ini mengubah metode bayar tenant Basic dari transfer manual jadi Midtrans (QRIS/VA/e-wallet), termasuk migration yang memperketat enum `booking.tipe_pembayaran`/`pembayaran.metode` dan drop kolom `bank_nama`/`bank_no_rekening`/`bank_pemilik_rekening` dari `tenants`. Kalau ada tenant Basic yang sudah aktif pakai transfer manual, beri tahu mereka dulu sebelum deploy karena tampilan checkout customer mereka berubah begitu deploy ini naik (langsung tampil QRIS/VA/e-wallet, bukan info rekening lagi).

Server production menjalankan dua proses background lewat [Supervisor](http://supervisord.org/), konfigurasi ada di `scripts/supervisor/laravel.conf`:

- **Queue worker**, memproses job seperti `KirimNotifikasiWhatsApp` lewat `queue:work`.
- **Scheduler**, menjalankan `booking:lepas-slot-kadaluarsa` tiap menit lewat `schedule:work`.

Cara pasang di server (Ubuntu/Debian):

```bash
# 1. Install Supervisor kalau belum ada
sudo apt-get update && sudo apt-get install -y supervisor

# 2. Salin config ke folder Supervisor, sesuaikan path /var/www/green-deahan-booking
#    di dalam file kalau lokasi deploy-mu berbeda
sudo cp scripts/supervisor/laravel.conf /etc/supervisor/conf.d/green-deahan-booking.conf

# 3. Baca ulang config dan jalankan program-nya
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start green-deahan-queue:*
sudo supervisorctl start green-deahan-scheduler:*

# 4. Cek statusnya
sudo supervisorctl status
```

Setelah dipasang, Supervisor otomatis merestart kedua proses ini kalau crash atau server reboot (`autostart=true`, `autorestart=true`). Log masing-masing proses ada di `storage/logs/queue-worker.log` dan `storage/logs/scheduler.log`.

Kalau ganti kode, jangan lupa `sudo supervisorctl restart green-deahan-queue:*` supaya worker pakai kode terbaru (worker PHP yang sudah jalan tidak otomatis reload class yang berubah).

### Ownership Git di Server

Kalau `git pull` di server gagal dengan error `detected dubious ownership in repository`, jalankan sekali (sebagai user yang menjalankan deploy script):

```bash
git config --global --add safe.directory /var/www/green-deahan-booking
```

### Environment Production

- `MAIL_MAILER` wajib diganti dari `log` ke driver SMTP asli. Email verifikasi (`MustVerifyEmail`) tidak akan pernah terkirim selama masih `log`, jadi staf tenant tidak akan bisa lolos halaman verify-email.
- `ADMIN_EMAIL` menerima notifikasi internal platform (misalnya pendaftar tenant baru lewat `/daftar`).

## Struktur Project

Ikuti struktur direktori Laravel standar. Panduan penempatan file spesifik project ada di `.claude/skills/green-deahan-booking/references/struktur-project.md`. Jangan bikin folder dasar baru tanpa persetujuan.

## AI Coding Agent

Project ini dikembangkan bareng Claude Code / Laravel Boost. Konvensi wajib untuk agent ada di `CLAUDE.md` dan skill `.claude/skills/green-deahan-booking/`, termasuk aturan multi-tenant, anti double-booking, larangan DaisyUI, aturan icon SVG, dan larangan tanda strip panjang di teks.
