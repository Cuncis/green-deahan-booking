<div wire:poll.30s>
    @if ($daftarCabang->isNotEmpty())
        <div class="mb-4">
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Filter Cabang</label>
            <select wire:model.live="cabangId" class="block w-full max-w-xs rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink">
                <option value="">Semua Cabang</option>
                @foreach ($daftarCabang as $cabang)
                    <option value="{{ $cabang->id }}">{{ $cabang->nama_cabang }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">
        <x-card>
            <div class="text-xs text-ink-soft mb-1.5">Booking Hari Ini</div>
            <div class="font-display text-2xl font-semibold text-ink">{{ $bookingHariIni }}</div>
        </x-card>
        <x-card>
            <div class="text-xs text-ink-soft mb-1.5">Menunggu Konfirmasi</div>
            <div class="font-display text-2xl font-semibold text-ink">{{ $menungguKonfirmasi }}</div>
        </x-card>
        <x-card>
            <div class="text-xs text-ink-soft mb-1.5">Booking Minggu Ini</div>
            <div class="font-display text-2xl font-semibold text-ink">{{ $bookingMingguIni }}</div>
        </x-card>
        <x-card>
            <div class="text-xs text-ink-soft mb-1.5">Tingkat Keterisian</div>
            <div class="font-display text-2xl font-semibold text-ink">{{ $tingkatKeterisian }}%</div>
        </x-card>
    </div>

    @if (! empty($pendapatanMingguan))
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
    @endif

    <x-card class="!p-0 overflow-hidden">
        <div class="px-5 py-4 border-b border-cream-dark">
            <div class="text-xs font-bold uppercase tracking-wide text-green">Booking Terbaru</div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-cream text-left text-[0.7rem] uppercase tracking-wide text-ink-soft">
                        <th class="px-5 py-2.5 font-bold">Customer</th>
                        <th class="px-5 py-2.5 font-bold">Lapangan</th>
                        <th class="px-5 py-2.5 font-bold">Jadwal</th>
                        <th class="px-5 py-2.5 font-bold">Status</th>
                        <th class="px-5 py-2.5 font-bold">Total</th>
                        <th class="px-5 py-2.5 font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookingTerbaru as $booking)
                        @php
                            $tipeBadge = match ($booking->status_booking) {
                                'menunggu' => 'pending',
                                'dikonfirmasi', 'selesai' => 'confirmed',
                                'dibatalkan' => 'cancelled',
                                default => 'info',
                            };
                        @endphp
                        <tr wire:key="booking-{{ $booking->id }}" class="border-b border-cream last:border-0">
                            <td class="px-5 py-3">
                                <div class="font-bold text-ink">{{ $booking->customer->nama }}</div>
                                <div class="text-xs text-ink-soft">{{ $booking->customer->no_telepon }}</div>
                            </td>
                            <td class="px-5 py-3">{{ $booking->slot->lapangan->nama }}</td>
                            <td class="px-5 py-3">{{ $booking->slot->tanggal->format('d/m/Y') }}, {{ substr($booking->slot->jam_mulai, 0, 5) }}</td>
                            <td class="px-5 py-3"><x-badge :type="$tipeBadge">{{ ucfirst($booking->status_booking) }}</x-badge></td>
                            <td class="px-5 py-3">Rp{{ number_format($booking->total_bayar, 0, ',', '.') }}</td>
                            <td class="px-5 py-3">
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->customer->no_telepon) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 bg-green text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold">
                                    <x-icon name="wa-chat" size="14" class="text-white" />
                                    Chat
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-6 text-center text-ink-soft">Belum ada booking.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
