<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Lapangan Saya, {{ $tenant->nama_bisnis }}</title>

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
                    <h1 class="font-display text-xl font-semibold text-ink">Lapangan Saya</h1>
                    <p class="text-sm text-ink-soft mt-0.5">
                        {{ $jumlahLapangan }}{{ $batasLapangan !== null ? " dari {$batasLapangan}" : '' }} lapangan dipakai.
                    </p>
                </div>

                @if ($batasLapangan === null || $jumlahLapangan < $batasLapangan)
                    <a href="{{ route('admin.lapangan.create') }}" class="inline-flex items-center gap-2 rounded-lg font-sans font-semibold text-sm px-5 py-2.5 bg-green text-white hover:bg-green-mid transition-colors">
                        <x-icon name="plus" size="16" class="text-white" />
                        Tambah Lapangan
                    </a>
                @endif
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

            @if ($batasLapangan !== null && $jumlahLapangan >= $batasLapangan)
                <div class="mb-5 rounded-lg border border-amber/30 bg-amber-pale px-4 py-4 text-sm text-amber">
                    <p class="mb-3">Paket {{ ucfirst($tenant->paket) }} kamu dibatasi {{ $batasLapangan }} lapangan. Upgrade paket untuk tambah lebih banyak.</p>

                    <div class="flex flex-wrap gap-3">
                        @if ($tenant->paket === 'basic')
                            <form method="POST" action="{{ route('admin.lapangan.minta-upgrade') }}">
                                @csrf
                                <input type="hidden" name="paket_tujuan" value="pro">
                                <button type="submit" class="flex flex-col items-start gap-0.5 rounded-lg border-2 border-amber/40 bg-white px-4 py-2.5 text-left hover:border-amber transition-colors">
                                    <span class="font-sans font-semibold text-sm text-ink">Upgrade ke Pro</span>
                                    <span class="text-xs text-ink-soft">3 lapangan, DP pembayaran, kode promo, booking berulang, rating &amp; ulasan, laporan pendapatan.</span>
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.lapangan.minta-upgrade') }}">
                                @csrf
                                <input type="hidden" name="paket_tujuan" value="premium">
                                <button type="submit" class="flex flex-col items-start gap-0.5 rounded-lg border-2 border-amber/40 bg-white px-4 py-2.5 text-left hover:border-amber transition-colors">
                                    <span class="font-sans font-semibold text-sm text-ink">Upgrade ke Premium</span>
                                    <span class="text-xs text-ink-soft">Lapangan tanpa batas, multi-cabang, membership, reminder otomatis, dan semua fitur Pro.</span>
                                </button>
                            </form>
                        @elseif ($tenant->paket === 'pro')
                            <form method="POST" action="{{ route('admin.lapangan.minta-upgrade') }}">
                                @csrf
                                <input type="hidden" name="paket_tujuan" value="premium">
                                <button type="submit" class="flex flex-col items-start gap-0.5 rounded-lg border-2 border-amber/40 bg-white px-4 py-2.5 text-left hover:border-amber transition-colors">
                                    <span class="font-sans font-semibold text-sm text-ink">Upgrade ke Premium</span>
                                    <span class="text-xs text-ink-soft">Lapangan tanpa batas, multi-cabang, membership, reminder otomatis, role staf, dan analitik lanjutan.</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($daftarLapangan as $lapangan)
                    <x-card class="!p-0 overflow-hidden">
                        <div class="h-32 bg-cream-dark flex items-center justify-center overflow-hidden">
                            @if ($lapangan->foto_url)
                                <img src="{{ $lapangan->foto_url }}" alt="{{ $lapangan->nama }}" class="w-full h-full object-cover">
                            @else
                                <x-icon name="futsal-goal" size="36" class="text-ink-soft" />
                            @endif
                        </div>
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <span class="font-display font-semibold text-ink">{{ $lapangan->nama }}</span>
                                <x-badge :type="$lapangan->status_aktif ? 'confirmed' : 'cancelled'">
                                    {{ $lapangan->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                </x-badge>
                            </div>
                            <p class="text-xs text-ink-soft mb-1">{{ ucfirst($lapangan->jenis_olahraga) }}, {{ $lapangan->cabang->nama_cabang }}</p>
                            <p class="text-sm text-ink-mid mb-3">Rp{{ number_format($lapangan->harga_per_jam, 0, ',', '.') }}/jam</p>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.lapangan.edit', $lapangan) }}" class="inline-flex items-center gap-1.5 rounded-lg font-sans font-semibold text-xs px-3 py-2 border-2 border-sand text-ink-mid hover:border-brown-light hover:text-brown transition-colors">
                                    <x-icon name="pencil-edit" size="14" />
                                    Edit
                                </a>

                                <div x-data="{ hapusTerbuka: false }">
                                    <button type="button" x-on:click="hapusTerbuka = true" class="inline-flex items-center gap-1.5 rounded-lg font-sans font-semibold text-xs px-3 py-2 bg-danger/10 text-danger border border-danger/20">
                                        <x-icon name="trash" size="14" />
                                        Hapus
                                    </button>

                                    <div x-show="hapusTerbuka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-5">
                                        <x-card class="max-w-sm w-full" x-on:click.outside="hapusTerbuka = false">
                                            <div class="font-display text-lg font-semibold text-ink mb-2">Hapus Lapangan</div>
                                            <p class="text-sm text-ink-mid mb-3">
                                                Lapangan "{{ $lapangan->nama }}" akan dihapus permanen, beserta jadwal slotnya yang belum pernah dibooking.
                                            </p>
                                            <form method="POST" action="{{ route('admin.lapangan.destroy', $lapangan) }}">
                                                @csrf
                                                @method('DELETE')
                                                <div class="flex gap-2">
                                                    <x-button type="submit" variant="danger" class="flex-1 justify-center">Ya, Hapus</x-button>
                                                    <x-button type="button" variant="secondary" class="flex-1 justify-center" x-on:click="hapusTerbuka = false">Batal</x-button>
                                                </div>
                                            </form>
                                        </x-card>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-card>
                @empty
                    <x-card class="lg:col-span-3">
                        <p class="text-sm text-ink-mid text-center py-4">Belum ada lapangan, tambahkan lapangan pertamamu.</p>
                    </x-card>
                @endforelse
            </div>
        </main>

        @livewireScripts
    </body>
</html>
