<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Dashboard, {{ $tenant->nama_bisnis }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        @vite('resources/css/app.css')
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-cream text-ink flex min-h-screen">

        <x-admin-sidebar :tenant="$tenant" />

        <main class="flex-1 px-6 md:px-8 py-6 max-w-6xl">
            <x-admin-topbar />

            <div class="mb-6">
                <h1 class="font-display text-xl font-semibold text-ink">Dashboard</h1>
                <p class="text-sm text-ink-soft mt-0.5">Ringkasan booking dan performa {{ $tenant->nama_bisnis }}.</p>
            </div>

            @if (session('success'))
                <div class="mb-5 rounded-lg border border-green/30 bg-green-pale px-4 py-3 text-sm text-green">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-5 rounded-lg border border-danger/30 bg-danger-pale px-4 py-3 text-sm text-danger">
                    {{ session('error') }}
                </div>
            @endif

            <livewire:dashboard-admin />
        </main>

        <x-wa-popup-script />
        @livewireScripts
    </body>
</html>
