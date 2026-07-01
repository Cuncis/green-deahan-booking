# Fitur per Paket: Basic / Pro / Premium

Satu codebase melayani tiga tingkat paket. Fitur mana yang aktif untuk satu tenant disimpan di tabel `tenant_fitur`, BUKAN di-hardcode di kode.

## Aturan Inti

**Sebelum render fitur apapun yang bukan fitur dasar (booking online, notifikasi WA), selalu cek dulu lewat `$tenant->punyaFitur('nama_fitur')`.**

```php
// di Controller atau Livewire component
$tenant = app('tenant');

if ($tenant->punyaFitur('pembayaran_online')) {
    // tampilkan pilihan metode bayar QRIS/VA/E-wallet
} else {
    // tampilkan instruksi transfer manual
}
```

```blade
{{-- di Blade view --}}
@if(app('tenant')->punyaFitur('kode_promo'))
    <x-promo-input />
@endif
```

## Daftar Fitur dan Paket Pemiliknya

| Kolom di `tenant_fitur` | Basic | Pro | Premium | Keterangan |
|---|---|---|---|---|
| `booking_online` | ✓ | ✓ | ✓ | Fitur dasar, selalu true |
| `notifikasi_whatsapp` | ✓ | ✓ | ✓ | Fitur dasar, selalu true |
| `pembayaran_online` | tidak | ya | ya | QRIS/E-wallet/VA via Midtrans/Xendit |
| `dp_pembayaran` | tidak | ya | ya | Pilihan bayar DP 50% atau lunas |
| `kode_promo` | tidak | ya | ya | Kode diskon |
| `booking_berulang` | tidak | ya | ya | Booking rutin mingguan |
| `rating_ulasan` | tidak | ya | ya | Review dari customer |
| `laporan_pendapatan` | tidak | ya | ya | Dashboard laporan harian/mingguan |
| `multi_cabang` | tidak | tidak | ya | Banyak cabang dalam satu dashboard |
| `sistem_membership` | tidak | tidak | ya | Tier Bronze/Silver/Gold |
| `reminder_otomatis` | tidak | tidak | ya | Reminder WA 2 jam sebelum main |
| `manajemen_staf` | tidak | tidak | ya | Role owner/manager/staff |
| `analitik_lanjutan` | tidak | tidak | ya | Perbandingan performa antar cabang |
| `batas_lapangan` | 1 | 3 | NULL (unlimited) | Bukan boolean, ini angka |

## Preset Saat Tenant Baru Daftar

Jangan isi kolom `tenant_fitur` manual satu-satu. Pakai method preset yang sudah ada di model:

```php
use App\Models\TenantFitur;

$preset = TenantFitur::presetUntukPaket('pro'); // 'basic' | 'pro' | 'premium'

TenantFitur::create(array_merge(['tenant_id' => $tenant->id], $preset));
```

Kalau ada fitur baru yang perlu ditambahkan ke sistem, update method `presetUntukPaket()` di `app/Models/TenantFitur.php` SEKALIGUS untuk ketiga paket. Jangan cuma tambah di satu tempat lalu lupa update preset lainnya.

## Validasi `batas_lapangan`

Ini satu-satunya kolom yang bukan boolean. Saat tenant mau tambah lapangan baru, validasi dulu:

```php
$jumlahLapanganSekarang = Lapangan::where('tenant_id', $tenant->id)->count();
$batas = $tenant->fitur->batas_lapangan;

if ($batas !== null && $jumlahLapanganSekarang >= $batas) {
    throw ValidationException::withMessages([
        'lapangan' => "Paket {$tenant->paket} kamu dibatasi {$batas} lapangan. Upgrade paket untuk tambah lebih banyak.",
    ]);
}
```

## Upgrade Paket

Kalau tenant upgrade dari Basic ke Pro (atau Pro ke Premium), update baris `tenant_fitur` mereka pakai preset paket baru. Jangan migrasi data lapangan/booking yang sudah ada, cukup ubah baris fitur:

```php
$presetBaru = TenantFitur::presetUntukPaket('pro');
$tenant->fitur->update($presetBaru);
$tenant->update(['paket' => 'pro']);
```

## Saat Bikin UI Baru: Selalu Tanya Dulu

Kalau diminta bikin fitur atau halaman baru, dan tidak jelas fitur itu masuk paket mana, JANGAN asal taruh di semua paket. Tanya ke user dulu: "fitur ini khusus paket apa, atau ada di semua paket?" Lalu update tabel di atas dan `presetUntukPaket()` sesuai jawabannya.
