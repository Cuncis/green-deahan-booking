<div>
    <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Pilih Tanggal</div>

    <div class="flex gap-2 overflow-x-auto pb-1 mb-5">
        @php $namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']; @endphp
        @foreach ($tanggalPilihan as $tanggal)
            @php $aktif = $tanggal->toDateString() === $selectedDate; @endphp
            <button
                type="button"
                wire:click="pilihTanggal('{{ $tanggal->toDateString() }}')"
                wire:key="tanggal-{{ $tanggal->toDateString() }}"
                class="flex-none w-14 py-2 text-center rounded-lg border transition-colors {{ $aktif ? 'bg-green border-green text-white' : 'bg-white border-cream-deep text-ink-mid' }}"
            >
                <div class="text-[0.65rem] uppercase {{ $aktif ? 'text-white' : 'text-ink-soft' }}">{{ $namaHari[$tanggal->dayOfWeek] }}</div>
                <div class="text-base font-bold">{{ $tanggal->day }}</div>
            </button>
        @endforeach
    </div>

    <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Pilih Jam</div>

    @if ($pesanError)
        <div class="mb-3 rounded-lg bg-danger-pale text-danger text-sm px-3 py-2">
            {{ $pesanError }}
        </div>
    @endif

    <div class="grid grid-cols-3 gap-2.5">
        @forelse ($slotTersedia as $slot)
            @php $tersedia = $slot->status === 'kosong'; @endphp
            <button
                type="button"
                @if ($tersedia) wire:click="pilihSlot({{ $slot->id }})" @else disabled @endif
                wire:key="slot-{{ $slot->id }}"
                wire:loading.attr="disabled"
                class="rounded-lg border px-2 py-2.5 text-center text-sm font-semibold transition-colors
                    {{ $selectedSlot === $slot->id
                        ? 'bg-green border-green text-white'
                        : ($tersedia
                            ? 'bg-white border-cream-deep text-ink-mid hover:border-green hover:bg-green-pale'
                            : 'bg-cream-dark border-cream-deep text-cream-deep line-through cursor-not-allowed') }}"
            >
                {{ substr($slot->jam_mulai, 0, 5) }}
                <span class="block text-xs font-normal opacity-85 mt-0.5">
                    {{ $tersedia ? 'Rp'.number_format($slot->harga, 0, ',', '.') : 'Tidak tersedia' }}
                </span>
            </button>
        @empty
            <p class="col-span-3 text-sm text-ink-soft">Belum ada jadwal untuk tanggal ini.</p>
        @endforelse
    </div>

    <div class="flex flex-wrap gap-4 mt-4 text-xs text-ink-soft">
        <span class="inline-flex items-center gap-1.5">
            <i class="inline-block w-2 h-2 rounded-sm bg-white border border-cream-deep"></i> Tersedia
        </span>
        <span class="inline-flex items-center gap-1.5">
            <i class="inline-block w-2 h-2 rounded-sm bg-green"></i> Dipilih
        </span>
        <span class="inline-flex items-center gap-1.5">
            <i class="inline-block w-2 h-2 rounded-sm bg-cream-deep"></i> Tidak tersedia
        </span>
    </div>
</div>
