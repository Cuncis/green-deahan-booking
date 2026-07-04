<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Pendaftaran Diterima, Green Deahan Sport</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-cream text-ink">

        <nav class="sticky top-0 z-40 flex items-center h-16 px-5 border-b border-cream-deep bg-cream/95 backdrop-blur">
            <a href="/">
                <img
                    src="https://gdlogin.greendeahan.com/wp-content/uploads/2026/04/full-logo-02.png"
                    alt="Green Deahan Sport"
                    class="h-9 w-auto object-contain"
                />
            </a>
        </nav>

        <div class="max-w-md mx-auto px-5 py-16 text-center">
            <div class="flex justify-center mb-4">
                <x-icon name="check-circle" size="48" class="text-green" />
            </div>
            <h1 class="font-display text-2xl font-semibold text-ink mb-2">Pendaftaran Diterima</h1>
            <p class="text-sm text-ink-mid mb-6">
                Kami akan menghubungi kamu dalam 1x24 jam untuk aktivasi "{{ $tenant->nama_bisnis }}"
                di {{ $tenant->domain }}.
            </p>

            @if ($tenant->custom_domain_diminta)
                <div class="mb-6 rounded-lg border border-gold/30 bg-gold/10 px-4 py-3 text-sm text-ink text-left">
                    Kamu juga meminta custom domain <strong>{{ $tenant->custom_domain_diminta }}</strong>.
                    Tim kami akan hubungi kamu untuk verifikasi kepemilikan domain sebelum diaktifkan.
                </div>
            @endif

            <a href="/harga" class="inline-flex items-center justify-center rounded-lg font-sans font-semibold text-sm px-5 py-2.5 border-2 border-sand text-ink-mid hover:border-brown-light hover:text-brown transition-colors">
                Kembali ke Halaman Harga
            </a>
        </div>
    </body>
</html>
