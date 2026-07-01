# Design Tokens & Styling: Tailwind Murni (Tanpa DaisyUI)

## Kenapa Tidak Pakai DaisyUI

DaisyUI bawa tema visual sendiri (rounded button khas, warna preset, shadow generik) yang akan terus-menerus di-override supaya cocok brand Green Deahan Sport. Hasilnya gampang terasa "template SaaS generik", bertentangan dengan tujuan project ini punya identitas visual sendiri (krem/hijau/earthy, khas, premium). Project ini pakai **Tailwind CSS murni** + komponen Blade custom yang dibangun dari token di bawah.

Jangan install paket `daisyui` di `package.json`. Jangan pakai class `btn`, `card`, `badge` dari DaisyUI. Semua komponen dibangun dari utility class Tailwind biasa, dikemas jadi Blade component supaya reusable.

## Setup `tailwind.config.js`

```js
// tailwind.config.js
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        cream: { DEFAULT: '#F5F0E8', dark: '#EDE5D5', deep: '#E0D5C0' },
        sand: '#C9B99A',
        brown: { DEFAULT: '#7C5C3A', light: '#A07850' },
        green: { DEFAULT: '#3A6B4A', mid: '#4E8B60', light: '#6AAF7C', pale: '#D6EAD9' },
        gold: { DEFAULT: '#C99A3A', pale: '#F5E9D0' },
        plum: { DEFAULT: '#6B4A6B', pale: '#E9DEE9' },
        ink: { DEFAULT: '#2A2018', mid: '#5C4A30', soft: '#8A7260' },
        danger: { DEFAULT: '#C0392B', pale: '#F7DEDC' },
        amber: { DEFAULT: '#B8860B', pale: '#FBF1D8' },
      },
      fontFamily: {
        display: ['Lora', 'serif'],
        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
      },
      borderRadius: {
        card: '16px',
      },
    },
  },
  plugins: [],
  // TIDAK ADA require('daisyui') DI SINI
};
```

## Palet Warna (Referensi Cepat)

| Token | Hex | Dipakai untuk |
|---|---|---|
| `cream` | `#F5F0E8` | Background utama |
| `cream-dark` | `#EDE5D5` | Background section alternatif |
| `cream-deep` | `#E0D5C0` | Border default |
| `green` | `#3A6B4A` | Warna utama brand: tombol primary, link aktif, header sidebar |
| `green-mid` | `#4E8B60` | Hover state warna green |
| `green-pale` | `#D6EAD9` | Background badge/highlight hijau lembut |
| `brown` | `#7C5C3A` | Aksen sekunder: link "jasa website", badge brown |
| `gold` | `#C99A3A` | Khusus fitur Premium: badge member, tag "PREMIUM" |
| `plum` | `#6B4A6B` | Khusus tag "PRO"/"PREMIUM" di navbar |
| `ink` | `#2A2018` | Teks utama |
| `ink-mid` | `#5C4A30` | Teks sekunder |
| `ink-soft` | `#8A7260` | Teks tersier/caption |
| `danger` | `#C0392B` | Status dibatalkan, error |
| `amber` | `#B8860B` | Status pending/menunggu |

**Aturan pemakaian warna per paket** (konsisten dengan demo HTML sebelumnya):
- Badge "PRO" → warna `gold`
- Badge "PREMIUM" → gradient `plum` ke `plum-light` atau solid `plum`
- Badge "Basic" → tidak perlu badge khusus, atau pakai `cream-deep`

## Tipografi

```
Heading besar (H1, hero)     : font-display text-3xl md:text-4xl font-semibold text-ink
Heading sedang (H2, section) : font-display text-xl font-semibold text-ink
Body text                     : font-sans text-sm text-ink-mid
Caption/label kecil           : font-sans text-xs text-ink-soft uppercase tracking-wide font-bold
```

Font di-load lewat Google Fonts di layout utama:
```html
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">
```

## Komponen Blade Wajib Dibuat (Bukan Pakai DaisyUI)

Simpan di `resources/views/components/`. Tiap kali butuh elemen UI berikut, pakai komponen ini, jangan tulis ulang class Tailwind dari nol setiap kali:

| Komponen | File | Dipakai untuk |
|---|---|---|
| `<x-button>` | `components/button.blade.php` | Tombol primary/secondary/outline, lihat varian di bawah |
| `<x-card>` | `components/card.blade.php` | Kartu panel (dashboard stat, booking item) |
| `<x-badge>` | `components/badge.blade.php` | Status booking (dikonfirmasi/menunggu/dibatalkan) |
| `<x-input>` | `components/input.blade.php` | Form input dengan label |
| `<x-icon>` | `components/icon.blade.php` | Wrapper SVG icon, lihat `references/icon-rules.md` |

### Contoh `<x-button>`

```php
{{-- resources/views/components/button.blade.php --}}
@props(['variant' => 'primary'])

@php
$base = 'inline-flex items-center justify-center gap-2 rounded-lg font-sans font-semibold text-sm px-5 py-2.5 transition-colors';
$variants = [
    'primary'   => 'bg-green text-white hover:bg-green-mid',
    'secondary' => 'border-2 border-sand text-ink-mid hover:border-brown-light hover:text-brown',
    'gold'      => 'bg-gold text-white hover:bg-gold/90',
];
@endphp

<button {{ $attributes->merge(['class' => $base . ' ' . $variants[$variant]]) }}>
    {{ $slot }}
</button>
```

Pemakaian:
```blade
<x-button variant="primary">Booking Sekarang</x-button>
<x-button variant="secondary">Lihat Detail</x-button>
```

## Spacing & Radius

```
Card padding       : p-5 md:p-6
Card radius         : rounded-card (16px, sudah didefinisikan di tailwind.config.js)
Button radius        : rounded-lg (8px)
Badge/pill radius     : rounded-full
Section gap (vertical): space-y-6 atau gap-6 untuk grid
```

## Saat Bikin Halaman Baru

1. Cek dulu apakah komponen yang dibutuhkan sudah ada di `resources/views/components/`
2. Kalau belum ada, bikin komponen baru mengikuti pola di atas (bukan tulis class Tailwind panjang langsung di halaman)
3. Pakai warna dari tabel token di atas, jangan reka hex baru sendiri kecuali benar-benar perlu varian baru (kalau perlu, tambahkan ke `tailwind.config.js` dan dokumentasikan di sini)
4. Jangan install atau import DaisyUI dengan alasan apapun
