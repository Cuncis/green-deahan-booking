@props(['tenant'])

<aside class="w-56 flex-shrink-0 bg-white border-r border-cream-deep flex flex-col">
    <div class="px-5 py-5 border-b border-cream-deep flex items-center justify-between gap-2">
        <span class="font-display font-semibold text-green text-sm">{{ $tenant->nama_bisnis }}</span>
        @if ($tenant->paket !== 'basic')
            <span class="text-[0.6rem] font-extrabold uppercase px-2 py-0.5 rounded-full text-white {{ $tenant->paket === 'premium' ? 'bg-plum' : 'bg-gold' }}">
                {{ $tenant->paket }}
            </span>
        @endif
    </div>

    <nav class="flex-1 px-3 py-3.5 space-y-1">
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
            <x-icon name="calendar" size="16" />
            Dashboard
        </a>

        @if ($tenant->punyaFitur('laporan_pendapatan'))
            <a href="{{ route('admin.laporan') }}"
               class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ request()->routeIs('admin.laporan') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
                <x-icon name="chart-trend" size="16" />
                Laporan Pendapatan
            </a>
        @endif

        <a href="{{ route('booking.index') }}"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-ink-mid hover:bg-cream">
            <x-icon name="globe" size="16" />
            Lihat Halaman Booking
        </a>
    </nav>

    <div class="px-5 py-3.5 border-t border-cream-deep text-xs text-ink-soft">
        Paket <strong class="text-gold">{{ ucfirst($tenant->paket) }}</strong>
    </div>
</aside>
