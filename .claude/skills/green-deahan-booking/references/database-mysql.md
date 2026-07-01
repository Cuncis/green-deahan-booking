# Database: MySQL

Project ini pakai **MySQL** (bukan PostgreSQL). Skema tabel yang sempat dirancang sebelumnya pakai sintaks PostgreSQL sebagai contoh awal, jadi ada beberapa penyesuaian kecil yang perlu diperhatikan saat menulis migration Laravel.

## Kenapa MySQL Tetap Cocok untuk Project Ini

Logic paling kritis di project ini (constraint unique anti double-booking, row locking pakai `lockForUpdate()`) berjalan sama persis di MySQL maupun PostgreSQL. Tidak ada alasan teknis untuk pindah ke PostgreSQL hanya karena skema awal pernah dicontohkan pakai sintaks Postgres. Laravel Eloquent dan query builder sudah mengabstraksi perbedaan ini, jadi migration yang ditulis pakai Laravel akan otomatis menyesuaikan ke MySQL tanpa perlu tulis SQL mentah.

## Penyesuaian yang Perlu Diperhatikan

**JSON column** untuk `raw_response_gateway` di tabel `pembayaran`: MySQL 5.7+ dan MySQL 8 sudah mendukung tipe `JSON` native, jadi di migration Laravel cukup pakai:
```php
$table->json('raw_response_gateway')->nullable();
```
Tidak perlu `JSONB` (itu istilah khusus PostgreSQL).

**UUID column** untuk `recurring_group_id` di tabel `booking`: Laravel migration pakai:
```php
$table->uuid('recurring_group_id')->nullable();
```
Ini otomatis disesuaikan Laravel jadi tipe `CHAR(36)` di MySQL. Tidak perlu khawatir bedanya, Eloquent yang urus.

**ENUM column**: MySQL mendukung native `ENUM`, jadi pola di migration sudah benar:
```php
$table->enum('status', ['kosong', 'hold', 'booked'])->default('kosong');
```
Ini sudah konsisten dipakai di semua migration project ini.

**Row locking `lockForUpdate()`**: berfungsi sama di MySQL (lewat `SELECT ... FOR UPDATE` di balik layar) dan PostgreSQL. Tidak ada penyesuaian khusus dibutuhkan, pola yang sudah didokumentasikan di `references/anti-double-booking.md` langsung berlaku.

## Koneksi Database di `.env`

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=green_deahan_booking
DB_USERNAME=root
DB_PASSWORD=
```

## Saat Menulis Migration Baru

Selalu tulis migration pakai Laravel Schema Builder (`Schema::create`, `$table->...`), JANGAN tulis raw SQL manual di dalam migration kecuali benar-benar tidak ada cara lain lewat Schema Builder. Ini supaya migration tetap portable kalau suatu saat memang perlu pindah database engine, dan supaya konsisten dengan migration yang sudah ada di project ini.
