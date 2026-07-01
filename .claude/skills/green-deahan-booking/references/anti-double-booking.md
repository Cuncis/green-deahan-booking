# Anti Double-Booking: Pola Wajib

Ini bagian paling kritis di seluruh sistem. Satu kesalahan kecil di sini berarti dua customer bisa dapat slot jam yang sama, yang merusak kepercayaan klien ke produk ini.

## Aturan Inti

**Setiap kode yang membaca lalu mengubah status `jadwal_slot` WAJIB dibungkus `DB::transaction()` dan baris yang dibaca WAJIB pakai `lockForUpdate()`.**

Tanpa ini, dua request yang masuk bersamaan bisa sama-sama lolos pengecekan "slot kosong?" sebelum salah satunya sempat update status, dan keduanya berhasil booking slot yang sama.

## Pola yang Benar

```php
DB::transaction(function () use ($tenant, $slotId) {

    // lockForUpdate() mengunci baris ini. Request lain yang juga mencoba
    // baca baris yang sama akan MENUNGGU sampai transaction ini selesai.
    $slot = JadwalSlot::where('id', $slotId)
        ->where('tenant_id', $tenant->id)
        ->lockForUpdate()
        ->firstOrFail();

    if ($slot->status !== 'kosong') {
        throw ValidationException::withMessages([
            'slot' => 'Slot ini baru saja diambil orang lain.',
        ]);
    }

    $slot->update(['status' => 'hold', 'hold_sampai' => now()->addMinutes(10)]);
});
```

## Pola yang SALAH (Jangan Pernah Ditiru)

```php
// SALAH, ada celah waktu antara cek dan update, dua request bisa lolos bersamaan
$slot = JadwalSlot::find($slotId);
if ($slot->status === 'kosong') {
    $slot->update(['status' => 'hold']); // <- titik rawan race condition
}
```

```php
// SALAH, lockForUpdate() tanpa transaction tidak ada gunanya,
// lock otomatis lepas begitu query selesai
$slot = JadwalSlot::where('id', $slotId)->lockForUpdate()->first();
$slot->update(['status' => 'hold']);
```

## Lapisan Pertahanan Kedua: Database Constraint

Migration tabel `jadwal_slot` punya constraint unique:

```php
$table->unique(['lapangan_id', 'tanggal', 'jam_mulai'], 'unik_slot_lapangan');
```

Ini bukan pengganti `lockForUpdate()`, tapi pengaman tambahan. Kalau suatu saat ada bug yang melewati `lockForUpdate()` (misal developer baru lupa pakai pola di atas), database sendiri akan menolak insert/update yang menghasilkan duplikat kombinasi lapangan+tanggal+jam. JANGAN PERNAH hapus constraint ini meski terasa "merepotkan" saat development.

## Alur Status Slot (Wajib Diikuti, Jangan Loncat Tahap)

```
kosong → hold (10 menit, saat customer pilih jam)
       → booked (PERMANEN, hanya setelah webhook payment gateway konfirmasi sukses)
       → kosong lagi (kalau hold kadaluarsa tanpa pembayaran, lewat scheduled job)
```

**Jangan pernah update slot langsung jadi `booked` di controller booking.** Slot baru boleh jadi `booked` di dalam `PembayaranController::webhook()`, setelah payment gateway konfirmasi sukses. Kalau langsung `booked` saat form submit, slot bisa "tersandera" kalau customer batal bayar.

## Job Wajib: Pelepas Slot Kadaluarsa

Project ini punya scheduled command yang HARUS selalu aktif di production:

```php
// routes/console.php
Schedule::command('booking:lepas-slot-kadaluarsa')->everyMinute();
```

Kalau bikin server baru atau deploy ulang, selalu cek cron Laravel scheduler benar-benar jalan (`php artisan schedule:run` terpasang di crontab server). Tanpa ini, slot yang di-hold tapi tidak jadi dibayar akan terus terkunci selamanya dan lapangan kelihatan "penuh" padahal sebenarnya kosong.

## Saat Menambah Fitur Baru yang Menyentuh Slot

Pertanyaan yang harus dijawab sebelum menulis kode apapun yang menyentuh `jadwal_slot`:

1. Apakah operasi ini mengubah status slot? Kalau ya, wajib `DB::transaction()` + `lockForUpdate()`.
2. Apakah ada kemungkinan dua request masuk bersamaan untuk slot yang sama? (Hampir selalu "ya" untuk apapun yang berhubungan booking customer-facing.)
3. Kalau operasi gagal di tengah jalan, apakah slot bisa "tersandera" di status yang salah? Pastikan ada exception handling yang melepas slot kembali kalau perlu.
