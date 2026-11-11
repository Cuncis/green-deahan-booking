<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Judul & deskripsi per halaman dari prop `meta` (lihat routes/web.php), dirender di server supaya terbaca mesin pencari --}}
        <title inertia>{{ $page['props']['meta']['title'] ?? 'Green Deahan Sport' }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Teko:wght@600&display=swap" rel="stylesheet">

        {{-- Sengaja tanpa resources/css/app.css: halaman Inertia (homepage, Tentang Kami) memakai CSS template Fuzio, lihat resources/fuzio/ --}}
        @viteReactRefresh
        @vite('resources/js/inertia.tsx')
        @inertiaHead

        <x-favicon />
        <x-seo-head :deskripsi="$page['props']['meta']['description'] ?? null" />
    </head>
    <body>
        <x-seo-body />
        @inertia
    </body>
</html>
