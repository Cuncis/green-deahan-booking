<x-card class="mb-6 !p-0 overflow-hidden">
    <div class="px-5 py-4 border-b border-cream-dark flex items-center justify-between gap-3">
        <div class="text-xs font-bold uppercase tracking-wide text-green">Kode Promo</div>
        <x-button type="button" variant="secondary" wire:click="bukaForm">Tambah Kode Baru</x-button>
    </div>

    @if ($tampilkanForm)
        <form wire:submit="simpan" class="px-5 py-4 border-b border-cream-dark bg-cream space-y-3.5">
            @if ($pesanError)
                <p class="text-sm text-danger">{{ $pesanError }}</p>
            @endif

            <div class="grid grid-cols-2 gap-3.5">
                <x-input label="Kode" name="kode" placeholder="Misal SEPI20" wire:model="kode" />
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Tipe Diskon</label>
                    <select wire:model="tipeDiskon" class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
                        <option value="persen">Persen</option>
                        <option value="nominal">Nominal</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3.5">
                <x-input label="Nilai" name="nilai" type="number" placeholder="10" wire:model="nilai" />
                <x-input label="Tanggal Mulai" name="tanggal_mulai" type="date" wire:model="tanggalMulai" />
                <x-input label="Tanggal Berakhir" name="tanggal_berakhir" type="date" wire:model="tanggalBerakhir" />
            </div>

            <x-input label="Kuota (kosongkan kalau tidak terbatas)" name="kuota" type="number" placeholder="Tidak terbatas" wire:model="kuota" />

            @error('kode') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            @error('nilai') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            @error('tanggalBerakhir') <p class="text-sm text-danger">{{ $message }}</p> @enderror

            <div class="flex gap-2">
                <x-button type="submit">Simpan Kode</x-button>
                <x-button type="button" variant="secondary" wire:click="tutupForm">Batal</x-button>
            </div>
        </form>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-cream text-left text-[0.7rem] uppercase tracking-wide text-ink-soft">
                    <th class="px-5 py-2.5 font-bold">Kode</th>
                    <th class="px-5 py-2.5 font-bold">Diskon</th>
                    <th class="px-5 py-2.5 font-bold">Periode</th>
                    <th class="px-5 py-2.5 font-bold">Kuota</th>
                    <th class="px-5 py-2.5 font-bold">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarKodePromo as $promo)
                    <tr wire:key="promo-{{ $promo->id }}" class="border-b border-cream last:border-0">
                        <td class="px-5 py-3 font-bold text-ink">{{ $promo->kode }}</td>
                        <td class="px-5 py-3">{{ $promo->tipe_diskon === 'persen' ? $promo->nilai.'%' : 'Rp'.number_format($promo->nilai, 0, ',', '.') }}</td>
                        <td class="px-5 py-3">{{ $promo->tanggal_mulai->format('d/m/Y') }} sampai {{ $promo->tanggal_berakhir->format('d/m/Y') }}</td>
                        <td class="px-5 py-3">{{ $promo->kuota ?? 'Tidak terbatas' }}</td>
                        <td class="px-5 py-3"><x-badge :type="$promo->status_aktif ? 'confirmed' : 'info'">{{ $promo->status_aktif ? 'Aktif' : 'Nonaktif' }}</x-badge></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-6 text-center text-ink-soft">Belum ada kode promo.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
