@props(['selected' => 'qris', 'action' => 'pilihMetodePembayaran'])

<div class="mb-5">
    <div class="text-xs font-bold uppercase tracking-wide text-green mb-3">Metode Pembayaran</div>

    <div class="grid grid-cols-3 gap-2">
        <button
            type="button"
            wire:click="{{ $action }}('qris')"
            class="rounded-lg border px-2 py-3 flex flex-col items-center gap-1.5 text-xs font-semibold transition-colors {{ $selected === 'qris' ? 'border-green bg-green-pale text-green' : 'border-cream-deep text-ink-mid' }}"
        >
            <x-icon name="qris" size="22" />
            QRIS
        </button>

        <button
            type="button"
            wire:click="{{ $action }}('ewallet')"
            class="rounded-lg border px-2 py-3 flex flex-col items-center gap-1.5 text-xs font-semibold transition-colors {{ $selected === 'ewallet' ? 'border-green bg-green-pale text-green' : 'border-cream-deep text-ink-mid' }}"
        >
            <x-icon name="wallet" size="22" />
            E-Wallet
        </button>

        <button
            type="button"
            wire:click="{{ $action }}('va')"
            class="rounded-lg border px-2 py-3 flex flex-col items-center gap-1.5 text-xs font-semibold transition-colors {{ $selected === 'va' ? 'border-green bg-green-pale text-green' : 'border-cream-deep text-ink-mid' }}"
        >
            <x-icon name="bank-transfer" size="22" />
            Transfer VA
        </button>
    </div>
</div>
