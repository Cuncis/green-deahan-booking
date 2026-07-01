@props(['activePage' => null, 'judul' => 'Dashboard'])

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $judul }}, Green Deahan Sport Platform</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-cream text-ink flex min-h-screen">

        <aside class="w-56 flex-shrink-0 bg-plum flex flex-col">
            <div class="px-5 py-5 border-b border-white/15">
                <span class="font-display font-semibold text-white text-sm">Green Deahan Sport</span>
                <div class="text-[0.62rem] font-extrabold uppercase tracking-wide text-white/70 mt-0.5">Platform</div>
            </div>

            <nav class="flex-1 px-3 py-3.5 space-y-1">
                <a href="{{ route('superadmin.dashboard') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $activePage === 'dashboard' ? 'bg-white/15 text-white font-bold' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <x-icon name="calendar" size="16" />
                    Dashboard
                </a>

                <a href="{{ route('superadmin.tenants') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $activePage === 'tenants' ? 'bg-white/15 text-white font-bold' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <x-icon name="checklist" size="16" />
                    Semua Tenant
                </a>

                <a href="{{ route('superadmin.tenants.create') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $activePage === 'tenants.create' ? 'bg-white/15 text-white font-bold' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <x-icon name="user-group" size="16" />
                    Tambah Tenant
                </a>
            </nav>

            <div class="px-5 py-3.5 border-t border-white/15">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-white/70 hover:text-white underline">Keluar</button>
                </form>
            </div>
        </aside>

        <main class="flex-1 px-6 md:px-8 py-6 max-w-6xl">
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

            @if (session('invitation_link'))
                <div class="mb-5 rounded-lg border border-gold/30 bg-gold/10 px-4 py-3 text-sm text-ink">
                    <div class="font-bold text-ink mb-1">Link undangan owner untuk {{ session('invitation_tenant') }}</div>
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            readonly
                            value="{{ session('invitation_link') }}"
                            x-data
                            x-on:click="$el.select()"
                            class="flex-1 rounded-lg border border-cream-deep bg-white px-3 py-2 text-xs text-ink-mid"
                        />
                    </div>
                    <p class="text-xs text-ink-soft mt-1.5">Kopi link ini dan kirim manual ke klien (WhatsApp/email). Berlaku 7 hari.</p>
                </div>
            @endif

            {{ $slot }}
        </main>
    </body>
</html>
