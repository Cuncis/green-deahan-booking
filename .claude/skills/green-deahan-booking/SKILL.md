---
name: green-deahan-booking
description: Konvensi dan aturan wajib untuk project Green Deahan Sport, sistem booking lapangan olahraga multi-tenant berbasis Laravel (React + Inertia, Blade + Alpine.js, MySQL). WAJIB dipakai untuk SEMUA pekerjaan di project ini seperti bikin halaman baru, tambah fitur, bikin migration, controller, model, component, styling, atau apapun yang menyentuh codebase Green Deahan Sport. Termasuk aturan multi-tenant (tenant_id), anti double-booking (lockForUpdate), preset fitur Basic/Pro/Premium, notifikasi WhatsApp, design token brand, larangan DaisyUI, aturan SVG icon, dan larangan tanda em dash di teks. Trigger skill ini bahkan kalau user cuma bilang "tambah fitur X" atau "perbaiki halaman Y" tanpa menyebut nama project, kalau konteksnya soal lapangan, booking, tenant, atau Green Deahan, ini skill yang relevan.
---

# Green Deahan Sport: Booking System

Project Laravel multi-tenant untuk bisnis booking lapangan olahraga (futsal, padel, badminton, tennis, mini soccer). Satu codebase melayani banyak klien (tenant) dengan 3 tingkat paket: Basic, Pro, Premium.

Skill ini adalah index. Baca reference file yang relevan sebelum mengerjakan tugas. Jangan asal pakai pengetahuan umum Laravel kalau project ini punya konvensi spesifik yang beda.

## Stack Resmi (Jangan Menyimpang)

- **Backend:** Laravel (PHP), MySQL
- **Frontend:** **React + TypeScript lewat Inertia** untuk halaman interaktif (admin, superadmin, halaman booking customer, halaman publik baru) + Blade + **Alpine.js** untuk halaman Blade yang tersisa (toggle, modal, dropdown). **Livewire sudah dihapus dari project, jangan dipasang lagi.**
- **Styling:** **Tailwind CSS murni**. TIDAK PAKAI DaisyUI, Bootstrap, atau UI kit manapun. Semua komponen dibangun custom dari design token brand (lihat `references/design-tokens.md`)
- **Icon:** SVG inline atau Blade component, TIDAK PERNAH emoji mentah di kode produksi
- **Notifikasi:** WhatsApp via `wa.me` link manual untuk sekarang (lihat `references/notifikasi-whatsapp.md` untuk rencana migrasi ke WhatsApp Business API)

### Halaman Interaktif = React + TypeScript (Inertia)

Dashboard admin tenant (`/admin`), superadmin (`/superadmin`), dan halaman booking customer memakai **React + TypeScript lewat Inertia**, dengan komponen **shadcn/ui** (Radix UI, lucide-react, sonner) yang diwarnai pakai token admin di `tailwind.config.js` (`primary` = green, `background` = cream, dst). Halaman booking memakai komponen yang sama dan warna utama tenant lewat variabel CSS `--aksen`.

- Kode: `resources/js/admin/` (entry `app.tsx`, halaman di `pages/admin/*` dan `pages/superadmin/*`, komponen shadcn di `components/ui/`, layout di `components/layout/AdminLayout.tsx`). Root view `resources/views/admin.blade.php`, dipilih di `HandleInertiaRequests::rootView()`.
- Controller me-return `Inertia::render('admin/Nama', [...])`. Aturan bisnis TETAP di server: filter `tenant_id`, `lockForUpdate()`, dan `punyaFitur()` (data panel fitur berbayar dikirim `null` kalau tenant tidak punya fiturnya, jangan dikirim lalu disembunyikan di React).
- Menu sidebar dibangun di `App\Services\NavigasiAdminService`. Halaman yang sudah React ditandai `inertia: true` di sana; halaman yang masih Blade dibuka dengan reload biasa.
- Status migrasi: seluruh halaman admin tenant (Dashboard, Booking, Lapangan, Jadwal, Laporan, Promo, Pengaturan) dan superadmin (Dashboard, Tenant, Galeri, Blog, Media, SEO) sudah React. Yang masih Blade hanya Profil (`/profile`, pakai `<x-admin-sidebar>`).
- Halaman booking customer di `/` domain tenant: React (`resources/js/booking/`, root view `booking.blade.php`, entry terpisah dari admin). Semua aksi lewat API `routes/api.php` (BookingController), aturan slot di `SlotBookingService`, harga di `HargaBookingService`.
- Jangan pakai nama prop halaman yang sama dengan shared prop (`auth`, `area`, `tenant`, `navigasi`, `flash`, `errors`), karena prop halaman menimpa shared prop dan merusak sidebar.
- Aksi yang dulu method Livewire sekarang route HTTP biasa yang `return back()->with('success'|'error', ...)`; layout menampilkan flash itu sebagai toast. Form dengan upload file kirim `POST` + `_method: 'put'` + `forceFormData`.
- Mobile wajib: tabel jadi kartu di layar kecil, target sentuh minimal 44px, form muat di layar HP.
- Test pakai `assertInertia()`, bukan `Livewire::test()`.

## Aturan Mutlak: Baca Sebelum Menulis Kode Apapun

1. **Setiap query database yang menyentuh data milik klien WAJIB difilter `tenant_id`.** Tidak ada pengecualian. Lihat `references/multi-tenant.md`.
2. **Setiap operasi yang mengubah status `jadwal_slot` WAJIB pakai `DB::transaction()` + `lockForUpdate()`.** Ini mencegah double-booking. Lihat `references/anti-double-booking.md`.
   - Hold slot customer lewat `App\Services\SlotBookingService` saja. Hold memberi `hold_token` acak; booking dan pelepasan hold wajib menyertakan token itu, dan token dihapus begitu booking dibuat (slot tetap `hold` sampai pembayaran dikonfirmasi). Setiap kode yang mengubah status slot ke `kosong`/`booked` juga mengosongkan `hold_token`. Kolom ini `#[Hidden]`, jangan pernah dikirim di daftar slot.
   - Harga booking customer (diskon member, kode promo + kuota, DP) hanya dihitung di `App\Services\HargaBookingService::hitung()`, untuk ringkasan maupun saat booking dibuat, supaya total yang tampil sama dengan yang ditagih.
   - "Sudah lewat" dan "hari ini" untuk slot dihitung di zona `Asia/Jakarta` (`SlotBookingService::ZONA_WAKTU`), bukan UTC aplikasi.
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

### Toko Online (dropship, situs korporat)

- Pelanggan: `/toko`, `/toko/{slug}`, `/keranjang`, `/checkout`, `/pesanan/{code}` di domain greendeahan.com (closure `$situsKorporat`), React di `resources/js/pages/toko/`, gaya di `resources/fuzio/css/gd-toko.css`. Admin: `/superadmin/toko/*`.
- Tabel `suppliers`, `products`, `orders`, `order_items`, `mayar_webhook_logs` (nama Inggris sesuai spesifikasi pemilik; bukan fitur tenant, tanpa `tenant_id`). Status pesanan: enum `App\Enums\OrderStatus`.
- `cost_price`, `supplier_id`, `supplier_product_url` PRIVAT: halaman pelanggan hanya menerima `Product::dataPublik()`. Harga, modal, dan total SELALU dihitung ulang di server (`KeranjangService`, `CheckoutController`); keranjang di session hanya berisi id dan qty.
- Katalog: kategori di `Product::KATEGORI` (material lapangan + `olahraga`), satuan di `Product::SATUAN` (harga tampil "/m²", "/sak", dst), `badge` dan `compare_price` (harga coret, hanya dikirim kalau lebih besar dari harga jual). `/toko` memfilter, mencari, mengurutkan, dan mem-paginate di server (`TokoController@index`, 9 per halaman). Produk tanpa harga riil dibuat `is_active=false`.
- `TOKO_PUBLIK=false`: menu Toko disembunyikan dan halaman `noindex`, tapi path tetap bisa dibuka untuk uji internal.
- Mayar: `config/mayar.php` (sumber tunggal untuk booking dan toko), `MayarService` (API v2 payment request) menolak server produksi selama `MAYAR_ENV` bukan production. Satu URL webhook per akun Mayar: webhook toko masuk lewat `PembayaranController` lalu `WebhookTokoService`; job `ProsesPembayaranToko` cek ulang status dan nominal ke API Mayar sebelum menandai dibayar, idempotent.

## Alur Kerja Singkat Saat Diminta Bikin Fitur Baru

1. Cek apakah fitur ini milik paket tertentu (Basic/Pro/Premium) → baca `references/fitur-per-paket.md`
2. Kalau menyentuh booking/slot → baca `references/anti-double-booking.md` dulu sebelum nulis controller
3. Bikin migration kalau perlu tabel baru → ikuti pola di `references/database-mysql.md`, jangan lupa kolom `tenant_id`
4. Bagian interaktif pakai halaman React (Inertia) + route/API Laravel; aturan bisnis tetap di server
5. Styling pakai token dari `references/design-tokens.md`, jangan reka warna/spacing sendiri
6. Icon pakai SVG dari `references/icon-rules.md`, bukan emoji
7. Sebelum selesai, cek teks yang ditulis tidak memakai tanda strip panjang
