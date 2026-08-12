<div wire:poll.30s>
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

    <div class="grid grid-cols-2 {{ $tenant->punyaFitur('laporan_pendapatan') ? 'lg:grid-cols-4' : '' }} gap-3.5 mb-6">
        <x-card>
            <div class="text-xs text-ink-soft mb-1.5">Booking Hari Ini</div>
            <div class="font-display text-2xl font-semibold text-ink">{{ $bookingHariIni }}</div>
        </x-card>
        <x-card>
            <div class="text-xs text-ink-soft mb-1.5">Menunggu Konfirmasi</div>
            <div class="font-display text-2xl font-semibold text-ink">{{ $menungguKonfirmasi }}</div>
        </x-card>

        @if ($tenant->punyaFitur('laporan_pendapatan'))
            <x-card>
                <div class="text-xs text-ink-soft mb-1.5">Booking Minggu Ini</div>
                <div class="font-display text-2xl font-semibold text-ink">{{ $bookingMingguIni }}</div>
            </x-card>
            <x-card>
                <div class="text-xs text-ink-soft mb-1.5">Tingkat Keterisian</div>
                <div class="font-display text-2xl font-semibold text-ink">{{ $tingkatKeterisian }}%</div>
            </x-card>
        @endif
    </div>

    <x-card class="mb-6 !p-0 overflow-hidden">
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
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->customer->no_telepon) }}"
                                       onclick="bukaChatWhatsApp(this.href); return false;"
                                       class="inline-flex items-center gap-1.5 bg-green text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold">
                                        <x-icon name="wa-chat" size="14" class="text-white" />
                                        Chat
                                    </a>

                                    @if (in_array($booking->status_booking, ['menunggu', 'dikonfirmasi'], true))
                                        <div x-data="{ batalkanTerbuka: false }" class="flex flex-wrap items-center gap-1.5">
                                            @if ($booking->status_booking === 'menunggu')
                                                <form method="POST" action="{{ route('admin.booking.confirm', $booking) }}">
                                                    @csrf
                                                    <x-button type="submit" class="!px-2.5 !py-1.5 !text-xs">Konfirmasi</x-button>
                                                </form>
                                            @endif

                                            <x-button
                                                type="button"
                                                variant="danger"
                                                class="!px-2.5 !py-1.5 !text-xs"
                                                x-on:click="batalkanTerbuka = true"
                                            >Batalkan</x-button>

                                            <div
                                                x-show="batalkanTerbuka"
                                                x-cloak
                                                class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-5"
                                            >
                                                <x-card class="max-w-sm w-full" x-on:click.outside="batalkanTerbuka = false">
                                                    <div class="font-display text-lg font-semibold text-ink mb-2">Batalkan Booking</div>
                                                    <p class="text-sm text-ink-mid mb-3">
                                                        Booking {{ $booking->kode_booking }} akan dibatalkan, slot akan kembali kosong.
                                                    </p>
                                                    <form method="POST" action="{{ route('admin.booking.cancel', $booking) }}">
                                                        @csrf
                                                        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">
                                                            Alasan pembatalan (opsional)
                                                        </label>
                                                        <textarea
                                                            name="alasan"
                                                            rows="3"
                                                            class="w-full rounded-lg border border-cream-deep bg-cream px-3 py-2 text-sm text-ink mb-3"
                                                            placeholder="Misal, customer minta ganti jadwal"
                                                        ></textarea>
                                                        <div class="flex gap-2">
                                                            <x-button type="submit" variant="danger" class="flex-1 justify-center">Ya, Batalkan</x-button>
                                                            <x-button
                                                                type="button"
                                                                variant="secondary"
                                                                class="flex-1 justify-center"
                                                                x-on:click="batalkanTerbuka = false"
                                                            >Tutup</x-button>
                                                        </div>
                                                    </form>
                                                </x-card>
                                            </div>
                                        </div>
                                    @endif
                                </div>
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

    @if ($tenant->punyaFitur('analitik_lanjutan'))
        <x-card id="analitik" class="mb-6 !p-0 overflow-hidden">
            <div class="px-5 py-4 border-b border-cream-dark">
                <div class="text-xs font-bold uppercase tracking-wide text-green">Perbandingan Performa Cabang</div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-cream text-left text-[0.7rem] uppercase tracking-wide text-ink-soft">
                            <th class="px-5 py-2.5 font-bold">Ranking</th>
                            <th class="px-5 py-2.5 font-bold">Cabang</th>
                            <th class="px-5 py-2.5 font-bold">Booking</th>
                            <th class="px-5 py-2.5 font-bold">Pendapatan</th>
                            <th class="px-5 py-2.5 font-bold">Keterisian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($perbandinganCabang as $index => $item)
                            <tr wire:key="cabang-rank-{{ $item['cabang']->id }}" class="border-b border-cream last:border-0">
                                <td class="px-5 py-3 font-bold text-ink">#{{ $index + 1 }}</td>
                                <td class="px-5 py-3">{{ $item['cabang']->nama_cabang }}</td>
                                <td class="px-5 py-3">{{ $item['totalBooking'] }}</td>
                                <td class="px-5 py-3">Rp{{ number_format($item['pendapatan'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3">{{ $item['keterisian'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-6 text-center text-ink-soft">Belum ada cabang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif

    @if ($tenant->punyaFitur('manajemen_staf'))
        <div id="staf">
            <livewire:staf-manager />
        </div>
    @endif

    @if ($tenant->punyaFitur('sistem_membership'))
        <x-card id="membership" class="mb-6">
            <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Membership Tiers</div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($ringkasanMembership as $tier)
                    <div class="rounded-lg border border-cream-deep p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <x-icon name="crown" size="16" class="text-gold" />
                            <span class="font-bold text-ink">{{ $tier['label'] }}</span>
                        </div>
                        <div class="font-display text-xl font-semibold text-ink">{{ $tier['jumlah'] }}</div>
                        <div class="text-xs text-ink-soft">member, diskon {{ $tier['persen'] }}%</div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    @if ($tenant->punyaFitur('reminder_otomatis'))
        <x-card id="reminder" class="mb-6 !p-0 overflow-hidden">
            <div class="px-5 py-4 border-b border-cream-dark">
                <div class="text-xs font-bold uppercase tracking-wide text-green">Reminder Otomatis</div>
            </div>
            <div class="overflow-x-auto">
                @php
                    $statusBadge = [
                        'perlu_dikirim' => 'pending',
                        'terjadwal' => 'info',
                        'terkirim' => 'confirmed',
                        'gagal' => 'cancelled',
                    ];
                    $statusLabel = [
                        'perlu_dikirim' => 'Perlu Dikirim',
                        'terjadwal' => 'Terjadwal',
                        'terkirim' => 'Terkirim',
                        'gagal' => 'Gagal',
                    ];
                @endphp
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-cream text-left text-[0.7rem] uppercase tracking-wide text-ink-soft">
                            <th class="px-5 py-2.5 font-bold">Customer</th>
                            <th class="px-5 py-2.5 font-bold">Lapangan &amp; Jadwal</th>
                            <th class="px-5 py-2.5 font-bold">Status</th>
                            <th class="px-5 py-2.5 font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftarReminder as $index => $reminder)
                            <tr wire:key="reminder-{{ $index }}" class="border-b border-cream last:border-0">
                                <td class="px-5 py-3">{{ $reminder['nama'] }}</td>
                                <td class="px-5 py-3">{{ $reminder['lapangan'] }}, {{ $reminder['waktu'] }}</td>
                                <td class="px-5 py-3">
                                    <x-badge :type="$statusBadge[$reminder['status']] ?? 'info'">
                                        {{ $statusLabel[$reminder['status']] ?? ucfirst($reminder['status']) }}
                                    </x-badge>
                                </td>
                                <td class="px-5 py-3">
                                    @if ($reminder['status'] === 'perlu_dikirim')
                                        <a
                                            href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $reminder['no_telepon']) }}?text={{ urlencode($reminder['pesan']) }}"
                                            x-on:click.prevent="bukaChatWhatsApp($el.href); $wire.tandaiTerkirim({{ $reminder['id'] }})"
                                            class="inline-flex items-center gap-1.5 bg-green text-white px-2.5 py-1.5 rounded-lg text-xs font-semibold"
                                        >
                                            <x-icon name="wa-chat" size="14" class="text-white" />
                                            Kirim Sekarang
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-6 text-center text-ink-soft">Belum ada reminder.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</div>
