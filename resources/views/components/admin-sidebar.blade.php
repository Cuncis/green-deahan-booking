@props(['tenant', 'activePage' => null])

@php
$badgeClass = match ($tenant->paket) {
    'premium' => 'bg-plum text-white',
    'pro' => 'bg-gold text-white',
    default => 'bg-cream-deep text-ink-mid',
};

$paketTextClass = match ($tenant->paket) {
    'premium' => 'text-plum',
    'pro' => 'text-gold',
    default => 'text-ink-mid',
};

/**
 * Item tanpa route sendiri (anchor ke section di Dashboard) hanya bisa
 * aktif lewat prop $activePage, karena request()->routeIs() tidak bisa
 * membedakan fragment (#staf, #promo, dst) di URL yang sama.
 */
$isAktif = function (string $key, ?string $routeName = null) use ($activePage) {
    if ($activePage !== null) {
        return $activePage === $key;
    }

    return $routeName && request()->routeIs($routeName);
};
@endphp

<aside class="w-56 flex-shrink-0 bg-white border-r border-cream-deep flex flex-col">
    <div class="px-5 py-5 border-b border-cream-deep flex items-center justify-between gap-2">
        <span class="font-display font-semibold text-green text-sm">{{ $tenant->nama_bisnis }}</span>
        <span class="text-[0.6rem] font-extrabold uppercase px-2 py-0.5 rounded-full {{ $badgeClass }}">
            {{ $tenant->paket }}
        </span>
    </div>

    <nav class="flex-1 px-3 py-3.5 space-y-1">
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $isAktif('dashboard', 'admin.dashboard') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
            <x-icon name="calendar" size="16" />
            Dashboard
        </a>

        <a href="{{ route('admin.booking') }}"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $isAktif('booking', 'admin.booking') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
            <x-icon name="checklist" size="16" />
            Semua Booking
        </a>

        <a href="{{ route('admin.lapangan') }}"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $isAktif('lapangan', 'admin.lapangan') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
            <x-icon name="futsal-goal" size="16" />
            Lapangan Saya
        </a>

        <a href="{{ route('admin.jadwal') }}"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $isAktif('jadwal', 'admin.jadwal') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
            <x-icon name="clock" size="16" />
            Jadwal
        </a>

        @if ($tenant->punyaFitur('laporan_pendapatan'))
            <a href="{{ route('admin.dashboard') }}#laporan-pendapatan"
               class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $isAktif('laporan') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
                <x-icon name="chart-trend" size="16" />
                Laporan Pendapatan
            </a>
        @endif

        @if ($tenant->punyaFitur('kode_promo'))
            <a href="{{ route('admin.dashboard') }}#kode-promo"
               class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $isAktif('promo') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
                <x-icon name="checklist" size="16" />
                Promo
            </a>
        @endif

        @if ($tenant->punyaFitur('manajemen_staf'))
            <a href="{{ route('admin.dashboard') }}#staf"
               class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $isAktif('staf') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
                <x-icon name="user-group" size="16" />
                Staf & Operator
            </a>
        @endif

        @if ($tenant->punyaFitur('sistem_membership'))
            <a href="{{ route('admin.dashboard') }}#membership"
               class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $isAktif('membership') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
                <x-icon name="crown" size="16" />
                Member & Loyalti
            </a>
        @endif

        @if ($tenant->punyaFitur('reminder_otomatis'))
            <a href="{{ route('admin.dashboard') }}#reminder"
               class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $isAktif('reminder') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
                <x-icon name="bell" size="16" />
                Reminder Otomatis
            </a>
        @endif

        @if ($tenant->punyaFitur('analitik_lanjutan'))
            <a href="{{ route('admin.dashboard') }}#analitik"
               class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $isAktif('analitik') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
                <x-icon name="bar-compare" size="16" />
                Analitik Antar Cabang
            </a>
        @endif

        <a href="{{ route('admin.pengaturan') }}"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm {{ $isAktif('pengaturan', 'admin.pengaturan') ? 'bg-green-pale text-green font-bold' : 'text-ink-mid hover:bg-cream' }}">
            <x-icon name="settings-gear" size="16" />
            Pengaturan
        </a>

        <a href="{{ route('booking.index') }}"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-ink-mid hover:bg-cream">
            <x-icon name="globe" size="16" />
            Lihat Halaman Booking
        </a>
    </nav>

    <div class="px-5 py-3.5 border-t border-cream-deep text-xs text-ink-soft">
        Paket <strong class="{{ $paketTextClass }}">{{ ucfirst($tenant->paket) }}</strong>
    </div>
</aside>
