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
                <x-icon name="{{ isset($pembayaranError) ? 'warning' : 'check-circle' }}" size="48" class="{{ isset($pembayaranError) ? 'text-danger' : 'text-green' }}" />
            </div>

            @if (isset($pembayaranError))
                <h1 class="font-display text-2xl font-semibold text-ink mb-2">Pendaftaran Tersimpan</h1>
                <p class="text-sm text-ink-mid mb-6">{{ $pembayaranError }}</p>
            @elseif ($tenant->status_aktif && $tenant->dibayar_at)
                <h1 class="font-display text-2xl font-semibold text-ink mb-2">Pembayaran Diterima</h1>
                <p class="text-sm text-ink-mid mb-6">
                    "{{ $tenant->nama_bisnis }}" di {{ $tenant->domain }} sudah aktif. Cek email
                    {{ $tenant->email_admin }} untuk link setup akun owner kamu.
                </p>
            @else
                <h1 class="font-display text-2xl font-semibold text-ink mb-2">Menunggu Konfirmasi Pembayaran</h1>
                <p class="text-sm text-ink-mid mb-6">
                    Terima kasih! Setelah pembayaran untuk "{{ $tenant->nama_bisnis }}" kami terima (biasanya
                    dalam beberapa menit), "{{ $tenant->domain }}" otomatis aktif dan link setup akun owner
                    akan dikirim ke {{ $tenant->email_admin }}.
                </p>
            @endif

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
