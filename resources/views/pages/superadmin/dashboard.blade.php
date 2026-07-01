<x-layouts::superadmin active-page="dashboard" judul="Dashboard">
    <div class="mb-6">
        <h1 class="font-display text-xl font-semibold text-ink">Dashboard</h1>
        <p class="text-sm text-ink-soft mt-0.5">Ringkasan seluruh tenant di platform.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-6">
        <x-card>
            <div class="text-xs text-ink-soft mb-1.5">Total Tenant</div>
            <div class="font-display text-2xl font-semibold text-ink">{{ $totalTenant }}</div>
        </x-card>
        <x-card>
            <div class="text-xs text-ink-soft mb-1.5">Tenant Aktif</div>
            <div class="font-display text-2xl font-semibold text-ink">{{ $tenantAktif }}</div>
        </x-card>
        <x-card>
            <div class="text-xs text-ink-soft mb-1.5">Booking Hari Ini (Semua Tenant)</div>
            <div class="font-display text-2xl font-semibold text-ink">{{ $bookingHariIni }}</div>
        </x-card>
    </div>

    <x-card class="!p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-cream-dark flex items-center justify-between gap-3">
            <div class="text-xs font-bold uppercase tracking-wide text-green">5 Tenant Terbaru</div>
            <a href="{{ route('superadmin.tenants') }}" class="text-xs font-semibold text-plum hover:underline">Lihat semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-cream text-left text-[0.7rem] uppercase tracking-wide text-ink-soft">
                        <th class="px-5 py-2.5 font-bold">Nama Bisnis</th>
                        <th class="px-5 py-2.5 font-bold">Domain</th>
                        <th class="px-5 py-2.5 font-bold">Paket</th>
                        <th class="px-5 py-2.5 font-bold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tenantTerbaru as $tenant)
                        <tr class="border-b border-cream last:border-0">
                            <td class="px-5 py-3 font-bold text-ink">{{ $tenant->nama_bisnis }}</td>
                            <td class="px-5 py-3 text-ink-mid">{{ $tenant->domain }}</td>
                            <td class="px-5 py-3"><x-paket-badge :paket="$tenant->paket" /></td>
                            <td class="px-5 py-3">
                                <x-badge :type="$tenant->status_aktif ? 'confirmed' : 'cancelled'">
                                    {{ $tenant->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-6 text-center text-ink-soft">Belum ada tenant terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts::superadmin>
