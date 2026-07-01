# Notifikasi WhatsApp: Manual Sekarang, API Nanti

## Status Saat Ini

Project ini memakai `wa.me` link manual untuk semua notifikasi WhatsApp. Customer mengklik tombol/link yang membuka WhatsApp dengan pesan yang sudah terisi otomatis, lalu mengirimnya sendiri (atau pesan terkirim ke admin yang harus diteruskan manual ke customer, tergantung alur).

Tidak ada integrasi WhatsApp Business API resmi terpasang saat ini. Jangan asumsikan ada kemampuan kirim pesan WhatsApp otomatis dari server tanpa interaksi user.

## Pola yang Dipakai Sekarang

```php
function buatLinkWhatsApp(string $nomorTujuan, string $pesan): string
{
    $nomorBersih = preg_replace('/[^0-9]/', '', $nomorTujuan);
    return "https://wa.me/{$nomorBersih}?text=" . urlencode($pesan);
}
```

```blade
<a href="{{ buatLinkWhatsApp($tenant->whatsapp_admin, $pesanKonfirmasi) }}"
   target="_blank"
   class="...">
    Buka Chat WhatsApp
</a>
```

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

Saat project sudah siap pakai WhatsApp Business API resmi (lewat provider seperti Twilio, atau API resmi Meta), perubahan yang perlu dilakukan:

1. **Job baru** `App\Jobs\KirimNotifikasiWhatsApp` yang dipanggil lewat queue, bukan link manual yang diklik user
2. **Trigger point** yang sudah disiapkan di kode (lihat `PembayaranController::webhook()` di project sebelumnya) sudah ada baris `dispatch(new KirimNotifikasiWhatsApp(...))`, tinggal job-nya diisi logic API call yang sebenarnya
3. **Reminder otomatis** (fitur Premium) BENAR-BENAR butuh API ini, karena reminder harus terkirim sendiri tanpa user mengklik apapun. Selama masih pakai `wa.me` manual, fitur reminder_otomatis tidak bisa benar-benar otomatis, paling jauh cuma bisa kirim notifikasi internal ke admin untuk follow-up manual.
4. Simpan kredensial API (token, nomor pengirim resmi) di `.env`, jangan hardcode di kode

## Catatan untuk Sekarang

Kalau diminta bikin fitur yang butuh notifikasi WhatsApp "otomatis tanpa user klik apapun" (terutama reminder Premium), kasih tahu user dengan jelas bahwa ini butuh WhatsApp Business API yang belum terpasang, dan tawarkan solusi sementara: tetap pakai `wa.me` link tapi mungkin dikirim manual oleh admin berdasarkan jadwal yang sistem siapkan, sambil menunggu integrasi API resmi.
