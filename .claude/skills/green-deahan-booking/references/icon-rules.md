# Aturan Icon: SVG, Bukan Emoji

## Aturan Inti

**Tidak ada emoji mentah (🏟️ 🥅 ⚽ 🎾 🏸 💬 📍 dst) di kode produksi.** Semua icon harus SVG, baik inline langsung di Blade view, atau lewat komponen `<x-icon>`.

Kenapa: emoji render beda-beda tergantung OS/browser/font yang terinstall, kadang muncul kotak putih atau bentuk aneh (sering disebut "broken emoji"). SVG konsisten di semua device dan bisa diberi warna brand.

## Kapan Boleh Pakai Emoji

Hampir tidak pernah, di kode produksi. Pengecualian sangat terbatas: di pesan WhatsApp manual (`wa.me` link) emoji masih boleh dipakai sewajarnya karena itu konteks chat personal, bukan UI website. Tapi di Blade view, komponen, dashboard, semua harus SVG.

## Cara Pakai Komponen `<x-icon>`

```php
{{-- resources/views/components/icon.blade.php --}}
@props(['name', 'size' => 20, 'class' => ''])

<span {{ $attributes->merge(['class' => "inline-flex items-center justify-center $class"]) }}
      style="width: {{ $size }}px; height: {{ $size }}px;">
    @include('icons.' . $name)
</span>
```

Tiap icon disimpan sebagai partial terpisah di `resources/views/icons/`, contoh:

```php
{{-- resources/views/icons/futsal-goal.blade.php --}}
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
     stroke-linecap="round" stroke-linejoin="round" class="w-full h-full">
    <rect x="3" y="7" width="18" height="11" rx="0.5"/>
    <path d="M3 11h18M8 7v11M16 7v11"/>
</svg>
```

Pemakaian:
```blade
<x-icon name="futsal-goal" size="24" class="text-green" />
```

Pakai `stroke="currentColor"` di SVG supaya warnanya ikut class Tailwind yang dipasang di pemanggil (`text-green`, `text-brown`, dst), bukan warna hardcoded di dalam SVG.

## Daftar Icon yang Sudah Pernah Dibuat (Dari Demo PDF & HTML Sebelumnya)

Icon-icon ini sudah pernah dirancang dengan gaya konsisten (line-art, stroke 1.5-1.8, warna brand). Kalau butuh icon serupa, samakan gayanya:

| Nama Icon | Dipakai untuk |
|---|---|
| `stadium` | Logo/cover umum |
| `futsal-goal` | Futsal |
| `soccer-ball` | Mini soccer |
| `padel-racket` | Padel |
| `shuttlecock` | Badminton |
| `tennis-racket` | Tennis |
| `ruler` | Ukuran lapangan |
| `land-map` | Kebutuhan lahan |
| `money-coins` | Modal & ROI |
| `chart-trend` | Laporan pendapatan |
| `leaf` | Perawatan rumput sintetis |
| `checklist` | Checklist perizinan, fitur dicentang |
| `megaphone` | Marketing/promosi |
| `target` | Konsultasi |
| `globe` | Website/domain |
| `build-hammer` | Jasa pembuatan lapangan |
| `bulb` | Tips |
| `warning-triangle` | Peringatan/perhatian |
| `check-circle` | Sukses/konfirmasi |
| `pin-marker` | Lokasi, atau penanda "perlu dicek" |
| `clock` | Waktu/jadwal |
| `phone-chat` | Notifikasi WhatsApp |
| `crown` | Badge member (khusus Premium, warna gold) |

## Saat Butuh Icon Baru yang Belum Ada di Daftar

Bikin SVG baru dengan gaya konsisten:
- `viewBox="0 0 24 24"`
- `fill="none" stroke="currentColor" stroke-width="1.6"` sampai `1.8`
- `stroke-linecap="round" stroke-linejoin="round"`
- Garis sederhana (line-art), bukan icon solid/filled, supaya konsisten dengan gaya icon yang sudah ada

## Mengganti Emoji yang Sudah Terlanjur Ada

Kalau menemukan emoji mentah di kode lama (misal dari hasil generate sebelumnya), ganti jadi `<x-icon>` dengan SVG setara. Tabel padanan cepat:

```
🏟️ → stadium       🥅 → futsal-goal     ⚽ → soccer-ball
🎾 → tennis-racket  🏸 → shuttlecock     📐 → ruler
🌿 → leaf           💰 → money-coins     📈 → chart-trend
✅ → check-circle   ⚠️ → warning-triangle 💡 → bulb
📍 → pin-marker     🌐 → globe           🏗️ → build-hammer
💬 → phone-chat     👑 → crown
```
