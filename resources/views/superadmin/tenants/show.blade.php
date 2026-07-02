@php
    $statusLabel = match ($statusCustomDomain) {
        'aktif' => 'Aktif',
        'ssl_pending' => 'SSL Pending',
        default => 'Belum dikonfigurasi',
    };
    $statusBadgeType = match ($statusCustomDomain) {
        'aktif' => 'confirmed',
        'ssl_pending' => 'pending',
        default => 'info',
    };
@endphp

<x-layouts::superadmin active-page="tenants" judul="Detail Tenant">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-semibold text-ink">{{ $tenant->nama_bisnis }}</h1>
            <p class="text-sm text-ink-soft mt-0.5">{{ $tenant->domain }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-paket-badge :paket="$tenant->paket" />
            <x-badge :type="$tenant->status_aktif ? 'confirmed' : 'cancelled'">
                {{ $tenant->status_aktif ? 'Aktif' : 'Nonaktif' }}
            </x-badge>
        </div>
    </div>

    <x-card class="max-w-2xl mb-6">
        <div class="text-xs font-bold uppercase tracking-wide text-plum mb-4">Custom Domain</div>

        <div class="flex justify-between py-2 border-b border-dashed border-cream-deep text-sm mb-3">
            <span class="text-ink-soft">Domain Default</span>
            <span class="font-semibold text-ink">{{ $tenant->domain }}</span>
        </div>

        @if ($tenant->custom_domain_diminta && ! $tenant->custom_domain)
            <div class="rounded-lg border border-gold/30 bg-gold/10 px-4 py-3 text-sm text-ink mb-3">
                Pelanggan meminta custom domain <strong>{{ $tenant->custom_domain_diminta }}</strong> saat mendaftar (+Rp250.000/tahun).
                Verifikasi kepemilikan domainnya dulu sebelum disimpan di bawah.
            </div>
        @endif

        @if ($tenant->paket === 'basic')
            <div class="rounded-lg border border-gold/30 bg-gold/10 px-4 py-3 text-sm text-ink">
                Custom domain hanya tersedia untuk paket Pro dan Premium. Upgrade paket tenant ini dulu untuk bisa memakai domain sendiri.
            </div>
        @else
            <div class="flex justify-between py-2 text-sm mb-3">
                <span class="text-ink-soft">Status Verifikasi</span>
                <x-badge :type="$statusBadgeType">{{ $statusLabel }}</x-badge>
            </div>

            <form method="POST" action="{{ route('superadmin.tenants.custom-domain.store', $tenant) }}" class="flex gap-2">
                @csrf
                <div class="flex-1">
                    <x-input name="custom_domain" placeholder="namadomain.com" :value="old('custom_domain', $tenant->custom_domain ?? $tenant->custom_domain_diminta)" />
                </div>
                <x-button type="submit">Simpan Domain</x-button>
            </form>
            @error('custom_domain') <p class="text-sm text-danger mt-2">{{ $message }}</p> @enderror

            @if ($tenant->custom_domain)
                <form method="POST" action="{{ route('superadmin.tenants.custom-domain.destroy', $tenant) }}" class="mt-3">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-danger underline">Hapus custom domain</button>
                </form>
            @endif
        @endif
    </x-card>

    @if (! empty($perintahServer))
        <x-card class="max-w-2xl mb-6">
            <div class="text-xs font-bold uppercase tracking-wide text-plum mb-3">Perintah Server</div>
            <p class="text-sm text-ink-mid mb-3">
                Jalankan perintah berikut di server supaya {{ $tenant->custom_domain }} benar-benar aktif dengan SSL.
            </p>
            <div class="space-y-2">
                @foreach ($perintahServer as $perintah)
                    <pre class="rounded-lg bg-ink text-cream text-xs p-3 overflow-x-auto whitespace-pre-wrap">{{ $perintah }}</pre>
                @endforeach
            </div>
        </x-card>
    @endif

    <a href="{{ route('superadmin.tenants') }}" class="text-sm text-ink-soft underline">Kembali ke Daftar Tenant</a>
</x-layouts::superadmin>
