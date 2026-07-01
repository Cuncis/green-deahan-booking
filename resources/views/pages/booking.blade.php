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
            @if (! $tenant->punyaFitur('multi_cabang'))
                <div class="max-w-4xl mx-auto px-5 pt-6 flex gap-2 overflow-x-auto">
                    @foreach ($lapangan as $item)
                        <a href="{{ route('booking.index', ['lapangan' => $item->id]) }}"
                           wire:navigate
                           class="flex-none inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-semibold whitespace-nowrap transition-colors {{ $item->id === $lapanganAktif->id ? 'border-green bg-green-pale text-green' : 'border-cream-deep bg-white text-ink-mid' }}">
                            {{ $item->nama }}
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="max-w-4xl mx-auto px-5 pt-4">
                <div class="w-full h-56 rounded-card bg-gradient-to-br from-green-pale to-cream-deep flex items-center justify-center mb-4">
                    <div x-show="iconLapangan === 'futsal-goal'" x-cloak><x-icon name="futsal-goal" size="84" class="text-green opacity-50" /></div>
                    <div x-show="iconLapangan === 'padel-racket'" x-cloak><x-icon name="padel-racket" size="84" class="text-green opacity-50" /></div>
                    <div x-show="iconLapangan === 'shuttlecock'" x-cloak><x-icon name="shuttlecock" size="84" class="text-green opacity-50" /></div>
                    <div x-show="iconLapangan === 'tennis-racket'" x-cloak><x-icon name="tennis-racket" size="84" class="text-green opacity-50" /></div>
                </div>

                <h1 class="font-display text-2xl font-semibold text-ink mb-1" x-text="lapanganNama">{{ $lapanganAktif->nama }}</h1>

                <div class="flex flex-wrap gap-4 text-sm text-ink-mid mb-2" x-show="cabangAlamat" x-cloak>
                    <span class="inline-flex items-center gap-1.5">
                        <x-icon name="location-pin" size="16" />
                        <span x-text="cabangAlamat"></span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-icon name="clock" size="16" />
                        <span>Buka <span x-text="jamBuka"></span> sampai <span x-text="jamTutup"></span> WIB</span>
                    </span>
                </div>

                <div class="text-lg font-bold text-green">
                    <span x-text="formatRupiah(lapanganHarga)"></span>
                    <span class="text-xs font-normal text-ink-soft">per jam</span>
                </div>

                @if ($tenant->punyaFitur('sistem_membership'))
                    <div class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-gold" x-show="member" x-cloak>
                        <x-icon name="crown" size="14" class="text-gold" />
                        <span>
                            Member <span x-text="member?.tier"></span>, harga khusus kamu
                            <span x-text="formatRupiah(member?.harga_member)"></span> per jam
                        </span>
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
                    kodePromoAktif: null,
                    tipePembayaranAktif: '{{ $tenant->punyaFitur('dp_pembayaran') ? 'dp' : ($tenant->punyaFitur('pembayaran_online') ? 'lunas' : 'manual') }}',
                    diskonJumlah: 0,
                    totalBayar: 0,
                    nama: '',
                    whatsapp: '',
                    mengirim: false,
                    hasil: null,
                    errorPesan: null,
                    lapanganIdAktif: {{ $lapanganAktif->id }},
                    lapanganNama: @js($lapanganAktif->nama),
                    lapanganJenis: @js($lapanganAktif->jenis_olahraga),
                    lapanganHarga: {{ $lapanganAktif->harga_per_jam }},
                    cabangAlamat: @js($lapanganAktif->cabang?->alamat ?? ''),
                    jamBuka: @js($lapanganAktif->cabang ? substr($lapanganAktif->cabang->jam_buka, 0, 5) : ''),
                    jamTutup: @js($lapanganAktif->cabang ? substr($lapanganAktif->cabang->jam_tutup, 0, 5) : ''),
                    member: null,
                    memberTimer: null,
                    reminderAktif: true,
                    init() {
                        this.$watch('whatsapp', (value) => {
                            clearTimeout(this.memberTimer);
                            const digits = (value || '').replace(/\D/g, '');
                            if (digits.length < 10) {
                                this.member = null;
                                return;
                            }
                            this.memberTimer = setTimeout(() => this.cekMembership(digits), 600);
                        });
                    },
                    cekMembership(noTelepon) {
                        fetch('{{ route('booking.cek_membership') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                            body: JSON.stringify({ no_telepon: noTelepon, lapangan_id: this.lapanganIdAktif }),
                        })
                        .then((res) => res.json())
                        .then((json) => { this.member = json.data; })
                        .catch(() => { this.member = null; });
                    },
                    get iconLapangan() {
                        const jenis = (this.lapanganJenis || '').toLowerCase();
                        if (jenis.includes('padel')) return 'padel-racket';
                        if (jenis.includes('badminton')) return 'shuttlecock';
                        if (jenis.includes('tennis')) return 'tennis-racket';
                        return 'futsal-goal';
                    },
                    get linkTransferManual() {
                        const admin = '{{ $tenant->whatsapp_admin ? preg_replace('/[^0-9]/', '', $tenant->whatsapp_admin) : '' }}';
                        const pesan = 'Halo, saya mau konfirmasi booking di {{ $tenant->nama_bisnis }}.\n\n'
                            + 'Lapangan: ' + this.lapanganNama + '\n'
                            + 'Tanggal: ' + this.tanggalLabel + '\n'
                            + 'Jam: ' + this.jamLabel + '\n'
                            + 'Nama: ' + this.nama + '\n'
                            + 'Total Bayar: ' + this.formatRupiah(this.totalBayar) + '\n\n'
                            + 'Berikut saya lampirkan bukti transfer manualnya.';
                        return 'https://wa.me/' + admin + '?text=' + encodeURIComponent(pesan);
                    },
                    onLapanganDipilih(detail) {
                        this.lapanganIdAktif = detail.lapanganId;
                        this.lapanganNama = detail.nama;
                        this.lapanganJenis = detail.jenisOlahraga;
                        this.lapanganHarga = detail.hargaPerJam;
                        this.cabangAlamat = detail.cabangAlamat;
                        this.jamBuka = detail.jamBuka.substring(0, 5);
                        this.jamTutup = detail.jamTutup.substring(0, 5);
                        this.slot = null;
                        this.tanggal = null;
                        this.jamMulai = null;
                        this.jamSelesai = null;
                        this.harga = 0;
                        this.totalBayar = 0;
                        this.diskonJumlah = 0;
                        this.kodePromoAktif = null;
                        this.member = null;
                    },
                    onSlotDipilih(detail) {
                        this.slot = detail.slotId;
                        this.tanggal = detail.tanggal;
                        this.jamMulai = detail.jamMulai;
                        this.jamSelesai = detail.jamSelesai;
                        this.harga = detail.harga;
                        this.totalBayar = detail.harga;
                        this.errorPesan = null;
                    },
                    onRingkasanBerubah(detail) {
                        this.tipePembayaranAktif = detail.tipePembayaran;
                        this.kodePromoAktif = detail.kodePromo;
                        this.diskonJumlah = detail.diskonJumlah;
                        this.totalBayar = detail.totalBayar;
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
                                kode_promo: this.kodePromoAktif,
                                tipe_pembayaran: this.tipePembayaranAktif,
                                reminder_aktif: this.reminderAktif,
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
                x-on:lapangan-dipilih.window="onLapanganDipilih($event.detail)"
                x-on:ringkasan-berubah.window="onRingkasanBerubah($event.detail)"
                class="max-w-4xl mx-auto px-5 py-6 grid gap-5 items-start lg:grid-cols-[1.4fr_1fr]"
            >
                <x-card>
                    <livewire:kalender-booking :lapangan-id="$lapanganAktif->id" :key="'kalender-'.$lapanganAktif->id" />
                </x-card>

                <x-card>
                    <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Ringkasan Booking</div>

                    <div class="flex justify-between py-2 border-b border-dashed border-cream-deep text-sm">
                        <span class="text-ink-soft">Lapangan</span>
                        <span class="font-semibold" x-text="lapanganNama"></span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-dashed border-cream-deep text-sm">
                        <span class="text-ink-soft">Tanggal</span>
                        <span class="font-semibold" x-text="tanggalLabel"></span>
                    </div>
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-ink-soft">Jam</span>
                        <span class="font-semibold" x-text="jamLabel"></span>
                    </div>

                    <div class="flex justify-between py-1 text-sm text-green" x-show="diskonJumlah > 0" x-cloak>
                        <span>Diskon <span x-text="kodePromoAktif"></span></span>
                        <span x-text="'-' + formatRupiah(diskonJumlah)"></span>
                    </div>

                    <div class="flex justify-between mt-3 pt-3 border-t-2 border-cream-deep text-base font-bold text-green">
                        <span>Total Bayar</span>
                        <span x-text="formatRupiah(totalBayar)"></span>
                    </div>

                    <div class="mt-4">
                        <livewire:ringkasan-booking :lapangan-id="$lapanganAktif->id" :key="'ringkasan-'.$lapanganAktif->id" />
                    </div>

                    <div class="mt-5 space-y-3.5">
                        <x-input label="Nama Lengkap" name="nama" placeholder="Masukkan nama kamu" x-model="nama" />
                        <x-input label="Nomor WhatsApp" name="whatsapp" type="tel" placeholder="08xxxxxxxxxx" x-model="whatsapp" />
                    </div>

                    @if ($tenant->punyaFitur('reminder_otomatis'))
                        <div class="flex items-center justify-between gap-3 mt-4 rounded-lg border border-cream-deep px-3 py-2.5">
                            <span class="text-sm text-ink-mid">Kirim pengingat WhatsApp 2 jam sebelum jadwal main</span>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="reminderAktif.toString()"
                                @click="reminderAktif = !reminderAktif"
                                :class="reminderAktif ? 'bg-green' : 'bg-cream-deep'"
                                class="relative inline-flex h-6 w-11 flex-none items-center rounded-full transition-colors"
                            >
                                <span :class="reminderAktif ? 'translate-x-5' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                            </button>
                        </div>
                    @endif

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
