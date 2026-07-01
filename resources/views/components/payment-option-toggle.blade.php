@props(['harga' => 0, 'tipePembayaran' => 'lunas', 'action' => 'pilihTipePembayaran'])

<div class="mb-5">
    <div class="text-xs font-bold uppercase tracking-wide text-green mb-3">Pilihan Bayar</div>

    <div class="grid grid-cols-2 gap-2">
        <button
            type="button"
            wire:click="{{ $action }}('dp')"
            class="rounded-lg border px-3 py-2.5 text-center text-sm font-semibold transition-colors {{ $tipePembayaran === 'dp' ? 'border-green bg-green-pale text-green' : 'border-cream-deep text-ink-mid' }}"
        >
            Bayar DP 50%
            <span class="block text-xs font-normal opacity-80 mt-0.5">Rp{{ number_format((int) round($harga / 2), 0, ',', '.') }}</span>
        </button>

        <button
            type="button"
            wire:click="{{ $action }}('lunas')"
            class="rounded-lg border px-3 py-2.5 text-center text-sm font-semibold transition-colors {{ $tipePembayaran === 'lunas' ? 'border-green bg-green-pale text-green' : 'border-cream-deep text-ink-mid' }}"
        >
            Bayar Lunas
            <span class="block text-xs font-normal opacity-80 mt-0.5">Rp{{ number_format($harga, 0, ',', '.') }}</span>
        </button>
    </div>
</div>
