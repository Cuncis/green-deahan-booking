<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {{-- Jangan biarkan iOS/Android mengubah nomor telepon teks biasa jadi link; tautan telepon/WA sudah dibuat eksplisit --}}
        <meta name="format-detection" content="telephone=no">

        {{-- Judul & deskripsi per halaman dari prop `meta` (lihat routes/web.php), dirender di server supaya terbaca mesin pencari --}}
        <title inertia>{{ $page['props']['meta']['title'] ?? 'Green Deahan Sport' }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Teko:wght@600&display=swap" rel="stylesheet">

        {{-- Sengaja tanpa resources/css/app.css: halaman Inertia (homepage, Tentang Kami, Kontak, Blog, Galeri) memakai CSS template Fuzio, lihat resources/fuzio/ --}}
        @viteReactRefresh
        @vite('resources/js/inertia.tsx')
        @inertiaHead

        {{-- Halaman yang tidak boleh diindeks (mis. toko yang belum dibuka untuk umum, keranjang, status pesanan) --}}
        @if (! empty($page['props']['meta']['noindex']))
            <meta name="robots" content="noindex, nofollow">
        @endif

        <x-favicon />
        <x-seo-head
            :deskripsi="$page['props']['meta']['description'] ?? null"
            :image="$page['props']['meta']['image'] ?? null"
            :tipe="$page['props']['meta']['type'] ?? 'website'"
        />
    </head>
    <body>
        <x-seo-body />
        @inertia
    </body>
</html>
