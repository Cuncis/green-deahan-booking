<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Green Deahan Sport, Jasa Pembuatan Lapangan Futsal, Mini Soccer, Padel dan Badminton Se-Indonesia</title>

        <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .ticker-wrap { overflow: hidden; }
            .ticker-inner { display: flex; gap: 3.5rem; animation: ticker 28s linear infinite; white-space: nowrap; }
            @keyframes ticker { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

            .reveal { opacity: 0; transform: translateY(22px); transition: opacity .65s ease, transform .65s ease; }
            .reveal.visible { opacity: 1; transform: translateY(0); }

            .faq-answer { max-height: 0; overflow: hidden; transition: max-height .4s ease, padding .3s ease; }
            .faq-answer.open { max-height: 300px; }
        </style>
    </head>
    <body class="bg-[#f7f5f2] font-marketing text-stone-900 antialiased" x-data="{
        revealObserver: null,
        initReveal() {
            this.revealObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        this.revealObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08 });
            this.$el.querySelectorAll('.reveal').forEach((el) => this.revealObserver.observe(el));

            const counterObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    const el = entry.target;
                    const target = parseInt(el.dataset.count ?? '0');
                    let current = 0;
                    const step = Math.ceil(target / 50);
                    const timer = setInterval(() => {
                        current = Math.min(current + step, target);
                        el.textContent = current >= target ? `${target}+` : String(current);
                        if (current >= target) clearInterval(timer);
                    }, 28);
                    counterObserver.unobserve(el);
                });
            }, { threshold: 0.5 });
            this.$el.querySelectorAll('[data-count]').forEach((el) => counterObserver.observe(el));
        },
    }" x-init="initReveal()">

        <x-site-nav />

        <!-- ══════════════ HERO ══════════════ -->
        <section class="mx-auto max-w-6xl px-6 pb-0 pt-28">
            <div class="grid items-center gap-10 pb-16 md:grid-cols-2">
                <div>
                    <span class="mb-5 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">
                        Jasa Pembuatan Lapangan #1 Se-Indonesia
                    </span>
                    <h1 class="font-marketing-display mb-5 text-4xl font-black leading-tight text-stone-900 md:text-5xl lg:text-6xl">
                        Bangun Lapangan Olahraga dari<br>
                        <span class="text-brand">Nol sampai Siap Beroperasi</span>
                    </h1>
                    <p class="mb-6 max-w-lg text-base leading-relaxed text-stone-500 md:text-lg">
                        Satu mitra, semua dikerjakan.
                        <strong class="text-stone-800">Survey, desain, konstruksi, hingga serah terima.</strong>
                        Anda tinggal pantau hasilnya.
                    </p>
                    <div class="mb-8 flex flex-wrap gap-3">
                        @foreach ([
                            ['icon' => 'futsal-goal', 'label' => 'Futsal'],
                            ['icon' => 'soccer-ball', 'label' => 'Mini Soccer'],
                            ['icon' => 'padel-racket', 'label' => 'Padel'],
                            ['icon' => 'shuttlecock', 'label' => 'Badminton'],
                        ] as $sport)
                            <div class="flex items-center gap-2 rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm font-semibold text-stone-700">
                                <x-icon :name="$sport['icon']" size="16" class="text-brand" />
                                {{ $sport['label'] }}
                            </div>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a
                            href="https://wa.me/6281357570064"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-2 rounded-xl bg-brand px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-dark/20 transition-colors hover:bg-brand-dark"
                        >
                            Konsultasi Gratis Sekarang
                        </a>
                        <a
                            href="/galeri"
                            class="rounded-xl border-2 border-brand px-7 py-3.5 text-sm font-bold text-brand transition-colors hover:bg-brand-50"
                        >
                            Lihat Portofolio &rarr;
                        </a>
                    </div>
                    <p class="mt-3 text-xs text-stone-400">*Konsultasi gratis, tanpa kewajiban apapun. Respon dalam 1 jam!</p>
                </div>

                <div class="relative">
                    <img
                        src="https://gdlogin.greendeahan.com/wp-content/uploads/2024/08/photo_24_2024-07-23_10-52-44.jpg"
                        alt="Lapangan Olahraga Green Deahan Sport"
                        class="aspect-[4/3] w-full rounded-2xl border border-stone-200 object-cover shadow-xl"
                    />
                    <div class="absolute -bottom-5 -left-5 flex items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100">
                            <x-icon name="futsal-goal" size="22" class="text-brand" />
                        </div>
                        <div>
                            <div class="font-marketing-display text-xl font-black text-brand">100+</div>
                            <div class="text-xs font-semibold text-stone-500">Proyek Berhasil</div>
                        </div>
                    </div>
                    <div class="absolute -right-4 -top-4 rounded-2xl bg-brand p-3 text-center text-white shadow-lg">
                        <div class="font-marketing-display text-2xl font-black">16+</div>
                        <div class="text-[10px] font-bold uppercase tracking-wide">Tahun</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- TICKER -->
        <div class="ticker-wrap border-y border-brand-dark bg-brand py-3">
            <div class="ticker-inner">
                @for ($i = 0; $i < 2; $i++)
                    @foreach (['FUTSAL', 'MINI SOCCER', 'PADEL', 'BADMINTON', 'GARANSI RESMI', 'SE-INDONESIA', 'KONSULTASI GRATIS', 'SEJAK 2010'] as $kata)
                        <span class="text-sm font-bold tracking-widest text-white">{{ $kata }}</span>
                        <span class="text-sm font-bold tracking-widest text-brand-300">&bull;</span>
                    @endforeach
                @endfor
            </div>
        </div>

        <!-- ══════════════ LAYANAN ══════════════ -->
        <section id="layanan" class="mx-auto max-w-6xl px-6 py-20">
            <div class="reveal mb-14 text-center">
                <span class="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Layanan Kami</span>
                <h2 class="font-marketing-display mb-3 text-3xl font-black text-stone-900 md:text-4xl">4 Jenis Lapangan yang Kami Bangun</h2>
                <p class="mx-auto max-w-xl text-sm text-stone-500 md:text-base">Semua dikerjakan oleh tim profesional berpengalaman dengan material berkualitas dan garansi resmi.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                @foreach ([
                    [
                        'nama' => 'Lapangan Futsal',
                        'badge' => 'Paling Populer',
                        'badgeClass' => 'text-brand bg-brand-50 border-brand-200',
                        'gambar' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2024/07/IMG-20181216-WA0019.jpg',
                        'deskripsi' => 'Konstruksi lapangan futsal standar internasional. Pilih lantai <strong>interlock</strong> atau <strong>rumput sintetis</strong>. Sudah termasuk pencahayaan, pagar, gawang, net, dan fasilitas pendukung lengkap.',
                        'checklist' => ['Lantai interlock atau rumput sintetis', 'Sistem pencahayaan LED standar pertandingan', 'Pagar, gawang, net termasuk', 'Ruang ganti & tribun (opsional)', 'Garansi konstruksi & material'],
                        'topik' => 'pembuatan lapangan futsal',
                    ],
                    [
                        'nama' => 'Mini Soccer',
                        'badge' => 'Outdoor & Indoor',
                        'badgeClass' => 'text-blue-700 bg-blue-50 border-blue-200',
                        'gambar' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/minisoccer2-img.png',
                        'deskripsi' => 'Lapangan mini soccer dengan rumput sintetis premium grade A. Sistem drainase profesional agar tidak tergenang saat hujan. Material bisa disesuaikan dengan <strong>budget Anda</strong>.',
                        'checklist' => ['Rumput sintetis premium grade A/B', 'Sistem drainase profesional', 'Tribun penonton & pagar keliling', 'Pencahayaan malam hari (opsional)', 'Cocok outdoor & indoor'],
                        'topik' => 'pembuatan lapangan mini soccer',
                    ],
                    [
                        'nama' => 'Lapangan Padel',
                        'badge' => 'Tren Terbaru 2025',
                        'badgeClass' => 'text-orange-700 bg-orange-50 border-orange-200',
                        'gambar' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/padel1-img-1-1.png',
                        'deskripsi' => 'Spesialis konstruksi lapangan padel standar internasional (IFF). Dari struktur rangka baja, kaca tempered, hingga lantai artificial grass padel, semua <strong>all-in-one service</strong>.',
                        'checklist' => ['Rangka baja galvanis anti karat', 'Kaca tempered safety glass', 'Artificial grass khusus padel', 'Standar internasional IFF', 'Bisa indoor & outdoor'],
                        'topik' => 'pembuatan lapangan padel',
                    ],
                    [
                        'nama' => 'Lapangan Badminton',
                        'badge' => 'Indoor Specialist',
                        'badgeClass' => 'text-purple-700 bg-purple-50 border-purple-200',
                        'gambar' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/badminton2-img-1.png',
                        'deskripsi' => 'Konstruksi lapangan badminton dengan lantai <strong>interlock</strong> atau <strong>kayu keras</strong> berkualitas. Non-slip, ramah sendi lutut. Pencahayaan LED anti-silau.',
                        'checklist' => ['Lantai interlock atau kayu keras', 'Non-slip & ramah lutut', 'Pencahayaan LED anti-silau', 'Tiang net & net standar BWF', '1 atau multi-court tersedia'],
                        'topik' => 'pembuatan lapangan badminton',
                    ],
                ] as $layanan)
                    <div class="reveal overflow-hidden rounded-2xl border border-stone-200 bg-white transition-all hover:border-brand hover:shadow-xl">
                        <img src="{{ $layanan['gambar'] }}" alt="{{ $layanan['nama'] }}" class="h-52 w-full object-cover">
                        <div class="p-6">
                            <span class="inline-block rounded-full border px-2.5 py-1 text-xs font-bold {{ $layanan['badgeClass'] }}">{{ $layanan['badge'] }}</span>
                            <h3 class="font-marketing-display mt-2 text-2xl font-black text-stone-900">{{ $layanan['nama'] }}</h3>
                            <p class="mb-4 mt-3 text-sm leading-relaxed text-stone-500">{!! $layanan['deskripsi'] !!}</p>
                            <ul class="mb-5 space-y-1.5 text-sm text-stone-600">
                                @foreach ($layanan['checklist'] as $item)
                                    <li class="flex items-center gap-2">
                                        <x-icon name="check-circle" size="15" class="text-brand flex-shrink-0" />
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                            <a
                                href="https://wa.me/6281357570064?text={{ urlencode("Halo, saya ingin konsultasi {$layanan['topik']}") }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block rounded-xl bg-brand py-3 text-center text-sm font-bold text-white transition-colors hover:bg-brand-dark"
                            >Tanya Harga {{ $layanan['nama'] }} &rarr;</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- ══════════════ PROSES ══════════════ -->
        <section class="border-y border-stone-200 bg-white px-6 py-20">
            <div class="mx-auto max-w-6xl">
                <div class="reveal mb-14 text-center">
                    <span class="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Cara Kerja</span>
                    <h2 class="font-marketing-display mb-3 text-3xl font-black text-stone-900 md:text-4xl">Proses Mudah, Anda Cukup Duduk Santai</h2>
                    <p class="mx-auto max-w-xl text-sm text-stone-500">Kami handle semua proses dari awal sampai lapangan siap beroperasi. Transparansi penuh di setiap tahap.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ([
                        ['no' => '01', 'judul' => 'Konsultasi Gratis', 'deskripsi' => 'Ceritakan kebutuhan, lokasi, dan budget. Tim kami respon dalam 1 jam.'],
                        ['no' => '02', 'judul' => 'Survey & Desain', 'deskripsi' => 'Tim kami datang ke lokasi, ukur lahan, dan buat desain lapangan sesuai kebutuhan.'],
                        ['no' => '03', 'judul' => 'Penawaran Harga', 'deskripsi' => 'RAB & penawaran harga detail dikirim. Transparan, tanpa biaya tersembunyi.'],
                        ['no' => '04', 'judul' => 'Konstruksi', 'deskripsi' => 'Pengerjaan oleh tim ahli, tepat waktu. Anda bisa pantau progress setiap hari.'],
                    ] as $langkah)
                        <div class="reveal rounded-2xl border border-stone-200 bg-[#f7f5f2] p-6 text-center transition-colors hover:border-brand">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand text-white">
                                <span class="font-marketing-display text-xl font-black">{{ $langkah['no'] }}</span>
                            </div>
                            <h3 class="mb-1 text-sm font-bold text-stone-900">{{ $langkah['judul'] }}</h3>
                            <p class="text-xs leading-relaxed text-stone-500">{{ $langkah['deskripsi'] }}</p>
                        </div>
                    @endforeach
                    <div class="reveal rounded-2xl bg-brand p-6 text-center text-white">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20">
                            <span class="font-marketing-display text-xl font-black">05</span>
                        </div>
                        <h3 class="mb-1 text-sm font-bold">Lapangan Siap!</h3>
                        <p class="text-xs leading-relaxed text-brand-200">Serah terima lapangan siap pakai. Pelunasan setelah 100% selesai. Garansi aktif!</p>
                    </div>
                </div>

                <div class="reveal mt-8 flex flex-col items-center gap-4 rounded-2xl border border-brand-200 bg-brand-50 p-5 md:flex-row">
                    <p class="text-sm leading-relaxed text-stone-700">
                        <strong>Sistem Pembayaran Aman:</strong> Bayar uang muka (DP) di awal, pelunasan dilakukan <strong>setelah lapangan selesai 100%</strong>.
                        Anda tidak perlu khawatir, kami yang menanggung risiko pengerjaannya.
                    </p>
                    <a
                        href="https://wa.me/6281357570064"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex-shrink-0 whitespace-nowrap rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-dark"
                    >Mulai Sekarang &rarr;</a>
                </div>
            </div>
        </section>

        <!-- ══════════════ STATISTIK ══════════════ -->
        <section class="bg-brand px-6 py-16">
            <div class="mx-auto max-w-6xl">
                <div class="grid grid-cols-2 gap-6 text-center text-white md:grid-cols-4">
                    <div class="reveal">
                        <div class="font-marketing-display mb-1 text-5xl font-black" data-count="16">0</div>
                        <div class="text-xs font-bold uppercase tracking-widest text-brand-300">+ Tahun Pengalaman</div>
                    </div>
                    <div class="reveal">
                        <div class="font-marketing-display mb-1 text-5xl font-black" data-count="100">0</div>
                        <div class="text-xs font-bold uppercase tracking-widest text-brand-300">+ Proyek Selesai</div>
                    </div>
                    <div class="reveal">
                        <div class="font-marketing-display mb-1 text-5xl font-black" data-count="15">0</div>
                        <div class="text-xs font-bold uppercase tracking-widest text-brand-300">+ Kota di Indonesia</div>
                    </div>
                    <div class="reveal">
                        <div class="font-marketing-display mb-1 text-5xl font-black">24/7</div>
                        <div class="text-xs font-bold uppercase tracking-widest text-brand-300">Support &amp; Konsultasi</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════ PORTOFOLIO ══════════════ -->
        <section id="portofolio" class="mx-auto max-w-6xl px-6 py-20">
            <div class="reveal mb-12 flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <span class="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Portofolio</span>
                    <h2 class="font-marketing-display text-3xl font-black text-stone-900 md:text-4xl">Hasil Nyata dari Proyek Kami</h2>
                    <p class="mt-2 text-sm text-stone-500">Sudah dikerjakan di berbagai kota seluruh Indonesia</p>
                </div>
                <a
                    href="/galeri"
                    class="flex-shrink-0 rounded-xl border-2 border-brand px-6 py-2.5 text-sm font-bold text-brand transition-colors hover:bg-brand-50"
                >
                    Lihat Semua Galeri &rarr;
                </a>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['gambar' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/futsal-img4-1.png', 'kategori' => 'Futsal', 'warna' => 'text-brand', 'judul' => 'Futsal Arena Jakarta Selatan', 'deskripsi' => 'Lantai interlock + pencahayaan LED. Selesai dalam 21 hari kerja.'],
                    ['gambar' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/04/minisoccer-img2.png', 'kategori' => 'Mini Soccer', 'warna' => 'text-blue-600', 'judul' => 'Mini Soccer Outdoor Surabaya', 'deskripsi' => 'Rumput sintetis premium + tribun penonton. Kapasitas 200 orang.'],
                    ['gambar' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/padel1-img.png', 'kategori' => 'Padel', 'warna' => 'text-orange-600', 'judul' => 'Padel Premium Medan', 'deskripsi' => 'Standar internasional IFF. Kaca tempered, rangka baja galvanis.'],
                    ['gambar' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/badminton1-img.png', 'kategori' => 'Badminton', 'warna' => 'text-purple-600', 'judul' => 'Badminton 4 Court Bandung', 'deskripsi' => 'Lantai interlock, LED anti-silau. 4 court dalam 1 gedung.'],
                    ['gambar' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/futsal1-outdoor.png', 'kategori' => 'Futsal', 'warna' => 'text-brand', 'judul' => 'Futsal Outdoor Makassar', 'deskripsi' => 'Rumput sintetis tahan cuaca, sistem drainase anti banjir.'],
                    ['gambar' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/04/minisoccer-img3.png', 'kategori' => 'Mini Soccer', 'warna' => 'text-blue-600', 'judul' => 'Mini Soccer Indoor Semarang', 'deskripsi' => 'Full roof + pencahayaan. Bisa dipakai malam hari non-stop.'],
                ] as $proyek)
                    <div class="reveal group flex flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white transition-all hover:shadow-lg">
                        <div class="h-52 overflow-hidden">
                            <img
                                src="{{ $proyek['gambar'] }}"
                                alt="{{ $proyek['judul'] }}"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                        </div>
                        <div class="flex-grow p-4">
                            <span class="text-xs font-bold {{ $proyek['warna'] }}">{{ $proyek['kategori'] }}</span>
                            <h4 class="mt-1 font-bold text-stone-900">{{ $proyek['judul'] }}</h4>
                            <p class="mt-1 text-xs text-stone-500">{{ $proyek['deskripsi'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="reveal mt-8 text-center">
                <a
                    href="/galeri"
                    class="inline-block rounded-xl border-2 border-brand px-8 py-3 font-bold text-brand transition-colors hover:bg-brand-50"
                >
                    Lihat 50+ Proyek Lainnya &rarr;
                </a>
            </div>
        </section>

        <!-- ══════════════ KEUNGGULAN ══════════════ -->
        <section class="border-y border-stone-200 bg-white px-6 py-20">
            <div class="mx-auto max-w-6xl">
                <div class="grid items-center gap-14 md:grid-cols-2">
                    <div class="reveal">
                        <span class="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Kenapa Pilih Kami?</span>
                        <h2 class="font-marketing-display mb-8 text-3xl font-black text-stone-900 md:text-4xl">Dipercaya Ratusan Pemilik<br>Bisnis Lapangan di Indonesia</h2>
                        <div class="space-y-4">
                            @foreach ([
                                ['icon' => 'build-hammer', 'judul' => 'Dari Nol Sampai Jadi, Anda Santai Saja', 'deskripsi' => 'Survey, desain, perizinan, konstruksi, finishing, semua kami urus. Anda tidak perlu pusing koordinasi banyak kontraktor.'],
                                ['icon' => 'shield', 'judul' => 'Garansi Konstruksi & Material Resmi', 'deskripsi' => 'Setiap proyek dilindungi garansi resmi. Jika ada masalah setelah selesai, tim kami langsung turun tangan, tanpa biaya tambahan.'],
                                ['icon' => 'wallet', 'judul' => 'Harga Transparan, Sesuai Budget Anda', 'deskripsi' => 'RAB detail dikirim sebelum mulai. Material bisa disesuaikan budget, interlock atau rumput sintetis, indoor atau outdoor.'],
                                ['icon' => 'zap', 'judul' => 'Pengerjaan Cepat & Tepat Waktu', 'deskripsi' => 'Jadwal pengerjaan tertulis di kontrak. Kami komitmen selesai tepat waktu, keterlambatan adalah tanggung jawab kami.'],
                                ['icon' => 'location-pin', 'judul' => 'Melayani Seluruh Indonesia', 'deskripsi' => 'Tim kami siap bergerak ke mana pun Anda butuhkan, Jawa, Sumatera, Kalimantan, Sulawesi, dan seluruh wilayah Indonesia.'],
                            ] as $keunggulan)
                                <div class="flex items-start gap-4 rounded-xl border border-stone-100 p-4 transition-all hover:border-brand hover:bg-brand-50/50">
                                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-brand-100">
                                        <x-icon :name="$keunggulan['icon']" size="20" class="text-brand" />
                                    </div>
                                    <div>
                                        <h4 class="mb-1 font-bold text-stone-900">{{ $keunggulan['judul'] }}</h4>
                                        <p class="text-sm leading-relaxed text-stone-500">{{ $keunggulan['deskripsi'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="reveal space-y-5">
                        <img
                            src="https://gdlogin.greendeahan.com/wp-content/uploads/2026/04/hero-img2.png"
                            alt="Tim Green Deahan Sedang Bekerja"
                            class="aspect-[4/3] w-full rounded-2xl border border-stone-200 object-cover"
                        />
                        <div class="rounded-2xl border border-stone-200 bg-[#f7f5f2] p-5">
                            <p class="mb-4 text-xs font-bold uppercase tracking-widest text-stone-400">Cakupan Wilayah Layanan</p>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach (['Jabodetabek', 'Jawa Tengah & Jawa Timur', 'Seluruh wilayah Sumatera', 'Seluruh wilayah Kalimantan', 'Seluruh wilayah Sulawesi', 'Bali & Nusa Tenggara (NTB & NTT)', 'Maluku & Papua'] as $wilayah)
                                    <div class="flex items-center gap-2 text-sm font-semibold text-stone-700">
                                        <x-icon name="check-circle" size="14" class="text-brand flex-shrink-0" />
                                        {{ $wilayah }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════ TESTIMONI ══════════════ -->
        <section class="mx-auto max-w-6xl px-6 py-20">
            <div class="reveal mb-14 text-center">
                <span class="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Testimoni Klien</span>
                <h2 class="font-marketing-display mb-2 text-3xl font-black text-stone-900 md:text-4xl">Apa Kata Klien Kami?</h2>
                <p class="text-sm text-stone-500">Kepercayaan klien adalah prioritas utama kami</p>
                <div class="mt-3 flex items-center justify-center gap-1">
                    <span class="flex items-center gap-0.5 text-yellow-400">
                        @for ($i = 0; $i < 5; $i++)
                            <x-icon name="star" size="18" />
                        @endfor
                    </span>
                    <span class="ml-2 font-bold text-stone-800">5.0</span>
                    <span class="ml-1 text-sm text-stone-400">dari 100+ ulasan</span>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['foto' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/foto1.webp', 'nama' => 'Budi Santoso', 'peran' => 'Owner Futsal, Jakarta Selatan', 'ulasan' => 'Awalnya ragu karena project dari nol, tapi GreenDeahan handle semuanya dengan profesional. Lapangan futsal saya sudah balik modal dalam 8 bulan.'],
                    ['foto' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/foto2.jpg', 'nama' => 'Ahmad Wijaya', 'peran' => 'Pengusaha, Surabaya', 'ulasan' => 'Tim yang sangat responsif! Konsultasi gratisnya detail banget, bantu pilih material sesuai budget. Lapangan mini soccer saya jadi yang paling ramai di daerah. Recommended 100%!'],
                    ['foto' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2026/05/foto3.jpg', 'nama' => 'Ricky Pratama', 'peran' => 'Investor, Medan', 'ulasan' => 'Proyek lapangan padel pertama di kota kami dikerjakan GreenDeahan. Dari desain sampai konstruksi standar internasional. Garansi dan after sales service-nya juga top!'],
                ] as $testimoni)
                    <div class="reveal rounded-2xl border border-stone-200 bg-white p-6 transition-shadow hover:shadow-lg">
                        <div class="mb-4 flex items-center gap-0.5 text-yellow-400">
                            @for ($i = 0; $i < 5; $i++)
                                <x-icon name="star" size="16" />
                            @endfor
                        </div>
                        <p class="mb-5 text-sm italic leading-relaxed text-stone-700">&ldquo;{{ $testimoni['ulasan'] }}&rdquo;</p>
                        <div class="flex items-center gap-3 border-t border-stone-100 pt-4">
                            <img src="{{ $testimoni['foto'] }}" alt="{{ $testimoni['nama'] }}" class="h-11 w-11 flex-shrink-0 rounded-full object-cover">
                            <div>
                                <div class="text-sm font-bold text-stone-900">{{ $testimoni['nama'] }}</div>
                                <div class="text-xs text-stone-400">{{ $testimoni['peran'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- ══════════════ FAQ ══════════════ -->
        <section class="border-y border-stone-200 bg-white px-6 py-20">
            <div class="mx-auto max-w-3xl">
                <div class="reveal mb-12 text-center">
                    <span class="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">FAQ</span>
                    <h2 class="font-marketing-display mb-2 text-3xl font-black text-stone-900 md:text-4xl">Pertanyaan yang Sering Ditanyakan</h2>
                    <p class="text-sm text-stone-500">
                        Tidak menemukan jawaban yang Anda cari?
                        <a href="https://wa.me/6281357570064" class="font-semibold text-brand underline">Tanya langsung via WhatsApp.</a>
                    </p>
                </div>

                <div class="reveal space-y-3" x-data="{ open: null }">
                    @foreach ([
                        ['q' => 'Apakah GreenDeahan mengerjakan proyek dari nol?', 'a' => 'Ya, kami mengerjakan proyek dari nol sampai selesai. Mulai dari konsultasi awal, survey lokasi, desain, perizinan, pengadaan material, konstruksi, hingga lapangan siap beroperasi. Anda tidak perlu koordinasi dengan banyak pihak, cukup hubungi kami saja.'],
                        ['q' => 'Lantai apa saja yang tersedia dan apa bedanya?', 'a' => 'Kami menyediakan: <strong>Lantai Interlock</strong>, awet, mudah diperbaiki jika rusak sebagian, cocok indoor, harga lebih terjangkau. <strong>Rumput Sintetis</strong>, estetik, nyaman untuk outdoor, tahan cuaca. Khusus badminton/indoor tersedia interlock non-slip dan ramah lutut. <strong>Artificial Grass Padel</strong>, khusus lapangan padel standar internasional.'],
                        ['q' => 'Berapa lama waktu pengerjaan konstruksi lapangan?', 'a' => 'Tergantung jenis dan skala proyek: <strong>Futsal 1 lapangan</strong> sekitar 14 sampai 21 hari. <strong>Mini Soccer</strong> sekitar 21 sampai 30 hari. <strong>Padel</strong> sekitar 14 sampai 21 hari. <strong>Badminton multi-court</strong> sekitar 21 sampai 35 hari. Jadwal tepat ditentukan saat survey dan tercantum dalam kontrak resmi.'],
                        ['q' => 'Apakah ada garansi setelah lapangan selesai?', 'a' => 'Ya, semua proyek dilengkapi garansi resmi untuk konstruksi dan material. Jika ada kerusakan atau masalah akibat pengerjaan, tim kami akan datang dan memperbaiki tanpa biaya tambahan. Detail garansi tertulis jelas di kontrak perjanjian.'],
                        ['q' => 'Bagaimana sistem pembayarannya?', 'a' => 'Sistem pembayaran kami aman dan transparan. Bayar uang muka (DP) setelah sepakat di kontrak, lalu progress payment sesuai milestone pengerjaan (opsional untuk proyek besar). <strong>Pelunasan dilakukan SETELAH lapangan selesai 100% dan Anda puas.</strong> Tidak ada biaya tersembunyi.'],
                        ['q' => 'Apakah melayani daerah luar Jawa?', 'a' => 'Kami melayani seluruh Indonesia, meliputi Jabodetabek, Jawa Tengah & Timur, Sumatera, Kalimantan, Sulawesi, Bali, NTB, dan wilayah lainnya. Tim lapangan kami akan bergerak ke lokasi Anda. Konsultasikan dulu kebutuhan dan lokasi Anda via WhatsApp.'],
                    ] as $i => $faq)
                        <div class="overflow-hidden rounded-xl border border-stone-200 bg-[#f7f5f2]">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between p-5 text-left text-sm font-semibold text-stone-900"
                                x-on:click="open = open === {{ $i }} ? null : {{ $i }}"
                            >
                                {{ $faq['q'] }}
                                <span class="ml-4 flex-shrink-0 text-lg font-black text-brand" x-text="open === {{ $i }} ? '−' : '+'"></span>
                            </button>
                            <div class="faq-answer px-5 text-sm leading-relaxed text-stone-600" :class="{ open: open === {{ $i }} }">
                                <div class="pb-5">{!! $faq['a'] !!}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- ══════════════ TENTANG ══════════════ -->
        <section class="mx-auto max-w-6xl px-6 py-20">
            <div class="grid items-center gap-14 md:grid-cols-2">
                <div class="reveal">
                    <img
                        src="https://gdlogin.greendeahan.com/wp-content/uploads/2026/04/profile-img1.jpg"
                        alt="Kantor dan Workshop Green Deahan Sport"
                        class="aspect-[4/3] w-full rounded-2xl border border-stone-200 object-cover"
                    />
                </div>
                <div class="reveal">
                    <span class="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Green Deahan Sport</span>
                    <h2 class="font-marketing-display mb-4 text-3xl font-black text-stone-900 md:text-4xl">Spesialis Lapangan Olahraga Sejak 2010</h2>
                    <p class="mb-4 text-sm leading-relaxed text-stone-600">
                        Green Deahan adalah jasa pembuatan lapangan olahraga profesional yang melayani seluruh Indonesia sejak tahun 2010.
                        Dengan pengalaman lebih dari <strong>16 tahun</strong>, kami spesialis dalam konstruksi lapangan futsal, mini soccer, padel, dan badminton.
                    </p>
                    <p class="mb-6 text-sm leading-relaxed text-stone-600">
                        Kami menawarkan layanan <strong>turnkey</strong> dari konsultasi awal, desain, perizinan, konstruksi, hingga lapangan siap beroperasi.
                        Anda cukup duduk dan pantau progress, kami yang kerjakan semuanya.
                    </p>
                    <div class="mb-6 flex flex-wrap gap-4">
                        @foreach ([['16+', 'Tahun Pengalaman'], ['100+', 'Proyek Selesai'], ['15+', 'Kota di Indonesia']] as [$angka, $label])
                            <div class="rounded-xl border border-stone-200 bg-[#f7f5f2] px-5 py-3 text-center">
                                <div class="font-marketing-display text-3xl font-black text-brand">{{ $angka }}</div>
                                <div class="text-xs font-semibold text-stone-500">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════ CTA FINAL ══════════════ -->
        <section class="bg-brand px-6 py-20">
            <div class="mx-auto max-w-3xl text-center">
                <span class="mb-5 inline-block rounded-full bg-white/20 px-3 py-1.5 text-xs font-bold tracking-wide text-white">Siap Mulai Proyek Anda?</span>
                <h2 class="font-marketing-display mb-4 text-3xl font-black leading-tight text-white md:text-5xl">
                    Wujudkan Lapangan<br>Impian Anda Sekarang!
                </h2>
                <p class="mx-auto mb-3 max-w-xl text-base leading-relaxed text-brand-200">
                    Konsultasi <strong class="text-white">GRATIS</strong>, survey lokasi, desain profesional, konstruksi berkualitas.
                    Garansi resmi &amp; pelunasan setelah lapangan 100% selesai.
                </p>
                <div class="mt-6 flex flex-col justify-center gap-4 sm:flex-row">
                    <a
                        href="https://wa.me/6281357570064?text={{ urlencode('Halo GreenDeahan, saya ingin konsultasi pembuatan lapangan') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center justify-center gap-2 rounded-xl bg-white px-8 py-4 text-sm font-black text-brand shadow-xl transition-colors hover:bg-brand-50"
                    >
                        <x-icon name="whatsapp-logo" size="16" class="text-brand" />
                        Chat WhatsApp Sekarang
                    </a>
                    <a
                        href="tel:+6281357570064"
                        class="flex items-center justify-center gap-2 rounded-xl border-2 border-white px-8 py-4 text-sm font-bold text-white transition-colors hover:bg-white/10"
                    >
                        +62 813-5757-0064
                    </a>
                </div>
                <p class="mt-5 text-xs text-brand-400">*Konsultasi 100% gratis, tidak ada kewajiban apapun</p>
            </div>
        </section>

        <x-site-footer />

    </body>
</html>
