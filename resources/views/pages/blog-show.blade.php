<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $artikel->judul }}, Green Deahan Sport</title>

        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .artikel-konten h2 { font-family: 'Manrope', sans-serif; font-weight: 700; letter-spacing: -0.02em; font-size: 1.25rem; color: #1c1917; margin-top: 2rem; margin-bottom: 0.75rem; }
            .artikel-konten p { color: #57534e; line-height: 1.75; margin-bottom: 1rem; }
            .artikel-konten ul { list-style: disc; padding-left: 1.5rem; color: #57534e; margin-bottom: 1rem; }
            .artikel-konten li { margin-bottom: 0.4rem; line-height: 1.6; }
            .artikel-konten strong { color: #1c1917; font-weight: 700; }
            .artikel-konten blockquote { background: #f3faf3; border-left: 4px solid #006400; border-radius: 0 12px 12px 0; padding: 1rem 1.25rem; margin: 1.5rem 0; color: #1c1917; font-size: 0.9rem; }
            .artikel-konten blockquote p { margin-bottom: 0; color: inherit; }
        </style>
    </head>
    <body class="bg-[#f7f5f2] font-marketing text-stone-900 antialiased">

        <x-site-nav :nav-links="[
            ['label' => 'Beranda', 'href' => '/'],
            ['label' => 'Galeri', 'href' => '/galeri'],
            ['label' => 'Blog', 'href' => '/blog'],
            ['label' => 'Kontak', 'href' => '/kontak'],
        ]" />

        <article class="mx-auto max-w-3xl px-6 pb-16 pt-28">
            <a href="/blog" class="mb-6 inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:underline">
                <x-icon name="chevron-left" size="14" />
                Kembali ke Blog
            </a>

            <span class="mb-4 inline-block rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-bold text-brand">{{ $artikel->kategori }}</span>

            <h1 class="font-marketing-display mb-3 text-3xl font-black leading-tight text-stone-900 md:text-4xl">{{ $artikel->judul }}</h1>

            <div class="mb-6 flex items-center gap-2 text-sm text-stone-400">
                <x-icon name="calendar" size="14" />
                {{ $artikel->tanggal_terbit->translatedFormat('d F Y') }}
            </div>

            @if ($artikel->foto_url)
                <img
                    src="{{ $artikel->foto_url }}"
                    alt="{{ $artikel->judul }}"
                    class="mb-8 aspect-[16/9] w-full rounded-2xl border border-stone-200 object-cover"
                />
            @endif

            <div class="artikel-konten text-sm md:text-base">
                {!! $artikel->konten !!}
            </div>

            <div class="mt-10 rounded-2xl border border-brand-200 bg-brand-50 p-5 text-center">
                <p class="mb-3 text-sm text-stone-700">Tertarik konsultasi soal proyek lapangan Anda?</p>
                <a
                    href="https://wa.me/6281357570064?text={{ urlencode('Halo, saya baca artikel '.$artikel->judul.' dan ingin konsultasi') }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-dark"
                >
                    <x-icon name="whatsapp-logo" size="16" class="text-white" />
                    Konsultasi Gratis via WhatsApp
                </a>
            </div>

            @if ($terkait->isNotEmpty())
                <div class="mt-12">
                    <h2 class="font-marketing-display mb-4 text-lg font-black text-stone-900">Artikel Terkait</h2>
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach ($terkait as $item)
                            <a href="/blog/{{ $item->slug }}" class="block overflow-hidden rounded-xl border border-stone-200 bg-white transition-colors hover:border-brand">
                                @if ($item->foto_url)
                                    <div class="aspect-[16/9] overflow-hidden">
                                        <img src="{{ $item->foto_url }}" alt="{{ $item->judul }}" class="h-full w-full object-cover">
                                    </div>
                                @endif
                                <div class="p-3">
                                    <p class="line-clamp-2 text-xs font-bold leading-tight text-stone-900">{{ $item->judul }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </article>

        <x-site-footer />
    </body>
</html>
