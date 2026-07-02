{{-- $kategoriTab dan $items dikirim dari GaleriController@index, datanya
     dikelola lewat /superadmin/galeri (lihat GaleriAdminController). --}}
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Galeri Proyek, Green Deahan Sport</title>

        <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .reveal { opacity: 0; transform: translateY(22px); transition: opacity .65s ease, transform .65s ease; }
            .reveal.visible { opacity: 1; transform: translateY(0); }

            .gal-grid { display: grid; grid-template-columns: repeat(3, 1fr); grid-auto-rows: 220px; gap: 14px; }
            @media (max-width: 768px) { .gal-grid { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 180px; } .tall { grid-row: span 1; } }
            @media (max-width: 480px) { .gal-grid { grid-template-columns: 1fr; grid-auto-rows: 200px; } }
            .gal-item { cursor: pointer; border-radius: 14px; overflow: hidden; border: 2px solid transparent; transition: transform .25s, box-shadow .25s, border-color .25s; background: #d4e6d4; }
            .gal-item:hover { transform: translateY(-4px); box-shadow: 0 14px 36px rgba(0,0,0,.13); border-color: #006400; }
            .gal-item .overlay { position: absolute; inset: 0; background: rgba(0,100,0,.75); opacity: 0; display: flex; align-items: center; justify-content: center; transition: opacity .25s; flex-direction: column; gap: .5rem; }
            .gal-item:hover .overlay { opacity: 1; }
            .tall { grid-row: span 2; }

            .tab-scroll { scrollbar-width: thin; scrollbar-color: #006400 #d4e6d4; }
            .tab-scroll::-webkit-scrollbar { height: 4px; }
            .tab-scroll::-webkit-scrollbar-track { background: #d4e6d4; border-radius: 99px; }
            .tab-scroll::-webkit-scrollbar-thumb { background: #006400; border-radius: 99px; }
            .tab-scroll::-webkit-scrollbar-thumb:hover { background: #004d00; }
        </style>
    </head>
    <body class="bg-[#f7f5f2] font-marketing text-stone-900 antialiased" x-data="galeriApp()" x-init="initReveal()">

        <x-site-nav />

        <!-- Lightbox -->
        <div
            x-show="lightboxOpen"
            x-cloak
            x-on:keydown.escape.window="closeLightbox()"
            x-on:keydown.arrow-left.window="nav(-1)"
            x-on:keydown.arrow-right.window="nav(1)"
            class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/90 p-4"
            x-on:click.self="closeLightbox()"
        >
            <div class="relative w-full max-w-3xl overflow-hidden rounded-2xl" x-show="lightboxOpen" x-transition>
                <template x-if="current">
                    <div>
                        <div class="relative aspect-[35/22] w-full overflow-hidden">
                            <img :src="current.src" :alt="current.title" class="h-full w-full object-cover">
                            <a
                                :href="current.src"
                                target="_blank"
                                rel="noopener noreferrer"
                                title="Buka gambar penuh di tab baru"
                                class="absolute bottom-2 right-2 z-10 flex h-8 w-8 items-center justify-center rounded-md bg-black/40 text-white backdrop-blur transition-colors hover:bg-brand/80"
                            >
                                <x-icon name="expand" size="16" />
                            </a>
                        </div>
                        <div class="bg-white p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <span class="mb-2 inline-block rounded-full border px-2.5 py-1 text-xs font-bold" :class="current.badgeClass" x-text="current.badgeLabel"></span>
                                    <h3 class="font-marketing-display text-lg font-black text-stone-900" x-text="current.title"></h3>
                                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-stone-500">
                                        <span x-text="current.desc"></span>
                                        <span class="inline-flex items-center gap-1"><x-icon name="location-pin" size="13" class="text-brand" /><span x-text="current.kota"></span></span>
                                        <span class="inline-flex items-center gap-1"><x-icon name="layers" size="13" class="text-brand" /><span x-text="current.material"></span></span>
                                    </p>
                                </div>
                                <a
                                    href="https://wa.me/6281357570064"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex-shrink-0 whitespace-nowrap rounded-lg bg-brand px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-brand-dark"
                                >Konsultasi &rarr;</a>
                            </div>
                        </div>
                    </div>
                </template>

                <button
                    type="button"
                    x-on:click="closeLightbox()"
                    class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-stone-700 hover:bg-white"
                >
                    <x-icon name="x-close" size="16" />
                </button>
                <button
                    type="button"
                    x-on:click.stop="nav(-1)"
                    class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 hover:bg-white"
                >
                    <x-icon name="chevron-left" size="18" />
                </button>
                <button
                    type="button"
                    x-on:click.stop="nav(1)"
                    class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 hover:bg-white"
                >
                    <x-icon name="chevron-right" size="18" />
                </button>
            </div>
        </div>

        <!-- Page Hero -->
        <section class="mx-auto max-w-6xl px-6 pb-10 pt-28">
            <div class="reveal">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div>
                        <span class="mb-3 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Portofolio Nyata</span>
                        <h1 class="font-marketing-display text-4xl font-black leading-tight text-stone-900 md:text-5xl">
                            Galeri Proyek<br><span class="text-brand">Green Deahan Sport</span>
                        </h1>
                        <p class="mt-3 max-w-xl text-sm text-stone-500 md:text-base">
                            Lihat hasil nyata konstruksi lapangan kami di berbagai kota Indonesia. Klik foto untuk melihat detail proyek.
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 gap-4">
                        <div class="rounded-xl border border-stone-200 bg-white px-5 py-3 text-center">
                            <div class="font-marketing-display text-2xl font-black text-brand">100+</div>
                            <div class="mt-0.5 text-xs font-semibold text-stone-400">Proyek</div>
                        </div>
                        <div class="rounded-xl border border-stone-200 bg-white px-5 py-3 text-center">
                            <div class="font-marketing-display text-2xl font-black text-brand">15+</div>
                            <div class="mt-0.5 text-xs font-semibold text-stone-400">Kota</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tabs -->
        <section class="sticky top-[65px] z-40 border-b border-stone-200 bg-[#f7f5f2] px-6 py-3">
            <div class="mx-auto max-w-6xl">
                <div class="tab-scroll flex gap-2 overflow-x-auto pb-1">
                    @foreach ($kategoriTab as $key => $tab)
                        <button
                            type="button"
                            x-on:click="activeFilter = '{{ $key }}'"
                            :class="activeFilter === '{{ $key }}'
                                ? 'bg-brand text-white border-brand shadow-md shadow-brand-dark/20'
                                : 'bg-white text-stone-600 border-stone-200 hover:border-brand hover:text-brand'"
                            class="flex flex-shrink-0 items-center gap-1.5 rounded-xl border-2 px-5 py-2.5 text-sm font-bold transition-all"
                        >
                            <x-icon :name="$tab['icon']" size="15" />
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Gallery grid -->
        <section class="mx-auto max-w-6xl px-6 py-10">
            <div class="reveal mb-6 flex items-center justify-between">
                <p class="text-xs italic text-stone-400">Klik foto untuk lihat detail</p>
            </div>

            <div class="gal-grid">
                @foreach ($items as $item)
                    <div
                        x-show="activeFilter === 'semua' || activeFilter === '{{ $item['cat'] }}'"
                        class="gal-item relative {{ $item['tall'] ? 'tall' : '' }}"
                        x-on:click="openLightboxById({{ $item['id'] }})"
                    >
                        <img src="{{ $item['src'] }}" alt="{{ $item['title'] }}" class="h-full w-full object-cover">
                        <div class="overlay">
                            <x-icon :name="$kategoriTab[$item['cat']]['icon']" size="30" class="text-white" />
                            <p class="px-4 text-center text-sm font-bold text-white">{{ $item['title'] }}</p>
                            <span class="text-xs text-brand-200">Klik untuk detail &rarr;</span>
                        </div>
                        <div class="absolute inset-x-0 bottom-0 bg-white/95 px-3 py-2 backdrop-blur">
                            <span class="text-[10px] font-bold text-brand">{{ $kategoriTab[$item['cat']]['label'] }}</span>
                            <p class="mt-0.5 truncate text-xs font-semibold leading-tight text-stone-800">{{ $item['title'] }}</p>
                            <p class="mt-0.5 flex items-center gap-1 text-[10px] text-stone-400">
                                <x-icon name="location-pin" size="10" />
                                {{ $item['kota'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- CTA strip -->
        <section class="bg-brand px-6 py-16">
            <div class="mx-auto flex max-w-4xl flex-col items-center justify-between gap-6 text-center md:flex-row md:text-left">
                <div>
                    <h2 class="font-marketing-display mb-2 text-2xl font-black text-white md:text-3xl">Tertarik Punya Lapangan Seperti Ini?</h2>
                    <p class="text-sm text-brand-200">Konsultasi gratis dengan tim kami, respons dalam 1 jam!</p>
                </div>
                <div class="flex flex-shrink-0 flex-col gap-3 sm:flex-row">
                    <a
                        href="https://wa.me/6281357570064?text={{ urlencode('Halo, saya tertarik membangun lapangan setelah lihat galeri') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-white px-7 py-3.5 text-sm font-bold text-brand transition-colors hover:bg-brand-50"
                    >
                        <x-icon name="whatsapp-logo" size="16" class="text-brand" />
                        Konsultasi Gratis
                    </a>
                    <a
                        href="/kontak"
                        class="whitespace-nowrap rounded-xl border-2 border-white px-7 py-3.5 text-center text-sm font-bold text-white transition-colors hover:bg-white/10"
                    >
                        Hubungi Kami
                    </a>
                </div>
            </div>
        </section>

        <x-site-footer />

        <script>
            function galeriApp() {
                return {
                    items: @json($items),
                    activeFilter: 'semua',
                    lightboxOpen: false,
                    lightboxIndex: 0,
                    get filteredItems() {
                        return this.activeFilter === 'semua'
                            ? this.items
                            : this.items.filter((item) => item.cat === this.activeFilter);
                    },
                    get current() {
                        return this.filteredItems[this.lightboxIndex] ?? null;
                    },
                    openLightboxById(id) {
                        const idx = this.filteredItems.findIndex((item) => item.id === id);
                        this.lightboxIndex = idx === -1 ? 0 : idx;
                        this.lightboxOpen = true;
                        document.body.style.overflow = 'hidden';
                    },
                    closeLightbox() {
                        this.lightboxOpen = false;
                        document.body.style.overflow = '';
                    },
                    nav(dir) {
                        const total = this.filteredItems.length;
                        this.lightboxIndex = (this.lightboxIndex + dir + total) % total;
                    },
                    initReveal() {
                        const observer = new IntersectionObserver((entries) => {
                            entries.forEach((entry) => {
                                if (entry.isIntersecting) {
                                    entry.target.classList.add('visible');
                                    observer.unobserve(entry.target);
                                }
                            });
                        }, { threshold: 0.08 });
                        this.$el.querySelectorAll('.reveal').forEach((el) => observer.observe(el));
                    },
                };
            }
        </script>
    </body>
</html>
