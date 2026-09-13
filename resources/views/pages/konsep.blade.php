<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Konsep Sport Center, Green Deahan Sport</title>

        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .reveal { opacity: 0; transform: translateY(22px); transition: opacity .65s ease, transform .65s ease; }
            .reveal.visible { opacity: 1; transform: translateY(0); }
        </style>
    </head>
    <body class="bg-[#f7f5f2] font-marketing text-stone-900 antialiased" x-data="{}" x-init="
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });
        $el.querySelectorAll('.reveal').forEach((el) => observer.observe(el));
    ">

        <x-site-nav />

        <!-- Page Hero -->
        <section class="mx-auto max-w-6xl px-6 pb-10 pt-28">
            <div class="reveal mx-auto max-w-3xl text-center">
                <span class="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">
                    Panduan Sebelum Membangun
                </span>
                <h1 class="font-marketing-display mb-4 text-4xl font-black leading-tight text-stone-900 md:text-5xl">
                    Konsep Sarana Sport Center yang Ideal untuk Bisnis Anda di Masa Depan
                </h1>
                <p class="mx-auto max-w-2xl text-base leading-relaxed text-stone-500 md:text-lg">
                    Sebelum mulai konstruksi, kenali dulu jenis konsep sport center, kebutuhan lahan, dan potensi bisnisnya. Supaya investasi Anda tepat sasaran sejak awal.
                </p>
            </div>
        </section>

        <!-- 3 Konsep -->
        <section class="mx-auto mb-16 max-w-6xl px-6">
            <div class="reveal mb-10 text-center">
                <span class="mb-3 inline-block text-xs font-bold uppercase tracking-widest text-brand">Pilihan Konsep</span>
                <h2 class="font-marketing-display text-3xl font-black text-stone-900 md:text-4xl">3 Konsep Sport Center yang Bisa Anda Pilih</h2>
                <p class="mx-auto mt-3 max-w-xl text-sm text-stone-500">Setiap konsep punya kebutuhan modal, lahan, dan target pasar yang berbeda. Sesuaikan dengan lokasi dan budget Anda.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                @foreach ([
                    [
                        'icon' => 'futsal-goal',
                        'nama' => 'Fokus Satu Cabang Olahraga',
                        'cocok' => 'Cocok untuk pemula, lahan terbatas',
                        'deskripsi' => 'Bangun satu jenis lapangan saja, misalnya futsal atau padel. Modal lebih ringan, proses pembangunan lebih cepat, dan lebih mudah dikelola untuk pemilik bisnis baru.',
                        'poin' => ['Kebutuhan lahan paling kecil', 'Modal awal lebih terjangkau', 'Cocok dites di satu lokasi dulu'],
                    ],
                    [
                        'icon' => 'layers',
                        'nama' => 'Multi-Court Kompleks',
                        'cocok' => 'Cocok untuk jangkau banyak segmen',
                        'deskripsi' => '2 sampai 4 lapangan dengan jenis olahraga berbeda dalam satu lokasi. Menjangkau lebih banyak komunitas sekaligus dan jam sibuk satu cabang bisa tertutup cabang lain.',
                        'poin' => ['Pendapatan dari beberapa segmen', 'Jam operasional lebih merata', 'Butuh lahan dan modal lebih besar'],
                    ],
                    [
                        'icon' => 'stadium',
                        'nama' => 'Sport Center Lifestyle',
                        'cocok' => 'Cocok untuk lokasi strategis perkotaan',
                        'deskripsi' => 'Lapangan dipadukan dengan area tongkrongan, kafe, atau tribun penonton. Pengunjung tidak cuma booking main, tapi juga menghabiskan waktu dan uang di tempat.',
                        'poin' => ['Ada pendapatan tambahan dari F&B', 'Nilai sewa tempat untuk event lebih tinggi', 'Butuh perencanaan desain lebih matang'],
                    ],
                ] as $konsep)
                    <div class="reveal rounded-2xl border border-stone-200 bg-white p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-brand-100">
                            <x-icon :name="$konsep['icon']" size="22" class="text-brand" />
                        </div>
                        <span class="mb-2 inline-block text-xs font-bold uppercase tracking-wide text-brand">{{ $konsep['cocok'] }}</span>
                        <h3 class="font-marketing-display mb-2 text-lg font-black text-stone-900">{{ $konsep['nama'] }}</h3>
                        <p class="mb-4 text-sm leading-relaxed text-stone-500">{{ $konsep['deskripsi'] }}</p>
                        <ul class="space-y-2 text-sm text-stone-600">
                            @foreach ($konsep['poin'] as $poin)
                                <li class="flex items-start gap-2">
                                    <x-icon name="check-circle" size="16" class="mt-0.5 flex-shrink-0 text-brand" />
                                    {{ $poin }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Kebutuhan Lahan -->
        <section class="bg-white px-6 py-16">
            <div class="mx-auto max-w-6xl">
                <div class="reveal mb-10 text-center">
                    <span class="mb-3 inline-block text-xs font-bold uppercase tracking-widest text-brand">Perencanaan Lahan</span>
                    <h2 class="font-marketing-display text-3xl font-black text-stone-900 md:text-4xl">Kisaran Kebutuhan Lahan per Jenis Lapangan</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-stone-500">Ukuran final tergantung hasil survey lokasi, tapi ini gambaran umum untuk bantu Anda memperkirakan lahan yang dibutuhkan.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['icon' => 'futsal-goal', 'nama' => 'Futsal', 'ukuran' => '15 x 25 m sampai 16 x 34 m', 'catatan' => 'Ukuran standar lapangan indoor'],
                        ['icon' => 'soccer-ball', 'nama' => 'Mini Soccer', 'ukuran' => '25 x 40 m sampai 30 x 50 m', 'catatan' => 'Ditambah area tribun bila perlu'],
                        ['icon' => 'padel-racket', 'nama' => 'Padel', 'ukuran' => '10 x 20 m per lapangan', 'catatan' => 'Ukuran standar internasional'],
                        ['icon' => 'shuttlecock', 'nama' => 'Badminton', 'ukuran' => '6.1 x 13.4 m per lapangan', 'catatan' => 'Perlu ruang sirkulasi tambahan'],
                    ] as $item)
                        <div class="reveal rounded-2xl border border-stone-200 p-5">
                            <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50">
                                <x-icon :name="$item['icon']" size="20" class="text-brand" />
                            </div>
                            <h3 class="font-marketing-display mb-1 text-base font-black text-stone-900">{{ $item['nama'] }}</h3>
                            <p class="mb-1 text-sm font-bold text-brand">{{ $item['ukuran'] }}</p>
                            <p class="text-xs text-stone-500">{{ $item['catatan'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Kenapa Menjanjikan -->
        <section class="mx-auto my-16 max-w-6xl px-6">
            <div class="reveal mb-10 text-center">
                <span class="mb-3 inline-block text-xs font-bold uppercase tracking-widest text-brand">Potensi Bisnis</span>
                <h2 class="font-marketing-display text-3xl font-black text-stone-900 md:text-4xl">Kenapa Sport Center Semakin Diminati</h2>
                <p class="mx-auto mt-3 max-w-xl text-sm text-stone-500">Bukan cuma soal olahraga. Ini beberapa alasan bisnis sport center terus dilirik para investor.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['icon' => 'chart-trend', 'judul' => 'Pendapatan Berulang', 'deskripsi' => 'Model booking per jam atau membership membuat pendapatan datang terus tiap minggu, bukan cuma transaksi sekali jalan.'],
                    ['icon' => 'user-group', 'judul' => 'Komunitas yang Loyal', 'deskripsi' => 'Olahraga seperti padel, futsal, dan badminton punya komunitas rutin yang booking jadwal tetap tiap minggu.'],
                    ['icon' => 'wallet', 'judul' => 'Bisa Mulai Bertahap', 'deskripsi' => 'Anda bisa mulai dari satu lapangan dulu, lalu tambah cabang atau jenis lapangan lain setelah bisnis berjalan.'],
                ] as $alasan)
                    <div class="reveal flex gap-4 rounded-2xl border border-stone-200 bg-white p-6">
                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-brand-100">
                            <x-icon :name="$alasan['icon']" size="20" class="text-brand" />
                        </div>
                        <div>
                            <h3 class="font-marketing-display mb-1 text-base font-black text-stone-900">{{ $alasan['judul'] }}</h3>
                            <p class="text-sm leading-relaxed text-stone-500">{{ $alasan['deskripsi'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="reveal mt-6 text-center text-xs text-stone-400">
                Estimasi biaya dan potensi pendapatan berbeda-beda tergantung lokasi, ukuran, dan cara pengelolaan. Konsultasikan dengan tim kami untuk RAB dan estimasi yang sesuai kondisi Anda.
            </p>
        </section>

        <x-sports-planner />

        <!-- CTA strip -->
        <section class="bg-brand px-6 py-16">
            <div class="mx-auto flex max-w-4xl flex-col items-center justify-between gap-6 text-center md:flex-row md:text-left">
                <div>
                    <h2 class="font-marketing-display mb-2 text-2xl font-black text-white md:text-3xl">Siap Wujudkan Konsep Sport Center Anda?</h2>
                    <p class="text-sm text-brand-200">Konsultasi gratis dengan tim kami, kami bantu tentukan konsep paling sesuai untuk lokasi Anda.</p>
                </div>
                <div class="flex flex-shrink-0 flex-col gap-3 sm:flex-row">
                    <a
                        href="https://wa.me/6281357570064?text={{ urlencode('Halo, saya ingin konsultasi konsep sport center untuk lokasi saya') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-white px-7 py-3.5 text-sm font-bold text-brand transition-colors hover:bg-brand-50"
                    >
                        <x-icon name="whatsapp-logo" size="16" class="text-brand" />
                        Konsultasi Gratis
                    </a>
                    <a
                        href="/galeri"
                        class="whitespace-nowrap rounded-xl border-2 border-white px-7 py-3.5 text-center text-sm font-bold text-white transition-colors hover:bg-white/10"
                    >
                        Lihat Portofolio
                    </a>
                </div>
            </div>
        </section>

        <x-site-footer />
    </body>
</html>
