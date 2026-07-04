<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Semua Booking, {{ $tenant->nama_bisnis }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        @vite('resources/css/app.css')
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-cream text-ink flex min-h-screen">

        <x-admin-sidebar :tenant="$tenant" />

        <main class="flex-1 px-6 md:px-8 py-6 max-w-6xl">
            <x-admin-topbar />

            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <div>
                    <h1 class="font-display text-xl font-semibold text-ink">Semua Booking</h1>
                    <p class="text-sm text-ink-soft mt-0.5">Daftar lengkap booking {{ $tenant->nama_bisnis }}.</p>
                </div>

                <a
                    href="{{ route('admin.booking.export', request()->query()) }}"
                    class="inline-flex items-center gap-2 rounded-lg font-sans font-semibold text-sm px-5 py-2.5 border-2 border-sand text-ink-mid hover:border-brown-light hover:text-brown transition-colors"
                >
                    <x-icon name="download" size="16" />
                    Download CSV
                </a>
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

            <x-card class="mb-6">
                <form method="GET" action="{{ route('admin.booking') }}" class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <div class="col-span-2 md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Cari</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center text-ink-soft">
                                <x-icon name="search" size="15" />
                            </span>
                            <input
                                type="text"
                                name="cari"
                                value="{{ $cari }}"
                                placeholder="Nama customer atau kode booking"
                                class="w-full rounded-lg border border-cream-deep bg-cream pl-9 pr-3 py-2.5 text-sm text-ink placeholder:text-ink-soft focus:border-green focus:outline-none focus:ring-1 focus:ring-green transition-colors"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Status</label>
                        <select name="status" class="w-full rounded-lg border border-cream-deep bg-cream px-3 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
                            <option value="">Semua Status</option>
                            @foreach (['menunggu' => 'Menunggu', 'dikonfirmasi' => 'Dikonfirmasi', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $value => $label)
                                <option value="{{ $value }}" @selected($filterStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Tanggal</label>
                        <input
                            type="date"
                            name="tanggal"
                            value="{{ $filterTanggal }}"
                            class="w-full rounded-lg border border-cream-deep bg-cream px-3 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Lapangan</label>
                        <select name="lapangan" class="w-full rounded-lg border border-cream-deep bg-cream px-3 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
                            <option value="">Semua Lapangan</option>
                            @foreach ($daftarLapangan as $lapangan)
                                <option value="{{ $lapangan->id }}" @selected((string) $filterLapangan === (string) $lapangan->id)>{{ $lapangan->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-2 md:col-span-5 flex justify-end gap-2">
                        @if ($filterStatus || $filterTanggal || $filterLapangan || $cari)
                            <a href="{{ route('admin.booking') }}" class="inline-flex items-center justify-center rounded-lg font-sans font-semibold text-sm px-5 py-2.5 border-2 border-sand text-ink-mid hover:border-brown-light hover:text-brown transition-colors">
                                Reset
                            </a>
                        @endif
                        <x-button type="submit">Terapkan Filter</x-button>
                    </div>
                </form>
            </x-card>

            <x-card class="!p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-cream text-left text-[0.7rem] uppercase tracking-wide text-ink-soft">
                                <th class="px-5 py-2.5 font-bold">Kode</th>
                                <th class="px-5 py-2.5 font-bold">Customer</th>
                                <th class="px-5 py-2.5 font-bold">Lapangan</th>
                                <th class="px-5 py-2.5 font-bold">Jadwal</th>
                                <th class="px-5 py-2.5 font-bold">Status</th>
                                <th class="px-5 py-2.5 font-bold">Total</th>
                                <th class="px-5 py-2.5 font-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookings as $booking)
                                @php
                                    $tipeBadge = match ($booking->status_booking) {
                                        'menunggu' => 'pending',
                                        'dikonfirmasi', 'selesai' => 'confirmed',
                                        'dibatalkan' => 'cancelled',
                                        default => 'info',
                                    };
                                @endphp
                                <tr class="border-b border-cream last:border-0">
                                    <td class="px-5 py-3 font-bold text-ink">{{ $booking->kode_booking }}</td>
                                    <td class="px-5 py-3">
                                        <div class="font-bold text-ink">{{ $booking->customer->nama }}</div>
                                        <div class="text-xs text-ink-soft">{{ $booking->customer->no_telepon }}</div>
                                    </td>
                                    <td class="px-5 py-3">{{ $booking->slot->lapangan->nama }}</td>
                                    <td class="px-5 py-3">{{ $booking->slot->tanggal->format('d/m/Y') }}, {{ substr($booking->slot->jam_mulai, 0, 5) }}</td>
                                    <td class="px-5 py-3"><x-badge :type="$tipeBadge">{{ ucfirst($booking->status_booking) }}</x-badge></td>
                                    <td class="px-5 py-3">Rp{{ number_format($booking->total_bayar, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->customer->no_telepon) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1.5 bg-green text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold">
                                                <x-icon name="wa-chat" size="14" class="text-white" />
                                                Chat
                                            </a>

                                            @if (in_array($booking->status_booking, ['menunggu', 'dikonfirmasi'], true))
                                                <div x-data="{ batalkanTerbuka: false }" class="flex flex-wrap items-center gap-1.5">
                                                    @if ($booking->status_booking === 'menunggu')
                                                        <form method="POST" action="{{ route('admin.booking.confirm', $booking) }}">
                                                            @csrf
                                                            <x-button type="submit" class="!px-2.5 !py-1.5 !text-xs">Konfirmasi</x-button>
                                                        </form>
                                                    @endif

                                                    <x-button
                                                        type="button"
                                                        variant="danger"
                                                        class="!px-2.5 !py-1.5 !text-xs"
                                                        x-on:click="batalkanTerbuka = true"
                                                    >Batalkan</x-button>

                                                    <div
                                                        x-show="batalkanTerbuka"
                                                        x-cloak
                                                        class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-5"
                                                    >
                                                        <x-card class="max-w-sm w-full" x-on:click.outside="batalkanTerbuka = false">
                                                            <div class="font-display text-lg font-semibold text-ink mb-2">Batalkan Booking</div>
                                                            <p class="text-sm text-ink-mid mb-3">
                                                                Booking {{ $booking->kode_booking }} akan dibatalkan, slot akan kembali kosong.
                                                            </p>
                                                            <form method="POST" action="{{ route('admin.booking.cancel', $booking) }}">
                                                                @csrf
                                                                <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">
                                                                    Alasan pembatalan (opsional)
                                                                </label>
                                                                <textarea
                                                                    name="alasan"
                                                                    rows="3"
                                                                    class="w-full rounded-lg border border-cream-deep bg-cream px-3 py-2 text-sm text-ink mb-3"
                                                                    placeholder="Misal, customer minta ganti jadwal"
                                                                ></textarea>
                                                                <div class="flex gap-2">
                                                                    <x-button type="submit" variant="danger" class="flex-1 justify-center">Ya, Batalkan</x-button>
                                                                    <x-button
                                                                        type="button"
                                                                        variant="secondary"
                                                                        class="flex-1 justify-center"
                                                                        x-on:click="batalkanTerbuka = false"
                                                                    >Tutup</x-button>
                                                                </div>
                                                            </form>
                                                        </x-card>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-6 text-center text-ink-soft">Tidak ada booking yang cocok dengan filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-cream-dark">
                    {{ $bookings->links() }}
                </div>
            </x-card>
        </main>

        @livewireScripts
    </body>
</html>
