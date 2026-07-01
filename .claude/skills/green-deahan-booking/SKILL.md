---
name: green-deahan-booking
description: Konvensi dan aturan wajib untuk project Green Deahan Sport, sistem booking lapangan olahraga multi-tenant berbasis Laravel (Blade + Livewire + Alpine.js, MySQL). WAJIB dipakai untuk SEMUA pekerjaan di project ini seperti bikin halaman baru, tambah fitur, bikin migration, controller, model, component, styling, atau apapun yang menyentuh codebase Green Deahan Sport. Termasuk aturan multi-tenant (tenant_id), anti double-booking (lockForUpdate), preset fitur Basic/Pro/Premium, notifikasi WhatsApp, design token brand, larangan DaisyUI, aturan SVG icon, dan larangan tanda em dash di teks. Trigger skill ini bahkan kalau user cuma bilang "tambah fitur X" atau "perbaiki halaman Y" tanpa menyebut nama project, kalau konteksnya soal lapangan, booking, tenant, atau Green Deahan, ini skill yang relevan.
---

# Green Deahan Sport: Booking System

Project Laravel multi-tenant untuk bisnis booking lapangan olahraga (futsal, padel, badminton, tennis, mini soccer). Satu codebase melayani banyak klien (tenant) dengan 3 tingkat paket: Basic, Pro, Premium.

Skill ini adalah index. Baca reference file yang relevan sebelum mengerjakan tugas. Jangan asal pakai pengetahuan umum Laravel kalau project ini punya konvensi spesifik yang beda.

## Stack Resmi (Jangan Menyimpang)

- **Backend:** Laravel (PHP), MySQL
- **Frontend:** Blade + **Livewire** (untuk komponen interaktif: kalender, slot booking, dashboard real-time) + **Alpine.js** (untuk interaksi kecil tanpa round-trip server: toggle, modal, dropdown)
- **Styling:** **Tailwind CSS murni**. TIDAK PAKAI DaisyUI, Bootstrap, atau UI kit manapun. Semua komponen dibangun custom dari design token brand (lihat `references/design-tokens.md`)
- **Icon:** SVG inline atau Blade component, TIDAK PERNAH emoji mentah di kode produksi
- **Notifikasi:** WhatsApp via `wa.me` link manual untuk sekarang (lihat `references/notifikasi-whatsapp.md` untuk rencana migrasi ke WhatsApp Business API)

## Aturan Mutlak: Baca Sebelum Menulis Kode Apapun

1. **Setiap query database yang menyentuh data milik klien WAJIB difilter `tenant_id`.** Tidak ada pengecualian. Lihat `references/multi-tenant.md`.
2. **Setiap operasi yang mengubah status `jadwal_slot` WAJIB pakai `DB::transaction()` + `lockForUpdate()`.** Ini mencegah double-booking. Lihat `references/anti-double-booking.md`.
3. **Tidak ada DaisyUI/Bootstrap/UI kit lain.** Tailwind murni + komponen Blade custom dari `references/design-tokens.md`.
4. **Tidak ada emoji mentah di kode produksi (Blade view, komponen).** Ganti dengan SVG. Lihat `references/icon-rules.md`.
5. **Tidak ada tanda strip panjang di teks apapun**, baik di UI, komentar kode yang user-facing, maupun dokumentasi yang akan dibaca user. Ganti dengan koma, titik, atau kata sambung natural. Lihat `references/wording-rules.md`.
6. **Fitur yang ditampilkan ke user HARUS dicek dulu lewat `$tenant->punyaFitur('nama_fitur')`** sebelum dirender. Jangan hardcode fitur tampil untuk semua tenant. Lihat `references/fitur-per-paket.md`.

## Peta Reference Files

Baca file yang relevan dengan tugas yang sedang dikerjakan:

| File | Kapan Dibaca |
|---|---|
| `references/multi-tenant.md` | Bikin query, controller, atau apapun yang ambil/simpan data tenant |
| `references/anti-double-booking.md` | Menyentuh logic booking, slot, hold, atau pembayaran |
| `references/fitur-per-paket.md` | Menentukan fitur apa yang tampil untuk Basic/Pro/Premium |
| `references/design-tokens.md` | Bikin halaman, komponen Blade, atau styling apapun |
| `references/icon-rules.md` | Ada kebutuhan icon di UI |
| `references/wording-rules.md` | Menulis teks UI, copy, atau dokumentasi |
| `references/notifikasi-whatsapp.md` | Bikin fitur notifikasi/konfirmasi ke customer |
| `references/struktur-project.md` | Bikin file baru, bingung taruh di folder mana |
| `references/database-mysql.md` | Bikin migration atau query, termasuk catatan penyesuaian dari skema awal yang sempat dirancang pakai PostgreSQL |

## Alur Kerja Singkat Saat Diminta Bikin Fitur Baru

1. Cek apakah fitur ini milik paket tertentu (Basic/Pro/Premium) → baca `references/fitur-per-paket.md`
2. Kalau menyentuh booking/slot → baca `references/anti-double-booking.md` dulu sebelum nulis controller
3. Bikin migration kalau perlu tabel baru → ikuti pola di `references/database-mysql.md`, jangan lupa kolom `tenant_id`
4. Bikin Livewire component untuk bagian interaktif, Blade biasa untuk bagian statis
5. Styling pakai token dari `references/design-tokens.md`, jangan reka warna/spacing sendiri
6. Icon pakai SVG dari `references/icon-rules.md`, bukan emoji
7. Sebelum selesai, cek teks yang ditulis tidak memakai tanda strip panjang
