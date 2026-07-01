@props(['selected' => 'qris'])

<div x-data="{ metode: @js($selected) }" class="mb-5">
    <div class="text-xs font-bold uppercase tracking-wide text-green mb-3">Metode Pembayaran</div>

    <div class="grid grid-cols-3 gap-2">
        <button
            type="button"
            @click="metode = 'qris'"
            :class="metode === 'qris' ? 'border-green bg-green-pale text-green' : 'border-cream-deep text-ink-mid'"
            class="rounded-lg border px-2 py-3 flex flex-col items-center gap-1.5 text-xs font-semibold transition-colors"
        >
            <x-icon name="qris" size="22" />
            QRIS
        </button>

        <button
            type="button"
            @click="metode = 'ewallet'"
            :class="metode === 'ewallet' ? 'border-green bg-green-pale text-green' : 'border-cream-deep text-ink-mid'"
            class="rounded-lg border px-2 py-3 flex flex-col items-center gap-1.5 text-xs font-semibold transition-colors"
        >
            <x-icon name="wallet" size="22" />
            E-Wallet
        </button>

        <button
            type="button"
            @click="metode = 'va'"
            :class="metode === 'va' ? 'border-green bg-green-pale text-green' : 'border-cream-deep text-ink-mid'"
            class="rounded-lg border px-2 py-3 flex flex-col items-center gap-1.5 text-xs font-semibold transition-colors"
        >
            <x-icon name="bank-transfer" size="22" />
            Transfer VA
        </button>
    </div>
</div>
