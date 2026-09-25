<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Blog dan Tips Lapangan Olahraga, Green Deahan Sport</title>

        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .reveal { opacity: 0; transform: translateY(22px); transition: opacity .65s ease, transform .65s ease; }
            .reveal.visible { opacity: 1; transform: translateY(0); }
            .blog-card { transition: transform .25s, box-shadow .25s, border-color .25s; }
            .blog-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.1); border-color: #006400; }
            .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
            .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        </style>
    </head>
    <body
        class="bg-[#f5f5f5] font-marketing text-stone-900 antialiased"
        x-data="blogApp()"
        x-init="$nextTick(() => initReveal()); $watch('filteredPosts', () => $nextTick(() => initReveal()))"
    >

        <x-site-nav :nav-links="[
            ['label' => 'Beranda', 'href' => '/'],
            ['label' => 'Konsep', 'href' => '/konsep'],
            ['label' => 'Galeri', 'href' => '/galeri'],
            ['label' => 'Blog', 'href' => '/blog'],
            ['label' => 'Kontak', 'href' => '/kontak'],
        ]" />

        <!-- Page Hero -->
        <section class="mx-auto max-w-6xl px-6 pb-10 pt-28">
            <div class="reveal">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div>
                        <span class="mb-3 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Tips &amp; Panduan</span>
                        <h1 class="font-marketing-display text-4xl font-black leading-tight text-stone-900 md:text-5xl">
                            Blog <span class="text-brand">Green Deahan</span>
                        </h1>
                        <p class="mt-3 max-w-xl text-sm text-stone-500 md:text-base">
                            Tips membangun lapangan, panduan memilih material, estimasi biaya, dan insight bisnis lapangan olahraga di Indonesia.
                        </p>
                    </div>
                    <div class="w-full flex-shrink-0 md:w-72">
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400">
                                <x-icon name="search" size="16" />
                            </span>
                            <input
                                type="text"
                                x-model="searchQuery"
                                placeholder="Cari artikel..."
                                class="w-full rounded-xl border-2 border-stone-200 bg-[#f5f5f5] py-3 pl-10 pr-4 text-sm font-medium transition-all focus:border-brand focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,100,0,.1)]"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Category filter -->
        <section class="reveal mx-auto mb-8 max-w-6xl px-6" x-show="categories.length > 1">
            <div class="flex flex-wrap gap-2">
                <template x-for="cat in categories" :key="cat">
                    <button
                        type="button"
                        x-on:click="activeCategory = cat"
                        :class="activeCategory === cat ? 'bg-brand text-white border-brand' : 'bg-[#f5f5f5] text-stone-600 border-stone-200 hover:border-brand hover:text-brand'"
                        class="rounded-full border-2 px-4 py-2 text-xs font-bold transition-all"
                        x-text="cat === 'semua' ? 'Semua' : cat"
                    ></button>
                </template>
            </div>
        </section>

        <!-- Blog grid -->
        <section class="mx-auto max-w-6xl px-6 pb-16">
            <!-- Featured post -->
            <div class="reveal mb-12" x-show="filteredPosts.length > 0 && !searchQuery && activeCategory === 'semua'">
                <template x-if="filteredPosts[0]">
                    <a :href="'/blog/' + filteredPosts[0].slug" class="group block overflow-hidden rounded-2xl border-2 border-stone-200 bg-[#f5f5f5] transition-colors hover:border-brand">
                        <div class="grid md:grid-cols-2">
                            <div class="relative overflow-hidden" style="min-height:280px">
                                <img :src="filteredPosts[0].fotoUrl" :alt="filteredPosts[0].judul" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            </div>
                            <div class="flex flex-col justify-center p-8">
                                <div class="mb-4 flex items-center gap-2">
                                    <span class="rounded-full bg-brand px-3 py-1 text-xs font-bold text-white">Artikel Pilihan</span>
                                    <span class="rounded-full border border-brand-200 bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand" x-text="filteredPosts[0].kategori"></span>
                                </div>
                                <h2 class="font-marketing-display mb-3 text-2xl font-black leading-tight text-stone-900 transition-colors group-hover:text-brand md:text-3xl" x-text="filteredPosts[0].judul"></h2>
                                <p class="mb-5 line-clamp-3 text-sm leading-relaxed text-stone-500" x-text="filteredPosts[0].ringkasan"></p>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-stone-400" x-text="filteredPosts[0].tanggal"></span>
                                    <span class="text-sm font-bold text-brand group-hover:underline">Baca selengkapnya &rarr;</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </template>
            </div>

            <!-- Posts grid -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" x-show="filteredPosts.length > 0">
                <template x-for="post in (searchQuery || activeCategory !== 'semua' ? filteredPosts : filteredPosts.slice(1))" :key="post.slug">
                    <div class="reveal">
                        <a :href="'/blog/' + post.slug" class="blog-card block overflow-hidden rounded-2xl border-2 border-stone-200 bg-[#f5f5f5]">
                            <div class="aspect-[16/9] overflow-hidden">
                                <img :src="post.fotoUrl" :alt="post.judul" class="h-full w-full object-cover">
                            </div>
                            <div class="p-5">
                                <div class="mb-3 flex flex-wrap gap-1.5">
                                    <span class="rounded-full border border-brand-200 bg-brand-50 px-2.5 py-0.5 text-xs font-bold text-brand" x-text="post.kategori"></span>
                                </div>
                                <h3 class="mb-2 line-clamp-2 text-base font-black leading-tight text-stone-900" x-text="post.judul"></h3>
                                <p class="mb-4 line-clamp-3 text-xs leading-relaxed text-stone-500" x-text="post.ringkasan"></p>
                                <div class="flex items-center justify-between border-t border-stone-100 pt-3">
                                    <span class="text-xs text-stone-400" x-text="post.tanggal"></span>
                                    <span class="text-xs font-bold text-brand">Baca &rarr;</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </template>
            </div>

            <!-- Empty state -->
            <div class="py-20 text-center" x-show="filteredPosts.length === 0" x-cloak>
                <h3 class="font-marketing-display mb-2 text-xl font-black text-stone-700" x-text="searchQuery ? 'Artikel tidak ditemukan' : 'Belum ada artikel'"></h3>
                <p class="text-sm text-stone-400" x-text="searchQuery ? 'Coba kata kunci lain.' : 'Artikel akan segera hadir.'"></p>
            </div>
        </section>

        <!-- CTA strip -->
        <section class="bg-brand px-6 py-16">
            <div class="mx-auto flex max-w-4xl flex-col items-center justify-between gap-6 text-center md:flex-row md:text-left">
                <div>
                    <h2 class="font-marketing-display mb-2 text-2xl font-black text-white md:text-3xl">Siap Membangun Lapangan Impian?</h2>
                    <p class="text-sm text-brand-200">Konsultasi gratis dengan tim ahli kami, respons dalam 1 jam!</p>
                </div>
                <a
                    href="https://wa.me/6281357570064?text={{ urlencode('Halo, saya ingin konsultasi lapangan setelah baca blog') }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex flex-shrink-0 items-center gap-2 whitespace-nowrap rounded-xl bg-[#f5f5f5] px-7 py-3.5 text-sm font-bold text-brand shadow-lg transition-colors hover:bg-brand-50"
                >
                    <x-icon name="whatsapp-logo" size="16" class="text-brand" />
                    Konsultasi Gratis Sekarang
                </a>
            </div>
        </section>

        <x-site-footer />

        <script>
            function blogApp() {
                return {
                    posts: @json($artikel),
                    searchQuery: '',
                    activeCategory: 'semua',
                    get categories() {
                        const cats = new Set(this.posts.map((p) => p.kategori));
                        return ['semua', ...Array.from(cats)];
                    },
                    get filteredPosts() {
                        let result = this.posts;
                        if (this.activeCategory !== 'semua') {
                            result = result.filter((p) => p.kategori === this.activeCategory);
                        }
                        if (this.searchQuery.trim()) {
                            const q = this.searchQuery.toLowerCase();
                            result = result.filter((p) => p.judul.toLowerCase().includes(q) || p.ringkasan.toLowerCase().includes(q));
                        }
                        return result;
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
                        this.$el.querySelectorAll('.reveal:not(.visible)').forEach((el) => observer.observe(el));
                    },
                };
            }
        </script>
    </body>
</html>
