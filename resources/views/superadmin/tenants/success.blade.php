<x-layouts::superadmin active-page="tenants" judul="Tenant Berhasil Dibuat">
    <div class="mb-6">
        <div class="w-14 h-14 rounded-full bg-green-pale flex items-center justify-center mb-4">
            <x-icon name="check-circle" size="28" class="text-green" />
        </div>
        <h1 class="font-display text-xl font-semibold text-ink">Tenant Berhasil Dibuat</h1>
        <p class="text-sm text-ink-soft mt-0.5">Kirim link undangan di bawah supaya PIC bisa langsung membuat akun owner.</p>
    </div>

    <x-card class="max-w-2xl mb-5">
        <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Ringkasan</div>

        <div class="flex justify-between py-2 border-b border-dashed border-cream-deep text-sm">
            <span class="text-ink-soft">Nama Bisnis</span>
            <span class="font-semibold text-ink">{{ $tenant->nama_bisnis }}</span>
        </div>
        <div class="flex justify-between py-2 border-b border-dashed border-cream-deep text-sm">
            <span class="text-ink-soft">Domain</span>
            <span class="font-semibold text-ink">{{ $tenant->domain }}</span>
        </div>
        <div class="flex justify-between py-2 text-sm">
            <span class="text-ink-soft">Paket</span>
            <x-paket-badge :paket="$tenant->paket" />
        </div>
    </x-card>

    <x-card class="max-w-2xl mb-5" x-data="{ copied: false, link: {{ json_encode($invitationLink) }} }">
        <div class="text-xs font-bold uppercase tracking-wide text-green mb-3">Link Invitation Owner</div>

        <div class="flex items-center gap-2">
            <input
                type="text"
                readonly
                :value="link"
                x-on:click="$el.select()"
                class="flex-1 rounded-lg border border-cream-deep bg-cream px-3 py-2.5 text-sm text-ink-mid"
            />
            <x-button
                type="button"
                variant="secondary"
                x-on:click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 2000)"
            >
                <x-icon name="checklist" size="14" />
                <span x-show="!copied">Kopi Link</span>
                <span x-show="copied" x-cloak>Tersalin</span>
            </x-button>
        </div>

        <p class="text-sm text-ink-mid mt-3">
            Kirim link ini ke {{ $namaPic }}. Link berlaku 7 hari.
        </p>
    </x-card>

    <div class="flex gap-2">
        <a href="{{ route('superadmin.tenants.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg font-sans font-semibold text-sm px-5 py-2.5 transition-colors bg-green text-white hover:bg-green-mid">
            Buat Tenant Lain
        </a>
        <a href="{{ route('superadmin.tenants') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg font-sans font-semibold text-sm px-5 py-2.5 transition-colors border-2 border-sand text-ink-mid hover:border-brown-light">
            Kembali ke Daftar Tenant
        </a>
    </div>
</x-layouts::superadmin>
