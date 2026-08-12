@php
    $maxNilai = max(array_merge(array_values($pendapatanMingguan), [1]));
    $namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    $maxJamRamai = max(array_merge(array_values($jamRamai), [1]));
@endphp

<div>
    @if ($daftarCabang->isNotEmpty())
        <div class="flex items-center justify-between gap-3 mb-6 bg-white border border-cream-deep rounded-card px-4 py-3">
            <span class="text-xs font-bold uppercase tracking-wide text-ink-soft">Filter Cabang</span>
            <select wire:model.live="cabangId" class="rounded-lg border border-cream-deep bg-cream px-3 py-2 text-sm text-ink">
                <option value="">Semua Cabang</option>
                @foreach ($daftarCabang as $cabang)
                    <option value="{{ $cabang->id }}">{{ $cabang->nama_cabang }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <x-card class="mb-6">
        <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Pendapatan 7 Hari Terakhir</div>

        <div class="flex items-end gap-2.5 h-40">
            @foreach ($pendapatanMingguan as $tanggal => $nilai)
                @php
                    $tinggi = $nilai > 0 ? max(6, round($nilai / $maxNilai * 100)) : 4;
                    $hari = \Illuminate\Support\Carbon::parse($tanggal);
                @endphp
                <div class="flex-1 flex flex-col items-center justify-end h-full gap-1.5" wire:key="chart-{{ $tanggal }}">
                    <span class="text-[0.62rem] text-ink-soft">Rp{{ number_format($nilai / 1000, 0, ',', '.') }}rb</span>
                    <div class="w-full max-w-[34px] rounded-t-md {{ $nilai === $maxNilai && $nilai > 0 ? 'bg-green' : 'bg-green-pale' }}" style="height: {{ $tinggi }}%"></div>
                    <span class="text-[0.68rem] text-ink-soft">{{ $namaHari[$hari->dayOfWeek] }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-4 pt-4 border-t border-cream-deep text-sm text-ink-mid">
            Total pendapatan 7 hari terakhir, Rp{{ number_format(array_sum($pendapatanMingguan), 0, ',', '.') }}.
        </div>
    </x-card>

    <x-card class="mb-6">
        <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Jam Ramai</div>
        <div class="flex gap-1">
            @foreach ($jamRamai as $jam => $jumlah)
                @php $intensitas = $jumlah > 0 ? max($jumlah / $maxJamRamai, 0.12) : 0.05; @endphp
                <div class="flex-1 flex flex-col items-center gap-1" wire:key="jam-{{ $jam }}">
                    <div
                        class="w-full h-8 rounded"
                        style="background-color: rgba(58, 107, 74, {{ $intensitas }})"
                        title="Jam {{ str_pad((string) $jam, 2, '0', STR_PAD_LEFT) }}.00, {{ $jumlah }} booking"
                    ></div>
                    @if ($jam % 3 === 0)
                        <span class="text-[0.6rem] text-ink-soft">{{ str_pad((string) $jam, 2, '0', STR_PAD_LEFT) }}</span>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="text-xs text-ink-soft mt-3">Semakin gelap warnanya, semakin ramai jam tersebut dibooking.</p>
    </x-card>
</div>
