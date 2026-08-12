# Green Deahan Sport, Booking System

Aplikasi Laravel multi-tenant untuk bisnis booking lapangan olahraga (futsal, padel, badminton, tennis, mini soccer). Satu codebase melayani banyak klien (tenant), masing-masing dengan subdomain atau custom domain sendiri, dan tiga tingkat paket berlangganan: **Basic**, **Pro**, **Premium**.

Repo ini privat dan hanya untuk internal tim Green Deahan.

## Stack

- **Backend:** Laravel 13, PHP 8.4, MySQL
- **Frontend:** Blade + Livewire 4 (komponen interaktif seperti kalender dan slot booking) + Alpine.js (interaksi ringan tanpa round-trip server)
- **Styling:** Tailwind CSS murni, tanpa DaisyUI/Bootstrap/UI kit lain. Semua komponen dibangun custom dari design token brand.
- **Pembayaran:** Mayar (`App\Services\PaymentService`, integrasi lewat Http client, lihat `config/services.php`)
- **Notifikasi:** WhatsApp lewat link `wa.me` (lihat `app/Jobs/KirimNotifikasiWhatsApp.php`)
- **Storage foto:** Disk lokal secara default, Cloudflare R2 untuk production multi-server (`App\Services\FotoUploadService`, lihat bagian Deployment)

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
sudo systemctl reload php8.4-fpm.service
```

`migrate --force` wajib dijalankan tiap deploy yang bawa migration baru, Laravel menolak jalan migrate di production tanpa flag ini. Kalau migration itu mengubah kolom/tabel (drop column, ubah enum, dst), backup database dulu sebelum `migrate --force` (`mysqldump`), karena migration seperti itu susah di-rollback bersih kalau sudah ada data baru masuk setelah deploy.

**Catatan untuk rilis "hapus transfer manual" (Juli 2026):** rilis ini mengubah metode bayar tenant Basic dari transfer manual jadi Midtrans (QRIS/VA/e-wallet), termasuk migration yang memperketat enum `booking.tipe_pembayaran`/`pembayaran.metode` dan drop kolom `bank_nama`/`bank_no_rekening`/`bank_pemilik_rekening` dari `tenants`. Kalau ada tenant Basic yang sudah aktif pakai transfer manual, beri tahu mereka dulu sebelum deploy karena tampilan checkout customer mereka berubah begitu deploy ini naik (langsung tampil QRIS/VA/e-wallet, bukan info rekening lagi).

Server production menjalankan dua tugas background lewat **cron** (bukan Supervisor, tidak ada proses panjang yang perlu di-restart tiap deploy), konfigurasi ada di `scripts/cron/green-deahan-booking`:

- **Queue worker**, tiap menit cron panggil `queue:work --stop-when-empty` untuk proses job seperti `KirimNotifikasiWhatsApp` yang ada di antrian, lalu keluar sendiri begitu antrian kosong.
- **Scheduler**, tiap menit cron panggil `schedule:run`, yang menjalankan tugas terjadwal mana saja yang memang jatuh tempo di menit itu: `booking:lepas-slot-kadaluarsa` (tiap menit), `tenant:kirim-tagihan-perpanjangan` dan `tenant:nonaktifkan-tenant-kadaluarsa` (harian).

Karena keduanya dipanggil ulang dari nol tiap menit oleh cron, kode terbaru otomatis kepakai tiap kali jalan, tidak ada proses lama yang perlu di-restart manual setelah deploy.

Cara pasang di server (Ubuntu/Debian):

```bash
# Salin file cron ke /etc/cron.d, sesuaikan path /var/www/green-deahan-booking
# di dalam file kalau lokasi deploy-mu berbeda. Cron otomatis baca ulang
# /etc/cron.d tiap file berubah, tidak perlu perintah "reread"/"update" seperti
# Supervisor.
sudo cp scripts/cron/green-deahan-booking /etc/cron.d/green-deahan-booking
sudo chmod 644 /etc/cron.d/green-deahan-booking

# Cek jadwalnya sudah terbaca
sudo crontab -l -u www-data 2>/dev/null; cat /etc/cron.d/green-deahan-booking
```

Log queue worker ada di `storage/logs/queue-worker.log`. Scheduler tidak punya log terpisah, masing-masing command yang dijalankannya sudah `Log::info()`/`Log::warning()` sendiri (lihat `storage/logs/laravel.log`).

### Ownership Git di Server

Kalau `git pull` di server gagal dengan error `detected dubious ownership in repository`, jalankan sekali (sebagai user yang menjalankan deploy script):

```bash
git config --global --add safe.directory /var/www/green-deahan-booking
```

### Environment Production

- `MAIL_MAILER` wajib diganti dari `log` ke `resend` (driver API Resend bawaan Laravel, isi `RESEND_API_KEY`). Email verifikasi (`MustVerifyEmail`) tidak akan pernah terkirim selama masih `log`, jadi staf tenant tidak akan bisa lolos halaman verify-email.
- `ADMIN_EMAIL` menerima notifikasi internal platform (misalnya pendaftar tenant baru lewat `/daftar`, atau permintaan upgrade paket lewat halaman Lapangan Saya).

### Storage Foto (Cloudflare R2)

Default-nya (`FILESYSTEM_DISK=public`) foto lapangan/galeri/logo tenant/artikel tersimpan di disk lokal server (`storage/app/public`, di-serve lewat symlink `public/storage`). Ini tidak cocok untuk production multi-server (foto yang diupload di satu server tidak kelihatan dari server lain), jadi pindahkan ke Cloudflare R2 (S3-compatible, lebih murah dari AWS S3 asli untuk kasus ini karena tidak ada biaya egress):

1. Buat bucket baru di dashboard Cloudflare R2, lalu buat API token S3 (Account API Tokens, bukan token Cloudflare biasa) untuk dapat `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY`.
2. Aktifkan akses publik bucket-nya (R2.dev public access) atau hubungkan custom domain ke bucket, lalu catat domain publik itu.
3. Isi di `.env`:
   ```bash
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=...
   AWS_SECRET_ACCESS_KEY=...
   AWS_DEFAULT_REGION=auto
   AWS_BUCKET=nama-bucket-kamu
   AWS_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
   AWS_URL=https://pub-xxxxxxxx.r2.dev
   AWS_USE_PATH_STYLE_ENDPOINT=true
   ```
   `AWS_URL` wajib diisi (bukan opsional), dipakai `App\Services\FotoUploadService` membangun URL foto yang benar-benar bisa diakses publik, R2 tidak menyediakan `Storage::url()` otomatis tanpa ini.
4. `composer install` di server (paket `league/flysystem-aws-s3-v3` sudah ada di `composer.json`, tidak perlu install manual).

Semua logika simpan/hapus foto (4 lokasi: `LapanganAdminController`, `GaleriAdminController`, `SettingsAdminController`, `ArtikelAdminController`) sudah lewat satu `App\Services\FotoUploadService` yang otomatis baca `FILESYSTEM_DISK`, tidak ada kode lain yang perlu diubah.

## Struktur Project

Ikuti struktur direktori Laravel standar. Panduan penempatan file spesifik project ada di `.claude/skills/green-deahan-booking/references/struktur-project.md`. Jangan bikin folder dasar baru tanpa persetujuan.

## AI Coding Agent

Project ini dikembangkan bareng Claude Code / Laravel Boost. Konvensi wajib untuk agent ada di `CLAUDE.md` dan skill `.claude/skills/green-deahan-booking/`, termasuk aturan multi-tenant, anti double-booking, larangan DaisyUI, aturan icon SVG, dan larangan tanda strip panjang di teks.
