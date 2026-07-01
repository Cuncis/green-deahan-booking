<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $tenant->nama_bisnis }}, Booking Lapangan</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-cream text-ink">

        <nav class="sticky top-0 z-40 flex items-center justify-between h-16 px-5 border-b border-cream-deep bg-cream/95 backdrop-blur">
            <div class="flex items-center gap-2 font-display font-semibold text-green">
                {{ $tenant->nama_bisnis }}
                @if ($tenant->paket !== 'basic')
                    <span class="text-[0.62rem] font-extrabold uppercase tracking-wide px-2 py-0.5 rounded-full text-white {{ $tenant->paket === 'premium' ? 'bg-plum' : 'bg-gold' }}">
                        {{ $tenant->paket }}
                    </span>
                @endif
            </div>

            @if ($tenant->whatsapp_admin)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $tenant->whatsapp_admin) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 bg-green text-white px-4 py-2 rounded-lg text-sm font-semibold">
                    <x-icon name="wa-chat" size="16" class="text-white" />
                    Hubungi Kami
                </a>
            @endif
        </nav>

        @if ($lapanganAktif)
            @php
                $iconLapangan = match (true) {
                    str_contains(strtolower($lapanganAktif->jenis_olahraga), 'futsal') => 'futsal-goal',
                    str_contains(strtolower($lapanganAktif->jenis_olahraga), 'padel') => 'padel-racket',
                    str_contains(strtolower($lapanganAktif->jenis_olahraga), 'badminton') => 'shuttlecock',
                    str_contains(strtolower($lapanganAktif->jenis_olahraga), 'tennis') => 'tennis-racket',
                    default => 'futsal-goal',
                };
            @endphp

            <div class="max-w-4xl mx-auto px-5 pt-6 flex gap-2 overflow-x-auto">
                @foreach ($lapangan as $item)
                    <a href="{{ route('booking.index', ['lapangan' => $item->id]) }}"
                       wire:navigate
                       class="flex-none inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-semibold whitespace-nowrap transition-colors {{ $item->id === $lapanganAktif->id ? 'border-green bg-green-pale text-green' : 'border-cream-deep bg-white text-ink-mid' }}">
                        {{ $item->nama }}
                    </a>
                @endforeach
            </div>

            <div class="max-w-4xl mx-auto px-5 pt-4">
                <div class="w-full h-56 rounded-card bg-gradient-to-br from-green-pale to-cream-deep flex items-center justify-center mb-4">
                    <x-icon :name="$iconLapangan" size="84" class="text-green opacity-50" />
                </div>

                <h1 class="font-display text-2xl font-semibold text-ink mb-1">{{ $lapanganAktif->nama }}</h1>

                <div class="flex flex-wrap gap-4 text-sm text-ink-mid mb-2">
                    @if ($lapanganAktif->cabang)
                        <span class="inline-flex items-center gap-1.5">
                            <x-icon name="location-pin" size="16" />
                            {{ $lapanganAktif->cabang->alamat }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <x-icon name="clock" size="16" />
                            Buka {{ substr($lapanganAktif->cabang->jam_buka, 0, 5) }} sampai {{ substr($lapanganAktif->cabang->jam_tutup, 0, 5) }} WIB
                        </span>
                    @endif
                </div>

                <div class="text-lg font-bold text-green">
                    Rp{{ number_format($lapanganAktif->harga_per_jam, 0, ',', '.') }}
                    <span class="text-xs font-normal text-ink-soft">per jam</span>
                </div>

                @if ($tenant->punyaFitur('sistem_membership'))
                    <div class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-gold">
                        <x-icon name="crown" size="14" class="text-gold" />
                        Member dapat harga dan promo khusus
                    </div>
                @endif
            </div>

            <div
                x-data="{
                    slot: null,
                    tanggal: null,
                    jamMulai: null,
                    jamSelesai: null,
                    harga: 0,
                    kodePromo: '',
                    promoDiterapkan: false,
                    tipePembayaran: '{{ $tenant->punyaFitur('dp_pembayaran') ? 'dp' : ($tenant->punyaFitur('pembayaran_online') ? 'lunas' : 'manual') }}',
                    nama: '',
                    whatsapp: '',
                    mengirim: false,
                    hasil: null,
                    errorPesan: null,
                    onSlotDipilih(detail) {
                        this.slot = detail.slotId;
                        this.tanggal = detail.tanggal;
                        this.jamMulai = detail.jamMulai;
                        this.jamSelesai = detail.jamSelesai;
                        this.harga = detail.harga;
                        this.errorPesan = null;
                    },
                    get tanggalLabel() {
                        if (!this.tanggal) return 'Belum dipilih';
                        const d = new Date(this.tanggal + 'T00:00:00');
                        return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' });
                    },
                    get jamLabel() {
                        if (!this.jamMulai) return 'Belum dipilih';
                        return this.jamMulai.substring(0, 5) + ' sampai ' + this.jamSelesai.substring(0, 5);
                    },
                    get totalBayar() {
                        return this.tipePembayaran === 'dp' ? Math.round(this.harga / 2) : this.harga;
                    },
                    formatRupiah(v) {
                        return 'Rp' + Math.round(v || 0).toLocaleString('id-ID');
                    },
                    kirimBooking() {
                        if (!this.slot || !this.nama || !this.whatsapp) {
                            this.errorPesan = 'Mohon lengkapi nama, nomor WhatsApp, dan pilih jam terlebih dahulu.';
                            return;
                        }
                        this.mengirim = true;
                        this.errorPesan = null;
                        fetch('{{ route('booking.buat') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({
                                slot_id: this.slot,
                                nama: this.nama,
                                whatsapp: this.whatsapp,
                                kode_promo: this.kodePromo || null,
                                tipe_pembayaran: this.tipePembayaran,
                            }),
                        })
                        .then(async (res) => {
                            const data = await res.json();
                            if (!res.ok) {
                                this.errorPesan = data.message || 'Booking gagal dibuat, silakan coba lagi.';
                                return;
                            }
                            this.hasil = data.data;
                        })
                        .catch(() => {
                            this.errorPesan = 'Booking gagal dibuat, silakan coba lagi.';
                        })
                        .finally(() => { this.mengirim = false; });
                    },
                    get waLink() {
                        if (!this.hasil) return '#';
                        const admin = '{{ $tenant->whatsapp_admin ? preg_replace('/[^0-9]/', '', $tenant->whatsapp_admin) : '' }}';
                        const pesan = 'Halo, saya sudah booking di {{ $tenant->nama_bisnis }}.\n\nKode booking: ' + this.hasil.kode_booking + '\nMohon info langkah pembayaran selanjutnya. Terima kasih.';
                        return 'https://wa.me/' + admin + '?text=' + encodeURIComponent(pesan);
                    },
                }"
                x-on:slot-dipilih.window="onSlotDipilih($event.detail)"
                class="max-w-4xl mx-auto px-5 py-6 grid gap-5 items-start lg:grid-cols-[1.4fr_1fr]"
            >
                <x-card>
                    <livewire:kalender-booking :lapangan-id="$lapanganAktif->id" :key="'kalender-'.$lapanganAktif->id" />
                </x-card>

                <x-card>
                    <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Ringkasan Booking</div>

                    <div class="flex justify-between py-2 border-b border-dashed border-cream-deep text-sm">
                        <span class="text-ink-soft">Lapangan</span>
                        <span class="font-semibold">{{ $lapanganAktif->nama }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-dashed border-cream-deep text-sm">
                        <span class="text-ink-soft">Tanggal</span>
                        <span class="font-semibold" x-text="tanggalLabel"></span>
                    </div>
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-ink-soft">Jam</span>
                        <span class="font-semibold" x-text="jamLabel"></span>
                    </div>

                    <div class="flex justify-between mt-3 pt-3 border-t-2 border-cream-deep text-base font-bold text-green">
                        <span>Total Bayar</span>
                        <span x-text="formatRupiah(totalBayar)"></span>
                    </div>

                    @if ($tenant->punyaFitur('kode_promo'))
                        <div class="flex gap-2 mt-4">
                            <div class="flex-1">
                                <x-input name="kode_promo" placeholder="Kode promo, misal SEPI20" x-model="kodePromo" />
                            </div>
                            <x-button type="button" variant="secondary" @click="promoDiterapkan = true">Pakai</x-button>
                        </div>
                        <p class="text-xs text-ink-soft mt-1" x-show="promoDiterapkan" x-cloak>
                            Kode promo akan dicek ulang saat booking diproses.
                        </p>
                    @endif

                    @if ($tenant->punyaFitur('dp_pembayaran'))
                        <div class="text-xs font-bold uppercase tracking-wide text-green mt-5 mb-3">Pilihan Bayar</div>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="tipePembayaran = 'dp'"
                                :class="tipePembayaran === 'dp' ? 'border-green bg-green-pale text-green' : 'border-cream-deep text-ink-mid'"
                                class="rounded-lg border px-3 py-2.5 text-center text-sm font-semibold transition-colors">
                                Bayar DP 50%
                                <span class="block text-xs font-normal opacity-80 mt-0.5" x-text="formatRupiah(Math.round(harga / 2))"></span>
                            </button>
                            <button type="button" @click="tipePembayaran = 'lunas'"
                                :class="tipePembayaran === 'lunas' ? 'border-green bg-green-pale text-green' : 'border-cream-deep text-ink-mid'"
                                class="rounded-lg border px-3 py-2.5 text-center text-sm font-semibold transition-colors">
                                Bayar Lunas
                                <span class="block text-xs font-normal opacity-80 mt-0.5">Langsung beres</span>
                            </button>
                        </div>
                    @endif

                    <div class="mt-5 space-y-3.5">
                        <x-input label="Nama Lengkap" name="nama" placeholder="Masukkan nama kamu" x-model="nama" />
                        <x-input label="Nomor WhatsApp" name="whatsapp" type="tel" placeholder="08xxxxxxxxxx" x-model="whatsapp" />
                    </div>

                    <p class="text-sm text-danger mt-3" x-show="errorPesan" x-text="errorPesan" x-cloak></p>

                    <x-button
                        type="button"
                        class="w-full mt-2 justify-center"
                        x-bind:disabled="!slot || mengirim"
                        x-bind:class="(!slot || mengirim) ? 'opacity-50 cursor-not-allowed' : ''"
                        @click="kirimBooking()"
                    >
                        <span x-show="!slot">Pilih jam terlebih dahulu</span>
                        <span x-show="slot && !mengirim" x-text="'Booking Sekarang, ' + formatRupiah(totalBayar)"></span>
                        <span x-show="mengirim" x-cloak>Memproses...</span>
                    </x-button>

                    <div class="flex items-center justify-center gap-1.5 mt-3 text-xs text-amber">
                        <x-icon name="clock" size="14" class="text-amber" />
                        Slot ditahan 10 menit setelah dipilih
                    </div>
                </x-card>

                <div x-show="hasil" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-5">
                    <x-card class="max-w-sm w-full text-center">
                        <div class="w-14 h-14 rounded-full bg-green-pale flex items-center justify-center mx-auto mb-4">
                            <x-icon name="check-circle" size="28" class="text-green" />
                        </div>
                        <h3 class="font-display text-xl font-semibold text-ink mb-2">Booking Berhasil Dibuat</h3>
                        <p class="text-sm text-ink-mid mb-4">Slot kamu sudah diamankan. Lanjutkan pembayaran supaya booking dikonfirmasi.</p>
                        <div class="bg-cream rounded-lg p-3 text-sm text-ink-soft text-left mb-4">
                            <div class="flex justify-between py-0.5"><span>Kode</span><strong class="text-ink" x-text="hasil?.kode_booking"></strong></div>
                            <div class="flex justify-between py-0.5"><span>Total</span><strong class="text-ink" x-text="formatRupiah(hasil?.total_bayar)"></strong></div>
                        </div>
                        <a :href="waLink" target="_blank" class="flex items-center justify-center gap-2 bg-green text-white rounded-lg py-3 font-bold text-sm mb-2">
                            <x-icon name="wa-chat" size="18" class="text-white" />
                            Buka Chat WhatsApp
                        </a>
                        <button type="button" class="text-sm text-ink-soft underline" @click="hasil = null">Tutup</button>
                    </x-card>
                </div>
            </div>
        @else
            <div class="max-w-xl mx-auto px-5 py-16 text-center">
                <p class="text-ink-mid">Belum ada lapangan tersedia untuk booking saat ini.</p>
            </div>
        @endif

        <footer class="text-center py-6 px-5 text-sm text-ink-soft border-t border-cream-deep">
            <strong class="text-green">{{ $tenant->nama_bisnis }}</strong>
            @if ($tenant->whatsapp_admin)
                , {{ $tenant->whatsapp_admin }}
            @endif
        </footer>

        @livewireScripts
    </body>
</html>
