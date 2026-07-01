<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $judul }}, {{ $tenant->nama_bisnis }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-cream text-ink flex min-h-screen">

        <x-admin-sidebar :tenant="$tenant" />

        <main class="flex-1 px-6 md:px-8 py-6 max-w-6xl">
            <div class="mb-6">
                <h1 class="font-display text-xl font-semibold text-ink">{{ $judul }}</h1>
                <p class="text-sm text-ink-soft mt-0.5">{{ $deskripsi }}</p>
            </div>

            <x-card>
                <p class="text-sm text-ink-mid">Halaman ini sedang disiapkan, segera hadir.</p>
            </x-card>
        </main>

        @livewireScripts
    </body>
</html>
