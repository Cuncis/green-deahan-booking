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

        <nav class="sticky top-0 z-40 flex items-center justify-between h-16 px-5 border-b border-cream-deep bg-cream/95 backdrop-blur">
            <div class="font-display font-semibold text-green">Green Deahan Sport</div>
            <a href="/harga" class="text-sm text-ink-mid hover:text-brown">Lihat Harga</a>
        </nav>

        <div class="max-w-xl mx-auto px-5 py-12">
            <div class="mb-6">
                <h1 class="font-display text-2xl font-semibold text-ink mb-1">Daftarkan Bisnismu</h1>
                <p class="text-sm text-ink-soft">Isi data di bawah, tim kami akan menghubungimu untuk aktivasi dalam 1x24 jam.</p>
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

                    <x-input label="Nama Bisnis" name="nama_bisnis" value="{{ old('nama_bisnis') }}" required />

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
                        <p class="text-xs text-ink-soft mt-1">Huruf kecil, angka, dan tanda hubung saja, minimal 3 karakter.</p>
                    </div>

                    <x-input label="Nama PIC (Penanggung Jawab)" name="nama_pic" value="{{ old('nama_pic') }}" required />
                    <x-input label="Email PIC" name="email_pic" type="email" value="{{ old('email_pic') }}" required />
                    <x-input label="Nomor WhatsApp PIC" name="whatsapp_pic" value="{{ old('whatsapp_pic') }}" required />

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Paket</label>
                        <select name="paket" class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
                            @foreach (['basic' => 'Basic, Rp150.000/bulan', 'pro' => 'Pro, Rp350.000/bulan', 'premium' => 'Premium, Rp750.000/bulan'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('paket', $paketTerpilih) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-button type="submit" class="w-full justify-center">Daftar Sekarang</x-button>
                </form>
            </x-card>
        </div>
    </body>
</html>
