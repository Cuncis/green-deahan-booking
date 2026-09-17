@php
    $waNumber = '6281357570064';
@endphp

<footer id="kontak" class="bg-stone-900 px-6 pb-8 pt-14 text-stone-400">
    <div class="mx-auto mb-10 grid max-w-6xl gap-10 sm:grid-cols-2 md:grid-cols-4">
        <div class="md:col-span-2">
            <div class="mb-4 flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white p-0.5">
                    <img
                        src="https://cdn.libradigital.id/site-assets/GD-logo-1.png"
                        alt="Green Deahan Sport"
                        class="h-full w-full object-contain"
                    />
                </div>
                <div>
                    <div class="font-marketing-display text-base font-black leading-none text-white">Green Deahan Sport</div>
                    <div class="mt-0.5 text-[10px] font-semibold uppercase leading-none tracking-widest text-stone-500">Sejak 2010</div>
                </div>
            </div>
            <p class="mb-5 max-w-xs text-sm leading-relaxed text-stone-500">
                Jasa pembuatan lapangan futsal, mini soccer, padel, dan badminton profesional sejak 2010. Melayani seluruh Indonesia.
            </p>
            <div class="space-y-2 text-sm">
                <p class="flex items-center gap-2">
                    <x-icon name="phone-call" size="14" class="text-stone-500" />
                    <a href="tel:+{{ $waNumber }}" class="transition-colors hover:text-white">+62 813-5757-0064</a>
                </p>
                <p class="flex items-center gap-2">
                    <x-icon name="mail-envelope" size="14" class="text-stone-500" />
                    <a href="mailto:rumput1927@gmail.com" class="transition-colors hover:text-white">rumput1927@gmail.com</a>
                </p>
                <p class="flex items-center gap-2">
                    <x-icon name="clock" size="14" class="text-stone-500" />
                    Senin-Sabtu, 06.00-23.00 WIB
                </p>
            </div>

            <div class="mt-4 flex items-center gap-3">
                @foreach ([
                    ['icon' => 'instagram-logo', 'href' => 'https://www.instagram.com/green_deahan1927', 'label' => 'Instagram', 'hover' => 'hover:bg-pink-600'],
                    ['icon' => 'facebook-logo', 'href' => 'https://www.facebook.com/greendeahan/', 'label' => 'Facebook', 'hover' => 'hover:bg-[#1877f2]'],
                    ['icon' => 'tiktok-logo', 'href' => 'https://www.tiktok.com/@green_deahan', 'label' => 'TikTok', 'hover' => 'hover:bg-stone-600'],
                    ['icon' => 'x-logo', 'href' => 'https://x.com/greendeahan1927', 'label' => 'X / Twitter', 'hover' => 'hover:bg-stone-600'],
                    ['icon' => 'youtube-logo', 'href' => 'https://www.youtube.com/@green_deahan', 'label' => 'YouTube', 'hover' => 'hover:bg-red-600'],
                ] as $social)
                    <a
                        href="{{ $social['href'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="{{ $social['label'] }}"
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-stone-800 text-stone-300 transition-colors {{ $social['hover'] }}"
                    >
                        <x-icon :name="$social['icon']" size="15" />
                    </a>
                @endforeach
            </div>
        </div>

        <div>
            <h4 class="mb-4 text-sm font-bold uppercase tracking-widest text-white">Layanan</h4>
            <ul class="space-y-2 text-sm">
                @foreach ([
                    'Lapangan Futsal' => 'lapangan futsal',
                    'Mini Soccer' => 'mini soccer',
                    'Lapangan Padel' => 'lapangan padel',
                    'Lapangan Badminton' => 'lapangan badminton',
                    'Maintenance & Servis' => 'maintenance lapangan',
                ] as $label => $topik)
                    <li>
                        <a
                            href="https://wa.me/{{ $waNumber }}?text={{ urlencode("Halo, saya ingin konsultasi {$topik}") }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="transition-colors hover:text-white"
                        >{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            <h4 class="mb-4 text-sm font-bold uppercase tracking-widest text-white">Halaman</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="/" class="transition-colors hover:text-white">Beranda</a></li>
                <li><a href="/konsep" class="transition-colors hover:text-white">Konsep Sport Center</a></li>
                <li><a href="/galeri" class="transition-colors hover:text-white">Galeri Proyek</a></li>
                <li><a href="/blog" class="transition-colors hover:text-white">Blog & Tips</a></li>
                <li><a href="/harga" class="transition-colors hover:text-white">Website Booking</a></li>
                <li><a href="/#layanan" class="transition-colors hover:text-white">Layanan</a></li>
                <li><a href="/kontak" class="transition-colors hover:text-white">Kontak Kami</a></li>
            </ul>
        </div>
    </div>

    <div class="mx-auto max-w-6xl border-t border-stone-800 pt-6 text-center text-xs text-stone-500">
        &copy; {{ now()->year }} <strong class="font-semibold text-stone-300">Green Deahan Sport</strong>. Seluruh hak cipta dilindungi.<br>
        Jasa Pembuatan Lapangan Futsal, Mini Soccer, Padel &amp; Badminton Se-Indonesia
    </div>
</footer>
