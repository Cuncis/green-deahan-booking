<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="format-detection" content="telephone=no">
        <meta name="theme-color" content="#F5F0E8">

        @php($meta = $page['props']['meta'] ?? [])
        <title inertia>{{ $meta['title'] ?? 'Booking Lapangan' }}</title>
        @if (! empty($meta['description']))
            <meta name="description" content="{{ $meta['description'] }}">
        @endif

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        {{-- Root view halaman booking customer React, lihat HandleInertiaRequests::rootView() --}}
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/booking/app.tsx'])
        @inertiaHead
        <x-favicon />
    </head>
    <body class="bg-background font-sans text-foreground antialiased">
        @inertia
    </body>
</html>
