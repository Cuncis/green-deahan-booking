# Struktur Project

## Folder Penting dan Isinya

```
app/
├── Models/
│   ├── Tenant.php, TenantFitur.php
│   ├── Cabang.php, Lapangan.php, JadwalSlot.php
│   ├── Booking.php, Pembayaran.php
│   ├── Customer.php, Membership.php, KodePromo.php
│   ├── ReminderLog.php, Ulasan.php, Staf.php
│   └── Concerns/
│       └── BelongsToTenant.php       (trait, lihat references/multi-tenant.md)
│
├── Http/
│   ├── Controllers/
│   │   ├── BookingController.php      (hold slot, buat booking)
│   │   └── PembayaranController.php   (webhook Mayar)
│   └── Middleware/
│       └── IdentifikasiTenant.php     (deteksi tenant dari domain)
│
├── Livewire/
│   ├── KalenderBooking.php            (komponen pilih tanggal & slot, real-time)
│   ├── DashboardAdmin.php             (dashboard owner, update live)
│   └── ...                            (komponen interaktif lain ditaruh di sini)
│
├── Console/Commands/
│   └── LepaskanSlotKadaluarsa.php     (scheduled command, jalan tiap menit)
│
└── Jobs/
    └── KirimNotifikasiWhatsApp.php    (TODO, lihat references/notifikasi-whatsapp.md)

resources/
├── views/
│   ├── components/                    (Blade component: button, card, badge, dst)
│   ├── icons/                         (partial SVG, satu file per icon)
│   ├── livewire/                      (view untuk komponen Livewire)
│   └── pages/                         (halaman utama: booking, dashboard, dst)
│
└── css/
    └── app.css                        (entry Tailwind, TANPA import DaisyUI)

database/
└── migrations/                        (ikuti urutan dari skema awal: tenants dulu,
                                         baru cabang, lapangan, jadwal_slot, dst)

routes/
├── web.php                            (halaman customer & dashboard, lewat middleware tenant)
├── api.php                            (kalau ada endpoint API terpisah)
└── console.php                        (jadwal scheduled command)
```

## Konvensi Penamaan

- **Tabel & kolom database:** bahasa Indonesia, snake_case (`jadwal_slot`, `harga_per_jam`, `status_booking`), ini sudah konsisten dari skema awal, jangan campur bahasa Inggris di nama kolom baru
- **Class PHP (Model, Controller):** PascalCase bahasa Indonesia sesuai entitasnya (`Lapangan`, `JadwalSlot`, `BookingController`)
- **Method dan variabel:** camelCase, boleh campur Indonesia-Inggris secukupnya mengikuti konvensi Laravel (`firstOrFail()`, `$tenant`, `$kodeBooking`)
- **Blade component:** kebab-case (`<x-promo-input>`, `<x-icon>`)
- **Route name:** snake_case dengan prefix konteks (`booking.hold_slot`, `dashboard.laporan`)

## Saat Bingung Taruh File Baru di Mana

- Logic yang dipanggil lewat HTTP request biasa (form submit, API call) → `Controllers/`
- Komponen UI yang butuh update real-time tanpa reload halaman (kalender, dashboard live) → `Livewire/`
- Tugas terjadwal (cron, scheduled command) → `Console/Commands/`
- Tugas yang dikirim ke queue/background (kirim notifikasi, proses berat) → `Jobs/`
- Validasi atau aturan bisnis yang dipakai berkali-kali di banyak tempat → pertimbangkan bikin Service class baru di `app/Services/` kalau belum ada foldernya, baru bikin
