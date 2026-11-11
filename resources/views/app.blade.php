<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>Green Deahan Sport - Jasa Pembuatan Lapangan Olahraga Se-Indonesia</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Teko:wght@600&display=swap" rel="stylesheet">

        {{-- Sengaja tanpa resources/css/app.css: halaman Inertia (homepage) memakai CSS template Fuzio, lihat resources/fuzio/ --}}
        @viteReactRefresh
        @vite('resources/js/inertia.tsx')
        @inertiaHead

        <x-favicon />
        <x-seo-head deskripsi="Jasa pembuatan lapangan futsal, mini soccer, padel, dan badminton profesional sejak 2010. Survey, desain, konstruksi, hingga serah terima. Melayani seluruh Indonesia." />
    </head>
    <body>
        <x-seo-body />
        @inertia
    </body>
</html>
