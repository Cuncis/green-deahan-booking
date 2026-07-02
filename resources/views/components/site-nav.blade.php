@props(['navLinks' => [
    ['label' => 'Beranda', 'href' => '/'],
    ['label' => 'Galeri', 'href' => '/galeri'],
    ['label' => 'Blog', 'href' => '/blog'],
    ['label' => 'Website Booking', 'href' => '/harga'],
    ['label' => 'Kontak', 'href' => '/kontak'],
]])

@php
    $waNumber = '6281357570064';
    $waLink = "https://wa.me/{$waNumber}";
@endphp

{{-- Floating WhatsApp button, tampil di seluruh halaman --}}
<a
    href="{{ $waLink }}"
    target="_blank"
    rel="noopener noreferrer"
    class="fixed bottom-7 right-7 z-[999] flex items-center gap-2 rounded-full bg-[#25d366] px-5 py-3.5 text-sm font-bold text-white shadow-[0_6px_24px_rgba(37,211,102,0.45)] transition-all hover:-translate-y-1 hover:shadow-[0_10px_30px_rgba(37,211,102,0.5)]"
>
    <x-icon name="whatsapp-logo" size="20" class="text-white" />
    Chat WhatsApp
</a>

<nav
    x-data="{ mobileOpen: false, scrolled: false }"
    x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 40 })"
    :class="scrolled ? 'shadow-md' : ''"
    class="fixed inset-x-0 top-0 z-50 border-b border-stone-200 bg-[#f7f5f2]/95 backdrop-blur transition-shadow duration-300"
>
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <a href="/" class="flex items-center">
            <img
                src="https://gdlogin.greendeahan.com/wp-content/uploads/2026/04/full-logo-02.png"
                alt="Green Deahan Sport"
                class="h-10 w-auto object-contain"
            />
        </a>

        <div class="hidden items-center gap-7 text-sm font-semibold text-stone-500 md:flex">
            @foreach ($navLinks as $link)
                <a href="{{ $link['href'] }}" class="transition-colors hover:text-brand">{{ $link['label'] }}</a>
            @endforeach
        </div>

        <a
            href="{{ $waLink }}"
            target="_blank"
            rel="noopener noreferrer"
            class="hidden items-center gap-2 rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-dark md:flex"
        >
            <x-icon name="whatsapp-logo" size="15" class="text-white" />
            Konsultasi Gratis
        </a>

        <button
            type="button"
            class="flex flex-col gap-1.5 p-1 md:hidden"
            :aria-expanded="mobileOpen"
            aria-label="Toggle menu"
            x-on:click="mobileOpen = !mobileOpen"
        >
            <span class="block h-0.5 w-6 bg-stone-700"></span>
            <span class="block h-0.5 w-6 bg-stone-700"></span>
            <span class="block h-0.5 w-6 bg-stone-700"></span>
        </button>
    </div>

    <div
        x-show="mobileOpen"
        x-cloak
        class="flex flex-col gap-4 border-t border-stone-200 bg-[#f7f5f2] px-6 py-4 text-sm font-semibold text-stone-700 md:hidden"
    >
        @foreach ($navLinks as $link)
            <a href="{{ $link['href'] }}" class="hover:text-brand" x-on:click="mobileOpen = false">{{ $link['label'] }}</a>
        @endforeach
        <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-brand py-2.5 text-center text-white">
            Konsultasi Gratis
        </a>
    </div>
</nav>
