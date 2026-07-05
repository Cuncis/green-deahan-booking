# Notifikasi WhatsApp: Manual Lewat wa.me (Bukan API Resmi)

## Status Saat Ini

Project ini memakai `wa.me` link manual untuk semua notifikasi WhatsApp, BUKAN WhatsApp Business API resmi. Pernah dicoba pasang `kstmostofa/laravel-whatsapp` (Cloud API resmi Meta) tapi diputuskan untuk di-revert dulu, karena butuh Meta Business verification + approval template yang punya waktu tunggu tidak pasti, sementara alur manual sudah cukup jalan. Jangan asumsikan ada kemampuan kirim pesan WhatsApp otomatis dari server tanpa interaksi user.

`App\Jobs\KirimNotifikasiWhatsApp` cuma mencatat log (`Log::info`), tidak benar-benar mengirim apa pun. Ini sengaja jadi titik pemasangan API resmi nanti kalau/waktu dibutuhkan lagi (lihat bagian TODO di bawah).

## Pola yang Dipakai Sekarang

Semua link `wa.me` di-generate inline di Blade pakai `preg_replace('/[^0-9]/', '', $nomor)`, bukan lewat helper terpusat.

```blade
<a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $tenant->whatsapp_admin) }}"
   onclick="bukaChatWhatsApp(this.href); return false;"
   class="...">
    Buka Chat WhatsApp
</a>
```

Klik tombol tetap butuh langkah manual pengguna (WhatsApp/wa.me tidak bisa auto-kirim tanpa itu), tapi UX-nya sudah ditingkatkan lewat `window.bukaChatWhatsApp()`:

- **Desktop**: buka popup kecil (420x680) yang diarahkan ke `web.whatsapp.com/send?phone=...&text=...` (bukan `wa.me` langsung), supaya langsung masuk WhatsApp Web tanpa halaman pilihan "Open app / Continue to WhatsApp Web" dari Meta.
- **Mobile/tablet** (dideteksi lewat `navigator.userAgent`): navigasi di tab yang sama ke link `wa.me` asli, supaya OS yang tangani app link dan langsung buka app WhatsApp.

Fungsi ini didefinisikan di `resources/views/components/wa-popup-script.blade.php`, di-include lewat `<x-wa-popup-script />` di halaman yang punya tombol wa.me dan dirender lewat Livewire (`pages/booking.blade.php`, `pages/admin/bookings/index.blade.php`, `pages/admin/dashboard.blade.php`). Sengaja BUKAN lewat `resources/js/app.js` karena halaman-halaman itu memakai Alpine bawaan Livewire (`@livewireScripts`), tidak memuat bundle Vite JS terpisah, jadi fungsi di `app.js` tidak akan pernah terpanggil di sana.

Pesan yang di-generate harus tetap mengikuti aturan wording di `references/wording-rules.md` (tidak ada em dash), meski ini konteks chat dan emoji masih boleh dipakai sewajarnya di sini.

## Contoh Pesan Konfirmasi Booking (Format yang Dipakai)

```php
$pesan = "Halo {$nama}, booking kamu di {$tenant->nama_bisnis} sudah dikonfirmasi.\n\n"
    . "Kode: {$kodeBooking}\n"
    . "Lapangan: {$namaLapangan}\n"
    . "Tanggal: {$tanggal}\n"
    . "Jam: {$jam}\n"
    . "Total: {$totalBayar}\n\n"
    . "Sampai jumpa di lapangan!";
```

## TODO: Migrasi ke WhatsApp Business API

Kalau nanti project butuh notifikasi WhatsApp benar-benar otomatis (terutama untuk reminder Premium, lihat di bawah), berikut yang perlu dilakukan. Ini PERNAH dibangun dengan package `kstmostofa/laravel-whatsapp` (Cloud API resmi Meta) lalu di-revert, jadi urutan kerjanya sudah diketahui:

1. `composer require kstmostofa/laravel-whatsapp`, publish config + migration, isi `WHATSAPP_ACCESS_TOKEN`/`WHATSAPP_PHONE_NUMBER_ID`/`WHATSAPP_BUSINESS_ACCOUNT_ID` di `.env` dari Meta Business Manager. Satu nomor untuk SELURUH platform (bukan per tenant), package ini bind `CloudClient` sebagai singleton dengan kredensial statis, tidak ada dukungan multi-akun bawaan.
2. Isi `App\Jobs\KirimNotifikasiWhatsApp::handle()` dengan `WhatsApp::messages()->sendTemplate($nomorE164, $namaTemplate, 'id', $components)`, tangkap `Throwable` dan `Log::error()` tanpa di-throw ulang (jangan sampai gagal kirim WA menggagalkan alur booking, apalagi kalau job ini kebetulan jalan sinkron seperti di `phpunit.xml` yang set `QUEUE_CONNECTION=sync`).
3. Business-initiated message di luar 24 jam WAJIB pakai template yang disetujui dulu di Meta Business Manager, tidak bisa teks bebas. Minimal butuh template untuk: konfirmasi booking, pembatalan booking, dan notifikasi booking baru ke owner tenant.
4. Matikan `WHATSAPP_WEB_ENABLED` (sidecar `whatsapp-web.js`, backend tidak resmi) dan `WHATSAPP_UI_ENABLED` (admin UI bawaan package di `/whatsapp` tidak ada auth bawaan) kecuali memang mau dipakai dan sudah dibungkus middleware auth sendiri.
5. Trigger point yang sudah ada: `PembayaranController::konfirmasiBooking()`/`batalkanBooking()`, `BookingAdminController::confirm()`/`cancel()` (dispatch `KirimNotifikasiWhatsApp`), dan `BookingController::buatBooking()` (titik yang pas untuk notifikasi owner soal booking baru, kalau mau ditambahkan lagi).

## Reminder Otomatis (Fitur Premium)

`ReminderLog` sudah dibuat (di `PembayaranController` dan `BookingAdminController::confirm()`) berisi jadwal kirim (`waktu_kirim`) dan pesan, tapi BELUM ada scheduled command yang membaca `ReminderLog` dan mengirimkannya. Fitur reminder_otomatis BENAR-BENAR butuh WhatsApp Business API resmi, karena reminder harus terkirim sendiri tanpa user mengklik apapun. Selama masih pakai `wa.me` manual, fitur ini tidak bisa benar-benar otomatis, paling jauh cuma bisa kirim notifikasi internal ke admin untuk follow-up manual.

## Catatan untuk Sekarang

Kalau diminta bikin fitur yang butuh notifikasi WhatsApp "otomatis tanpa user klik apapun" (terutama reminder Premium), kasih tahu user dengan jelas bahwa ini butuh WhatsApp Business API yang belum terpasang, dan tawarkan solusi sementara: tetap pakai `wa.me` link (dengan UX popup/direct-app yang sudah ada) tapi mungkin dikirim manual oleh admin berdasarkan jadwal yang sistem siapkan, sambil menunggu integrasi API resmi.
