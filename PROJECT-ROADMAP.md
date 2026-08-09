# Green Deahan Sport, Roadmap dan Cek Status Project

Dokumen ini dibuat dari isi codebase (riwayat git, README, catatan konvensi project) supaya kamu bisa cek apakah arah pembangunan sejauh ini sudah sesuai dengan tujuan akhir kamu. Ditulis sesederhana mungkin, tanpa terlalu banyak istilah teknis.

## 1. Sebenarnya Project Ini Apa

Ini adalah **platform booking online untuk banyak tempat olahraga sekaligus** (futsal, padel, badminton, tennis, mini soccer). Satu sistem yang sama dipakai bareng-bareng oleh banyak klien (disebut "tenant" di kode), masing-masing punya alamat website sendiri (subdomain atau domain custom), dan bisa pilih salah satu dari tiga paket langganan: **Basic**, **Pro**, **Premium**.

Ada dua jenis pengunjung yang dilayani sistem yang sama ini:
- **greendeahan.com**, situs resmi Green Deahan sendiri (halaman harga, blog, galeri), dipakai untuk jualan produk booking online ini ke calon klien.
- **Website tiap tenant** (contoh: arenabaru.greendeahan.com atau domain custom milik klien), tempat pelanggan tenant itu pilih lapangan, jam main, dan bayar online.

## 2. Perjalanan Pembangunan Sejauh Ini

Disusun dari riwayat commit, dari yang paling awal:

| Tahap | Yang dikerjakan |
|---|---|
| **1. Fondasi** | Setup awal Laravel, struktur database dan model dasar |
| **2. Tampilan awal** | Desain visual, warna, komponen halaman booking |
| **3. Dashboard admin tenant** | Halaman untuk pemilik lapangan kelola booking, lapangan, jadwal, pengaturan |
| **4. Gerbang pembayaran (versi pertama)** | Booking dan pembayaran mulai jalan (awalnya pakai Midtrans dan draft Xendit) |
| **5. Alur booking** | Konfirmasi/batalkan booking, cegah dua orang booking jam yang sama |
| **6. Panel platform (superadmin)** | Dukungan domain custom, dashboard khusus buat kamu selaku pemilik platform (beda dari dashboard pemilik tenant) |
| **7. Pendaftaran tenant baru** | Halaman `/daftar` untuk calon klien daftar sendiri, data contoh untuk paket Basic/Pro/Premium |
| **8. Perbaikan dan pembersihan** | Perbaikan login dan berbagai bug lain |
| **9. Pindahan situs korporat** | Konten situs greendeahan.com lama (dari Nuxt) dipindah masuk ke sistem ini |
| **10. Penyempurnaan pembayaran** | Opsi transfer manual dihapus total, lalu Midtrans/Xendit **diganti sepenuhnya jadi Mayar** |
| **11. Perbaikan tombol WhatsApp** | Klik tombol WhatsApp jadi lebih rapi (langsung buka WhatsApp Web di komputer, langsung buka app di HP) |
| **12. Tagihan tenant otomatis** | Pendaftaran tenant baru sekarang otomatis buat tagihan Mayar dan langsung diarahkan ke halaman bayar, begitu dibayar akun tenant otomatis aktif dan email undangan buat ownernya otomatis terkirim, tidak perlu lagi kamu aktifkan manual satu-satu |

**Kondisi saat ini:** pembayaran booking pelanggan maupun tagihan langganan tenant sama-sama sudah lewat Mayar dari ujung ke ujung, hasil pengujian otomatis 349 dari 351 lolos (2 yang gagal sudah ada dari sebelumnya, tidak berhubungan sama sekali dengan pekerjaan sesi ini).

## 3. Fitur di Tiap Paket

| Fitur | Basic | Pro | Premium |
|---|:---:|:---:|:---:|
| Booking online | Ya | Ya | Ya |
| Notifikasi WhatsApp (klik manual) | Ya | Ya | Ya |
| Pembayaran online (Mayar: QRIS/VA/e-wallet) | Ya | Ya | Ya |
| Opsi bayar DP 50% | Tidak | Ya | Ya |
| Kode promo | Tidak | Ya | Ya |
| Booking rutin mingguan | Tidak | Ya | Ya |
| Rating dan ulasan | Tidak | Ya | Ya |
| Laporan pendapatan | Tidak | Ya | Ya |
| Banyak cabang | Tidak | Tidak | Ya |
| Membership (Bronze/Silver/Gold) | Tidak | Tidak | Ya |
| Reminder otomatis | Tidak | Tidak | Ya (setengah otomatis, lihat bagian 4) |
| Role staf (owner/manager/staff) | Tidak | Tidak | Ya |
| Analitik lanjutan antar cabang | Tidak | Tidak | Ya |
| Batas jumlah lapangan | 1 | 3 | tanpa batas |

## 4. Mana yang Sudah Otomatis, Mana yang Masih Manual

**Keputusan yang sudah disepakati (9 Agustus 2026):** WhatsApp memang sengaja tetap manual (klik link `wa.me`), bukan sesuatu yang harus dibetulkan. Selain itu, semua langkah yang masih perlu campur tangan manusia jadi target untuk dihilangkan.

**Sudah otomatis penuh:**
- Pelanggan booking lapangan, bayar lewat Mayar, sistem langsung konfirmasi booking-nya dan kunci jamnya. Tidak ada campur tangan manusia sama sekali.
- Calon klien daftar tenant baru, bayar lewat Mayar (harga sesuai paket, tanpa nego), sistem otomatis aktifkan akunnya, perpanjang masa aktif, dan kirim email undangan ke ownernya. Command `tenant:activate` masih ada buat kondisi khusus (akun gratis/diskon, atau perpanjangan, lihat bagian di bawah).
- Slot yang sudah "dikunci sementara" tapi tidak jadi dibayar otomatis dilepas lagi tiap menit.
- Fitur yang tampil ke tenant selalu sesuai paketnya, tidak ada yang bocor ke paket yang tidak berhak.

**Sengaja tetap manual (bukan masalah, ini pilihan):**
- **Notifikasi WhatsApp.** Tetap pakai link `wa.me` yang harus diklik manual, bukan WhatsApp Business API resmi. Ini pernah dicoba dipasang tapi dibatalkan karena proses verifikasi dari Meta bisa makan waktu lama dan tidak pasti. Langkah pemasangan ulangnya sudah didokumentasikan kalau suatu saat mau dicoba lagi.
- **Reminder otomatis Premium, jadi setengah otomatis.** Karena WhatsApp resmi belum dipasang, reminder tidak bisa benar-benar terkirim sendiri. Rencananya: sistem tetap otomatis catat kapan reminder harus dikirim, lalu tampilkan daftar "reminder yang harus dikirim sekarang" di dashboard tenant supaya staf tinggal klik sekali kirim, tidak perlu ingat-ingat sendiri jadwalnya. Lihat bagian 5.

**Masih manual, jadi target berikutnya untuk dibikin otomatis:**
- **Tagihan perpanjangan tahunan.** Pendaftaran pertama kali sekarang sudah otomatis (lihat di atas), tapi perpanjangan tahun berikutnya masih harus dijalankan manual lewat command. Belum ada sistem yang otomatis deteksi tenant yang masa aktifnya mau habis lalu kirim tagihan sendiri.
- **Pemasangan domain custom.** Biaya tambahan domain custom sekarang sudah otomatis tertagih saat daftar, tapi proses pasang DNS dan sertifikat keamanannya (SSL) tetap harus dikerjakan manual oleh tim, karena verifikasi kepemilikan domain memang butuh pengecekan manusia.
- **Pembuatan akun superadmin.** Belum ada cara otomatis untuk bikin akun superadmin baru, masih harus diubah manual langsung di database.

## 5. Saran dan Langkah Selanjutnya

Diurutkan dari yang paling penting:

1. ~~Otomatiskan tagihan dan aktivasi tenant baru.~~ **Sudah selesai (sesi ini).** Pendaftaran tenant sekarang otomatis buat tagihan Mayar dan aktif sendiri begitu dibayar.
2. **Otomatiskan tagihan perpanjangan tahunan.** Ini lanjutan alami dari poin 1, sekarang alur pendaftaran awal sudah terbukti jalan. Sistem perlu bisa deteksi tenant yang mau habis masa aktifnya, kirim tagihan perpanjangan sendiri, lalu perpanjang otomatis begitu dibayar.
3. **Bikin cara mudah bikin akun superadmin.** Kecil dan aman dikerjakan, supaya server baru tidak perlu diutak-atik database manual.
4. **Widget "reminder yang harus dikirim" di dashboard tenant.** Cara murah bikin fitur reminder Premium jadi benar-benar berguna tanpa perlu pasang WhatsApp API dulu.
5. **Cek ulang tabel harga dan fitur di bagian 3, apakah masih sesuai dengan yang benar-benar dijual sekarang.** Ini bukan pekerjaan coding, cukup dicek lima menit. Perlu diperhatikan, harga di tabel itu sekarang JUGA yang dipakai sistem untuk menagih otomatis, jadi kalau angkanya beda dari yang sebenarnya dijual, bukan cuma salah tampilan, tapi bisa salah tagih ke calon klien.
6. **Fitur lain yang mungkin sudah direncanakan tapi belum ada jejaknya di sistem** (refund, ekspor data tenant, mata uang lain, aplikasi mobile, dan sejenisnya). Kalau ada rencana seperti itu, sebutkan saja supaya bisa ikut dicatat di sini.

Bilang saja kalau mau mulai kerjakan poin 2 (tagihan perpanjangan otomatis), nanti langsung dibuatkan rencana kerjanya.
