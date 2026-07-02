<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Harga, Green Deahan Sport</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-cream text-ink">

        <nav class="sticky top-0 z-40 flex items-center justify-between h-16 px-5 border-b border-cream-deep bg-cream/95 backdrop-blur">
            <div class="font-display font-semibold text-green">Green Deahan Sport</div>
            <a href="{{ route('daftar.show') }}" class="inline-flex items-center gap-2 bg-green text-white px-4 py-2 rounded-lg text-sm font-semibold">
                Daftarkan Bisnis
            </a>
        </nav>

        <div class="max-w-5xl mx-auto px-5 py-12">
            <div class="text-center mb-10">
                <h1 class="font-display text-3xl md:text-4xl font-semibold text-ink mb-3">Pilih Paket untuk Bisnismu</h1>
                <p class="text-sm text-ink-mid max-w-xl mx-auto">
                    Sistem booking lapangan olahraga lengkap, mulai dari booking online sampai laporan pendapatan.
                    Upgrade paket kapan saja lewat tim kami.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <x-card class="flex flex-col">
                    <div class="text-xs font-bold uppercase tracking-wide text-ink-soft mb-2">Basic</div>
                    <div class="font-display text-2xl font-semibold text-ink mb-1">Rp150.000<span class="text-sm font-sans text-ink-soft">/bulan</span></div>
                    <p class="text-xs text-ink-soft mb-5">Cocok untuk 1 lapangan yang baru mulai jualan online.</p>

                    <ul class="space-y-2.5 text-sm text-ink-mid mb-6 flex-1">
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Booking online</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Notifikasi WhatsApp</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> 1 lapangan</li>
                    </ul>

                    <a href="{{ route('daftar.show', ['paket' => 'basic']) }}" class="inline-flex items-center justify-center rounded-lg font-sans font-semibold text-sm px-5 py-2.5 border-2 border-sand text-ink-mid hover:border-brown-light hover:text-brown transition-colors">
                        Mulai Sekarang
                    </a>
                </x-card>

                <x-card class="flex flex-col border-2 !border-gold relative">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 text-[0.62rem] font-extrabold uppercase tracking-wide px-3 py-1 rounded-full bg-gold text-white">Paling Laris</span>
                    <div class="text-xs font-bold uppercase tracking-wide text-gold mb-2">Pro</div>
                    <div class="font-display text-2xl font-semibold text-ink mb-1">Rp350.000<span class="text-sm font-sans text-ink-soft">/bulan</span></div>
                    <p class="text-xs text-ink-soft mb-5">Untuk bisnis yang sudah jalan dan mau kelola promo, DP, dan laporan.</p>

                    <ul class="space-y-2.5 text-sm text-ink-mid mb-6 flex-1">
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Semua fitur Basic</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Pembayaran online (QRIS/VA/E-wallet)</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> DP pembayaran</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Kode promo & booking berulang</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Laporan pendapatan</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Sampai 3 lapangan</li>
                    </ul>

                    <a href="{{ route('daftar.show', ['paket' => 'pro']) }}" class="inline-flex items-center justify-center rounded-lg font-sans font-semibold text-sm px-5 py-2.5 bg-gold text-white hover:bg-gold/90 transition-colors">
                        Mulai Sekarang
                    </a>
                </x-card>

                <x-card class="flex flex-col">
                    <div class="text-xs font-bold uppercase tracking-wide text-plum mb-2">Premium</div>
                    <div class="font-display text-2xl font-semibold text-ink mb-1">Rp750.000<span class="text-sm font-sans text-ink-soft">/bulan</span></div>
                    <p class="text-xs text-ink-soft mb-5">Untuk bisnis multi cabang dengan tim dan member tetap.</p>

                    <ul class="space-y-2.5 text-sm text-ink-mid mb-6 flex-1">
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Semua fitur Pro</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Multi cabang tanpa batas</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Sistem membership</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Reminder otomatis</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Manajemen staf</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Analitik lanjutan antar cabang</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="16" class="text-green" /> Lapangan tanpa batas</li>
                    </ul>

                    <a href="{{ route('daftar.show', ['paket' => 'premium']) }}" class="inline-flex items-center justify-center rounded-lg font-sans font-semibold text-sm px-5 py-2.5 bg-plum text-white hover:bg-plum/90 transition-colors">
                        Mulai Sekarang
                    </a>
                </x-card>
            </div>
        </div>
    </body>
</html>
