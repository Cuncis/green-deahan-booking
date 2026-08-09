<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Daftarkan Bisnis, Green Deahan Sport</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-cream text-ink">

        <nav class="sticky top-0 z-40 h-16 border-b border-cream-deep bg-cream/95 backdrop-blur">
            <div class="mx-auto flex h-full max-w-6xl items-center justify-between px-6">
                <a href="/">
                    <img
                        src="https://gdlogin.greendeahan.com/wp-content/uploads/2026/04/full-logo-02.png"
                        alt="Green Deahan Sport"
                        class="h-9 w-auto object-contain"
                    />
                </a>
                <a href="/harga" class="text-sm text-ink-mid hover:text-brown">Lihat Harga</a>
            </div>
        </nav>

        <div
            class="max-w-xl mx-auto px-5 py-12"
            x-data="{
                paket: @js(old('paket', $paketTerpilih)),
                customDomain: @js(old('custom_domain_diminta', '')),
                hargaPaket: @js($hargaPaket),
                hargaAddonDomain: @js($hargaAddonDomain),
                get bisaCustomDomain() { return this.paket !== 'basic' },
                get totalHarga() {
                    let total = this.hargaPaket[this.paket] ?? 0;
                    if (this.bisaCustomDomain && this.customDomain.trim()) { total += this.hargaAddonDomain; }
                    return total;
                },
                formatRupiah(angka) { return 'Rp' + angka.toLocaleString('id-ID'); },
            }"
        >
            <div class="mb-6">
                <h1 class="font-display text-2xl font-semibold text-ink mb-1">Daftarkan Bisnismu</h1>
                <p class="text-sm text-ink-soft">Isi data di bawah, kamu akan diarahkan ke halaman pembayaran dan akun ownermu aktif otomatis begitu pembayaran diterima.</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-danger/30 bg-danger-pale px-4 py-3 text-sm text-danger">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-card>
                <form method="POST" action="{{ route('daftar.store') }}" class="space-y-4">
                    @csrf

                    <x-input label="Nama Bisnis" name="nama_bisnis" value="{{ old('nama_bisnis') }}" placeholder="Contoh: Futsal Merdeka" required />

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Paket</label>
                        <select name="paket" x-model="paket" class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
                            @foreach (['basic' => 'Basic, Rp1,5jt/tahun', 'pro' => 'Pro, Rp2,5jt/tahun', 'premium' => 'Premium, Rp4,5jt/tahun'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('paket', $paketTerpilih) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="bisaCustomDomain" x-cloak>
                        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1" for="custom_domain_diminta">
                            Custom Domain (opsional, +Rp250.000/tahun)
                        </label>
                        <input
                            id="custom_domain_diminta"
                            type="text"
                            name="custom_domain_diminta"
                            x-model="customDomain"
                            placeholder="Contoh: namabisnis.com"
                            class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft focus:border-green focus:outline-none focus:ring-1 focus:ring-green"
                        />
                        <p class="text-xs text-ink-soft mt-1">
                            Kosongkan kalau cukup pakai subdomain di bawah. Kalau diisi, tim kami akan hubungi kamu untuk verifikasi kepemilikan domain sebelum diaktifkan.
                        </p>
                        @error('custom_domain_diminta')
                            <p class="text-xs text-danger mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="! bisaCustomDomain" class="rounded-lg border border-gold/30 bg-gold/10 px-4 py-3 text-xs text-ink-mid">
                        Custom domain hanya tersedia untuk paket Pro dan Premium. Pilih salah satu paket itu kalau kamu mau pakai domain sendiri.
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Subdomain</label>
                        <div class="flex items-center rounded-lg border border-cream-deep bg-cream focus-within:border-green focus-within:ring-1 focus-within:ring-green">
                            <input
                                type="text"
                                name="subdomain"
                                value="{{ old('subdomain') }}"
                                placeholder="namabisnis"
                                required
                                class="flex-1 min-w-0 bg-transparent px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft focus:outline-none"
                            />
                            <span class="pr-4 text-sm text-ink-soft whitespace-nowrap">.greendeahan.com</span>
                        </div>
                        <p class="text-xs text-ink-soft mt-1">Huruf kecil, angka, dan tanda hubung saja, minimal 3 karakter. Ini alamat default kamu, aktif segera setelah disetujui.</p>
                    </div>

                    <x-input label="Nama PIC (Penanggung Jawab)" name="nama_pic" value="{{ old('nama_pic') }}" placeholder="Contoh: Budi Santoso" required />
                    <x-input label="Email PIC" name="email_pic" type="email" value="{{ old('email_pic') }}" placeholder="Contoh: budi@email.com" required />
                    <x-input label="Nomor WhatsApp PIC" name="whatsapp_pic" value="{{ old('whatsapp_pic') }}" placeholder="Contoh: 081234567890" required />

                    <div class="rounded-lg border border-cream-deep bg-cream px-4 py-3 flex items-center justify-between">
                        <span class="text-sm text-ink-mid">Estimasi Total</span>
                        <span class="font-display text-lg font-semibold text-green" x-text="formatRupiah(totalHarga) + '/tahun'"></span>
                    </div>

                    <x-button type="submit" class="w-full justify-center">Daftar Sekarang</x-button>
                </form>
            </x-card>
        </div>
    </body>
</html>
