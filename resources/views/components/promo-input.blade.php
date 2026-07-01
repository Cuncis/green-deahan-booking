@props(['model' => 'kodePromo', 'status' => null, 'action' => 'cekPromo'])

<div class="mb-5">
    <div x-data="{ diketik: false }" class="flex gap-2">
        <div class="flex-1">
            <input
                type="text"
                wire:model="{{ $model }}"
                x-on:input="diketik = true"
                placeholder="Kode promo, misal SEPI20"
                class="w-full rounded-lg border bg-cream px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft focus:outline-none focus:ring-1 transition-colors {{ $status === true ? 'border-green focus:border-green focus:ring-green' : ($status === false ? 'border-danger focus:border-danger focus:ring-danger' : 'border-cream-deep focus:border-green focus:ring-green') }}"
                :class="diketik ? '!border-cream-deep !ring-0' : ''"
            />
        </div>
        <x-button type="button" variant="secondary" wire:click="{{ $action }}" x-on:click="diketik = false">
            Pakai
        </x-button>
    </div>

    @if ($status === true)
        <p class="text-xs text-green mt-1.5 flex items-center gap-1.5">
            <x-icon name="check-circle" size="14" class="text-green" />
            Kode promo berhasil dipakai.
        </p>
    @elseif ($status === false)
        <p class="text-xs text-danger mt-1.5 flex items-center gap-1.5">
            <x-icon name="warning" size="14" class="text-danger" />
            Kode promo tidak valid atau sudah kadaluarsa.
        </p>
    @endif
</div>
