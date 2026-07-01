<x-layouts::superadmin active-page="tenants" judul="Semua Tenant">
    <div class="mb-6 flex items-start justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-semibold text-ink">Semua Tenant</h1>
            <p class="text-sm text-ink-soft mt-0.5">Kelola seluruh tenant yang terdaftar di platform.</p>
        </div>
        <a href="{{ route('superadmin.tenants.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg font-sans font-semibold text-sm px-5 py-2.5 transition-colors bg-green text-white hover:bg-green-mid">
            Tambah Tenant
        </a>
    </div>

    <form method="GET" action="{{ route('superadmin.tenants') }}" class="flex flex-wrap items-end gap-3 mb-5">
        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Paket</label>
            <select name="paket" class="rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink">
                <option value="">Semua Paket</option>
                <option value="basic" @selected($filterPaket === 'basic')>Basic</option>
                <option value="pro" @selected($filterPaket === 'pro')>Pro</option>
                <option value="premium" @selected($filterPaket === 'premium')>Premium</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Status</label>
            <select name="status" class="rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink">
                <option value="">Semua Status</option>
                <option value="aktif" @selected($filterStatus === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($filterStatus === 'nonaktif')>Nonaktif</option>
            </select>
        </div>

        <x-button type="submit" variant="secondary">Filter</x-button>

        @if ($filterPaket || $filterStatus)
            <a href="{{ route('superadmin.tenants') }}" class="text-sm text-ink-soft underline">Reset</a>
        @endif
    </form>

    <x-card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-cream text-left text-[0.7rem] uppercase tracking-wide text-ink-soft">
                        <th class="px-5 py-2.5 font-bold">Nama Bisnis</th>
                        <th class="px-5 py-2.5 font-bold">Domain</th>
                        <th class="px-5 py-2.5 font-bold">Paket</th>
                        <th class="px-5 py-2.5 font-bold">Status</th>
                        <th class="px-5 py-2.5 font-bold">Berakhir</th>
                        <th class="px-5 py-2.5 font-bold">Booking</th>
                        <th class="px-5 py-2.5 font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tenants as $tenant)
                        <tr class="border-b border-cream last:border-0">
                            <td class="px-5 py-3 font-bold text-ink">{{ $tenant->nama_bisnis }}</td>
                            <td class="px-5 py-3 text-ink-mid">{{ $tenant->domain }}</td>
                            <td class="px-5 py-3"><x-paket-badge :paket="$tenant->paket" /></td>
                            <td class="px-5 py-3">
                                <x-badge :type="$tenant->status_aktif ? 'confirmed' : 'cancelled'">
                                    {{ $tenant->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-ink-mid">{{ $tenant->tanggal_berakhir?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-5 py-3 text-ink-mid">{{ $tenant->bookings_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @if ($tenant->status_aktif)
                                        <form method="POST" action="{{ route('superadmin.tenants.deactivate', $tenant) }}">
                                            @csrf
                                            <x-button type="submit" variant="secondary" class="!px-2.5 !py-1.5 !text-xs">Nonaktifkan</x-button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('superadmin.tenants.activate', $tenant) }}">
                                            @csrf
                                            <x-button type="submit" variant="secondary" class="!px-2.5 !py-1.5 !text-xs">Aktifkan</x-button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('superadmin.tenants.invite', $tenant) }}">
                                        @csrf
                                        <x-button type="submit" variant="secondary" class="!px-2.5 !py-1.5 !text-xs">Kirim Invitation Owner</x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-6 text-center text-ink-soft">Tidak ada tenant yang cocok dengan filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts::superadmin>
