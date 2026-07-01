@props(['tenant'])

<div class="mt-5 rounded-lg border border-cream-deep bg-cream p-4">
    <div class="text-xs font-bold uppercase tracking-wide text-green mb-3">Transfer Manual</div>

    <div class="space-y-1.5 text-sm mb-4">
        <div class="flex justify-between">
            <span class="text-ink-soft">Bank</span>
            <span class="font-semibold text-ink">{{ $tenant->bank_nama ?? 'Belum diatur' }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-ink-soft">No. Rekening</span>
            <span class="font-semibold text-ink">{{ $tenant->bank_no_rekening ?? 'Belum diatur' }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-ink-soft">Atas Nama</span>
            <span class="font-semibold text-ink">{{ $tenant->bank_pemilik_rekening ?? 'Belum diatur' }}</span>
        </div>
    </div>

    <p class="text-xs text-ink-soft mb-3">
        Transfer sesuai total bayar, lalu kirim bukti transfer beserta detail booking lewat WhatsApp supaya admin bisa segera mengonfirmasi.
    </p>

    <a
        :href="linkTransferManual"
        target="_blank"
        class="flex items-center justify-center gap-2 bg-green text-white rounded-lg py-2.5 font-bold text-sm w-full"
    >
        <x-icon name="wa-chat" size="18" class="text-white" />
        Konfirmasi via WhatsApp
    </a>
</div>
