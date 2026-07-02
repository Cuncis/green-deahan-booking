<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3">
            <span class="text-xs font-bold uppercase tracking-wide text-ink-soft">Lapangan</span>
            <select wire:model.live="lapanganId" class="rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink">
                <option value="">Semua Lapangan</option>
                @foreach ($daftarLapangan as $lapangan)
                    <option value="{{ $lapangan->id }}">{{ $lapangan->nama }}</option>
                @endforeach
            </select>
        </div>

        <x-button type="button" x-on:click="$wire.bukaFormGenerate()">
            <x-icon name="plus" size="16" class="text-white" />
            Generate Slot
        </x-button>
    </div>

    @if ($pesanGenerate)
        <div class="mb-5 rounded-lg border border-green/30 bg-green-pale px-4 py-3 text-sm text-green">
            {{ $pesanGenerate }}
        </div>
    @endif

    @if ($pesanError)
        <div class="mb-5 rounded-lg border border-danger/30 bg-danger-pale px-4 py-3 text-sm text-danger">
            {{ $pesanError }}
        </div>
    @endif

    <x-card class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <button type="button" wire:click="mingguSebelumnya" class="p-2 rounded-lg hover:bg-cream text-ink-mid">
                <x-icon name="chevron-left" size="18" />
            </button>
            <span class="text-sm font-bold text-ink">
                Minggu {{ \Carbon\Carbon::parse($mingguAwal)->translatedFormat('d M') }}, {{ \Carbon\Carbon::parse($mingguAwal)->addDays(6)->translatedFormat('d M Y') }}
            </span>
            <button type="button" wire:click="mingguBerikutnya" class="p-2 rounded-lg hover:bg-cream text-ink-mid">
                <x-icon name="chevron-right" size="18" />
            </button>
        </div>

        <div class="grid grid-cols-7 gap-2">
            @foreach ($ringkasanMingguan as $hari)
                <button
                    type="button"
                    wire:click="pilihTanggal('{{ $hari['tanggal'] }}')"
                    wire:key="hari-{{ $hari['tanggal'] }}"
                    class="rounded-lg border px-2 py-3 text-center transition-colors {{ $tanggalDipilih === $hari['tanggal'] ? 'border-green bg-green-pale' : 'border-cream-deep hover:border-sand' }}"
                >
                    <div class="text-[0.65rem] uppercase tracking-wide text-ink-soft">{{ $hari['label'] }}</div>
                    <div class="font-display font-semibold text-ink text-sm">{{ $hari['tanggalPendek'] }}</div>
                    <div class="text-[0.65rem] text-ink-soft mt-1">{{ $hari['terisi'] }}/{{ $hari['total'] }} terisi</div>
                </button>
            @endforeach
        </div>
    </x-card>

    <x-card class="!p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-cream-dark">
            <div class="text-xs font-bold uppercase tracking-wide text-green">
                Slot {{ $tanggalDipilih ? \Carbon\Carbon::parse($tanggalDipilih)->translatedFormat('d F Y') : '' }}
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-cream text-left text-[0.7rem] uppercase tracking-wide text-ink-soft">
                        <th class="px-5 py-2.5 font-bold">Jam</th>
                        @if (! $lapanganId)
                            <th class="px-5 py-2.5 font-bold">Lapangan</th>
                        @endif
                        <th class="px-5 py-2.5 font-bold">Harga</th>
                        <th class="px-5 py-2.5 font-bold">Status</th>
                        <th class="px-5 py-2.5 font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($slotTanggalDipilih as $slot)
                        @php
                            $tipeBadge = match ($slot->status) {
                                'kosong' => 'confirmed',
                                'hold' => 'pending',
                                'booked' => 'info',
                                'nonaktif' => 'cancelled',
                                default => 'info',
                            };
                        @endphp
                        <tr wire:key="slot-{{ $slot->id }}" class="border-b border-cream last:border-0">
                            <td class="px-5 py-3">{{ substr($slot->jam_mulai, 0, 5) }} - {{ substr($slot->jam_selesai, 0, 5) }}</td>
                            @if (! $lapanganId)
                                <td class="px-5 py-3">{{ $slot->lapangan->nama }}</td>
                            @endif
                            <td class="px-5 py-3">
                                @if ($slotDiedit === $slot->id)
                                    <form wire:submit="simpanHargaSlot" class="flex items-center gap-1.5">
                                        <input
                                            type="number"
                                            wire:model="editHarga"
                                            class="w-28 rounded-lg border border-cream-deep bg-cream px-2 py-1.5 text-sm text-ink"
                                        />
                                        <button type="submit" class="text-green text-xs font-bold">Simpan</button>
                                        <button type="button" wire:click="tutupEditSlot" class="text-ink-soft text-xs">Batal</button>
                                    </form>
                                @else
                                    Rp{{ number_format($slot->harga, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="px-5 py-3"><x-badge :type="$tipeBadge">{{ ucfirst($slot->status) }}</x-badge></td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1.5">
                                    @if ($slotDiedit !== $slot->id)
                                        <button type="button" wire:click="bukaEditSlot({{ $slot->id }})" class="inline-flex items-center gap-1 rounded-lg font-sans font-semibold text-xs px-2.5 py-1.5 border-2 border-sand text-ink-mid hover:border-brown-light">
                                            <x-icon name="pencil-edit" size="13" />
                                            Harga
                                        </button>
                                    @endif

                                    @if (in_array($slot->status, ['kosong', 'nonaktif'], true))
                                        <button
                                            type="button"
                                            wire:click="toggleNonaktif({{ $slot->id }})"
                                            class="inline-flex items-center gap-1 rounded-lg font-sans font-semibold text-xs px-2.5 py-1.5 {{ $slot->status === 'nonaktif' ? 'bg-green/10 text-green' : 'bg-danger/10 text-danger' }}"
                                        >
                                            {{ $slot->status === 'nonaktif' ? 'Aktifkan' : 'Nonaktifkan' }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-ink-soft">Belum ada slot di tanggal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($tampilkanGenerateForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-5">
            <x-card class="max-w-md w-full" x-on:click.outside="$wire.tutupFormGenerate()">
                <div class="flex items-center justify-between mb-4">
                    <div class="font-display text-lg font-semibold text-ink">Generate Slot</div>
                    <button type="button" wire:click="tutupFormGenerate" class="text-ink-soft">
                        <x-icon name="x-close" size="18" />
                    </button>
                </div>

                <form wire:submit="generateSlot" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Lapangan</label>
                        <select wire:model="genLapanganId" class="w-full rounded-lg border border-cream-deep bg-cream px-3 py-2.5 text-sm text-ink">
                            @foreach ($daftarLapangan as $lapangan)
                                <option value="{{ $lapangan->id }}">{{ $lapangan->nama }}</option>
                            @endforeach
                        </select>
                        @error('genLapanganId') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Dari Tanggal</label>
                            <input type="date" wire:model="genTanggalMulai" class="w-full rounded-lg border border-cream-deep bg-cream px-3 py-2.5 text-sm text-ink" />
                            @error('genTanggalMulai') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Sampai Tanggal</label>
                            <input type="date" wire:model="genTanggalSelesai" class="w-full rounded-lg border border-cream-deep bg-cream px-3 py-2.5 text-sm text-ink" />
                            @error('genTanggalSelesai') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Interval per Slot (jam)</label>
                        <select wire:model="genIntervalJam" class="w-full rounded-lg border border-cream-deep bg-cream px-3 py-2.5 text-sm text-ink">
                            <option value="0.5">30 menit</option>
                            <option value="1">1 jam</option>
                            <option value="1.5">1,5 jam</option>
                            <option value="2">2 jam</option>
                        </select>
                        @error('genIntervalJam') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-input label="Harga per Slot (Rp)" name="genHarga" type="number" wire:model="genHarga" />
                        @error('genHarga') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <p class="text-xs text-ink-soft">
                        Slot dibuat mengikuti jam buka dan jam tutup cabang lapangan yang dipilih.
                    </p>

                    <div class="flex gap-2 pt-1">
                        <x-button type="submit" class="flex-1 justify-center">Generate</x-button>
                        <x-button type="button" variant="secondary" class="flex-1 justify-center" wire:click="tutupFormGenerate">Batal</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    @endif
</div>
