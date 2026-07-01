@php
    $maxNilai = max(array_merge(array_values($pendapatanMingguan), [1]));
    $namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
@endphp

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
