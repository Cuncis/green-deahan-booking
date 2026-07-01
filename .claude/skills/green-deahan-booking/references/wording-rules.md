# Aturan Wording: Tanpa Tanda Em Dash (—)

## Aturan Inti

**Tidak ada tanda strip panjang (biasa disebut em dash) di teks apapun yang ditulis untuk project ini.** Berlaku di UI (Blade view, copy tombol, label), dokumentasi, pesan WhatsApp, maupun komentar kode yang sifatnya user-facing (misal pesan error yang ditampilkan ke customer).

Alasan: tanda em dash terasa seperti gaya tulisan AI generatif yang khas, dan project ini sengaja menghindari kesan itu di semua materi (sudah diterapkan konsisten di PDF panduan dan demo HTML sebelumnya).

## Cara Mengganti

Tidak ada satu pengganti universal, tergantung konteks kalimatnya:

| Konteks Kalimat | Ganti Em Dash Dengan |
|---|---|
| Memisah dua klausa terkait | Koma (`,`) |
| Judul bab/section dengan angka | Titik dua (`:`). Contoh: judul yang tadinya pakai strip panjang jadi "Bab 3: Ukuran Lapangan" |
| Penjelasan tambahan di akhir kalimat | Titik (`.`) lalu kalimat baru, atau kata sambung seperti "yaitu", "yakni" |
| Daftar/rentang angka | Kata "sampai", bukan tanda hubung apapun. Contoh: "08.00 sampai 24.00" untuk kalimat, atau hyphen biasa "08.00-24.00" kalau di tabel yang butuh ringkas |

## Contoh Sebelum/Sesudah

```
SALAH: "Sistem booking ini — yang sudah dipakai sejak 2010 — terbukti efektif."
BENAR: "Sistem booking ini, yang sudah dipakai sejak 2010, terbukti efektif."

SALAH: "Pilih jam — lalu konfirmasi pembayaran."
BENAR: "Pilih jam, lalu konfirmasi pembayaran."

SALAH: "Booking Berhasil — Konfirmasi sudah dikirim ke WhatsApp."
BENAR: "Booking Berhasil. Konfirmasi sudah dikirim ke WhatsApp."

SALAH: "Jam buka 08.00 — 24.00 WIB"
BENAR: "Jam buka 08.00 sampai 24.00 WIB" (atau di tabel ringkas: "08.00-24.00", pakai hyphen biasa bukan em dash)
```

## Beda Strip Panjang vs Hyphen Biasa

Yang dilarang adalah tanda strip panjang (em dash) dan strip menengah (en dash). Hyphen biasa yang pendek masih boleh dipakai untuk:
- Rentang angka di tabel yang butuh ringkas: `08.00-24.00`, `Rp 100rb-200rb`
- Kode booking: `GDS-482910`
- Nomor telepon: `0813-5757-0064`
- Nama compound yang memang pakai hyphen: bukan kasus umum di project ini

Intinya: hyphen pendek untuk data terstruktur (kode, nomor, rentang angka) boleh. Strip panjang untuk gaya kalimat atau narasi, tidak boleh sama sekali.

## Saat Menulis Teks Baru (UI, Dokumentasi, Pesan)

Sebelum submit teks apapun yang ditulis untuk project ini, cek ulang apakah ada karakter strip panjang di dalamnya. Kalau ada, tulis ulang kalimatnya pakai koma, titik, atau kata sambung natural seperti yang dicontohkan di atas.
