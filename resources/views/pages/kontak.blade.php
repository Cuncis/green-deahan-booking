<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Kontak Kami, Green Deahan Sport</title>

        <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .reveal { opacity: 0; transform: translateY(22px); transition: opacity .65s ease, transform .65s ease; }
            .reveal.visible { opacity: 1; transform: translateY(0); }
            .social-card { transition: transform .22s, box-shadow .22s, border-color .22s; }
            .social-card:hover { transform: translateY(-4px); box-shadow: 0 14px 36px rgba(0,0,0,.12); }
            .faq-answer { max-height: 0; overflow: hidden; transition: max-height .4s ease, padding .3s ease; }
            .faq-answer.open { max-height: 300px; }
        </style>
    </head>
    <body class="bg-[#f7f5f2] font-marketing text-stone-900 antialiased" x-data="kontakApp()" x-init="initReveal()">

        <x-site-nav :nav-links="[
            ['label' => 'Beranda', 'href' => '/'],
            ['label' => 'Galeri', 'href' => '/galeri'],
            ['label' => 'Blog', 'href' => '/blog'],
            ['label' => 'Kontak', 'href' => '/kontak'],
        ]" />

        <!-- Page Hero -->
        <section class="mx-auto max-w-6xl px-6 pb-10 pt-28">
            <div class="reveal mx-auto max-w-2xl text-center">
                <span class="mb-4 inline-flex items-center rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">
                    <span class="mr-1.5 inline-block h-2 w-2 animate-pulse rounded-full bg-green-500 align-middle"></span>
                    Tim Kami Online, Respon dalam 1 Jam
                </span>
                <h1 class="font-marketing-display mb-4 text-4xl font-black leading-tight text-stone-900 md:text-5xl">
                    Hubungi <span class="text-brand">Green Deahan</span><br>Kami Siap Membantu!
                </h1>
                <p class="text-base leading-relaxed text-stone-500">
                    Punya pertanyaan soal lapangan? Ingin estimasi biaya? Atau siap mulai proyek? Pilih cara yang paling nyaman untuk Anda.
                </p>
            </div>
        </section>

        <!-- Quick contact cards -->
        <section class="mx-auto mb-14 max-w-6xl px-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a
                    href="https://wa.me/6281357570064?text={{ urlencode('Halo GreenDeahan, saya ingin konsultasi lapangan') }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="social-card reveal group flex flex-col items-center rounded-2xl border-2 border-stone-200 bg-white p-5 text-center hover:border-[#25d366]"
                >
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#25d366]">
                        <x-icon name="whatsapp-logo" size="26" class="text-white" />
                    </div>
                    <div class="font-marketing-display mb-1 text-sm font-black text-stone-900 transition-colors group-hover:text-[#25d366]">WhatsApp</div>
                    <div class="mb-1 text-sm font-bold text-brand">+62 813-5757-0064</div>
                    <div class="mt-3 w-full rounded-full bg-[#25d366] px-4 py-1.5 text-center text-xs font-bold text-white transition-colors group-hover:bg-[#1fbc5a]">
                        Chat Sekarang &rarr;
                    </div>
                </a>

                <a href="tel:+6281357570064" class="social-card reveal group flex flex-col items-center rounded-2xl border-2 border-stone-200 bg-white p-5 text-center hover:border-brand">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand">
                        <x-icon name="phone-call" size="22" class="text-white" />
                    </div>
                    <div class="font-marketing-display mb-1 text-sm font-black text-stone-900 transition-colors group-hover:text-brand">Telepon</div>
                    <div class="mb-1 text-sm font-bold text-brand">+62 813-5757-0064</div>
                    <div class="mt-3 w-full rounded-full bg-brand px-4 py-1.5 text-center text-xs font-bold text-white transition-colors group-hover:bg-brand-dark">
                        Hubungi &rarr;
                    </div>
                </a>

                <a href="mailto:rumput1927@gmail.com" class="social-card reveal group flex flex-col items-center rounded-2xl border-2 border-stone-200 bg-white p-5 text-center hover:border-red-400">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-red-500">
                        <x-icon name="mail-envelope" size="22" class="text-white" />
                    </div>
                    <div class="font-marketing-display mb-1 text-sm font-black text-stone-900 transition-colors group-hover:text-red-500">Email</div>
                    <div class="mb-1 break-all text-xs font-bold text-brand">rumput1927@gmail.com</div>
                    <div class="mt-3 w-full rounded-full bg-red-500 px-4 py-1.5 text-center text-xs font-bold text-white transition-colors group-hover:bg-red-600">
                        Kirim Email &rarr;
                    </div>
                </a>

                <div class="social-card reveal flex flex-col items-center rounded-2xl border-2 border-brand bg-brand p-5 text-center">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20">
                        <x-icon name="clock" size="22" class="text-white" />
                    </div>
                    <div class="font-marketing-display mb-3 text-sm font-black text-white">Jam Operasional</div>
                    <div class="w-full space-y-1.5 text-xs">
                        <div class="flex justify-between text-brand-200"><span>Senin sampai Jumat</span><span class="font-bold text-white">06.00-23.00</span></div>
                        <div class="flex justify-between text-brand-200"><span>Sabtu</span><span class="font-bold text-white">06.00-23.00</span></div>
                        <div class="flex justify-between text-brand-200"><span>Minggu</span><span class="font-bold text-yellow-300">Konsultasi Online</span></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Form + Info -->
        <section class="mx-auto mb-16 max-w-6xl px-6">
            <div class="grid gap-8 lg:grid-cols-5">

                <!-- Contact Form -->
                <div class="reveal lg:col-span-3">
                    <div class="rounded-2xl border border-stone-200 bg-white p-8">
                        <div class="mb-6">
                            <span class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">
                                <x-icon name="checklist" size="13" />
                                Formulir Kontak
                            </span>
                            <h2 class="font-marketing-display text-2xl font-black text-stone-900">Kirim Pesan ke Kami</h2>
                        </div>

                        <!-- Success state -->
                        <div class="py-8 text-center" x-show="submitted" x-cloak>
                            <div class="mb-4 flex justify-center">
                                <x-icon name="check-circle" size="48" class="text-brand" />
                            </div>
                            <h3 class="font-marketing-display mb-2 text-xl font-black text-stone-900">Pesan Terkirim!</h3>
                            <p class="mb-6 text-sm text-stone-500">Jendela WhatsApp akan segera terbuka. Tim kami siap menghubungi Anda.</p>
                            <button type="button" x-on:click="submitted = false" class="rounded-xl bg-brand px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-dark">
                                Kirim Pesan Lagi
                            </button>
                        </div>

                        <form class="space-y-4" x-show="!submitted" x-on:submit.prevent="submit()">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600" for="f-name">Nama Lengkap *</label>
                                    <input id="f-name" x-model="name" type="text" placeholder="Masukkan nama Anda"
                                           class="w-full rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm placeholder-stone-400 transition-colors focus:border-brand focus:shadow-[0_0_0_3px_rgba(0,100,0,.1)] focus:outline-none" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600" for="f-type">Jenis Lapangan yang Diminati *</label>
                                    <select id="f-type" x-model="lapanganType"
                                            class="w-full rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm text-stone-700 transition-colors focus:border-brand focus:shadow-[0_0_0_3px_rgba(0,100,0,.1)] focus:outline-none">
                                        <option value="">-- Pilih jenis lapangan --</option>
                                        <option value="Lapangan Futsal">Lapangan Futsal</option>
                                        <option value="Mini Soccer">Mini Soccer</option>
                                        <option value="Lapangan Padel">Lapangan Padel</option>
                                        <option value="Lapangan Badminton">Lapangan Badminton</option>
                                        <option value="Lebih dari 1 jenis">Lainnya / Lebih dari 1 jenis</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600" for="f-city">Kota / Lokasi Proyek *</label>
                                    <input id="f-city" x-model="city" type="text" placeholder="Contoh: Jakarta Selatan"
                                           class="w-full rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm placeholder-stone-400 transition-colors focus:border-brand focus:shadow-[0_0_0_3px_rgba(0,100,0,.1)] focus:outline-none" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600" for="f-budget">Estimasi Budget</label>
                                    <select id="f-budget" x-model="budget"
                                            class="w-full rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm text-stone-700 transition-colors focus:border-brand focus:shadow-[0_0_0_3px_rgba(0,100,0,.1)] focus:outline-none">
                                        <option value="">-- Pilih range budget --</option>
                                        <option value="Di bawah Rp 200 juta">Di bawah Rp 200 juta</option>
                                        <option value="Rp 200-500 juta">Rp 200-500 juta</option>
                                        <option value="Rp 500 juta-1 miliar">Rp 500 juta-1 miliar</option>
                                        <option value="Di atas Rp 1 miliar">Di atas Rp 1 miliar</option>
                                        <option value="Belum tahu, minta estimasi">Belum tahu, minta estimasi</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600" for="f-msg">Pesan / Detail Kebutuhan</label>
                                <textarea id="f-msg" x-model="message" rows="4"
                                          placeholder="Ceritakan kebutuhan Anda: ukuran lahan, jumlah lapangan, fasilitas yang diinginkan, target waktu selesai, dll."
                                          class="w-full resize-none rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm placeholder-stone-400 transition-colors focus:border-brand focus:shadow-[0_0_0_3px_rgba(0,100,0,.1)] focus:outline-none"></textarea>
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-stone-600">Saya tertarik untuk (boleh pilih lebih dari satu)</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ([
                                        'konsultasi' => 'Konsultasi Gratis',
                                        'survey' => 'Survey Lokasi',
                                        'rab' => 'Estimasi RAB',
                                        'renovasi' => 'Renovasi Lapangan',
                                        'perawatan' => 'Perawatan Berkala',
                                    ] as $value => $label)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-stone-200 bg-[#f7f5f2] px-3 py-2 text-xs font-semibold text-stone-700 transition-colors hover:border-brand">
                                            <input type="checkbox" x-model="interests" value="{{ $value }}" class="accent-[#006400]" />
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <small class="block text-xs font-semibold text-danger" x-show="error" x-cloak x-text="error"></small>

                            <button
                                type="submit"
                                :disabled="loading"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand py-4 text-sm font-bold text-white shadow-lg shadow-brand-dark/20 transition-colors hover:bg-brand-dark disabled:opacity-70"
                            >
                                <span x-show="!loading"><x-icon name="send" size="16" class="text-white" /></span>
                                <span x-text="loading ? 'Mengirim...' : 'Kirim Pesan Sekarang'"></span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="reveal space-y-5 lg:col-span-2">
                    <div class="rounded-2xl border border-stone-200 bg-white p-6">
                        <h3 class="font-marketing-display mb-5 flex items-center gap-2 border-b border-stone-100 pb-3 text-base font-black text-stone-900">
                            <x-icon name="location-pin" size="18" class="text-brand" />
                            Informasi Kontak
                        </h3>
                        <div class="space-y-4">
                            <div class="flex gap-3.5">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-brand-50">
                                    <x-icon name="phone-call" size="17" class="text-brand" />
                                </div>
                                <div>
                                    <p class="mb-0.5 text-xs font-bold uppercase tracking-wide text-stone-400">Telepon / WhatsApp</p>
                                    <a href="tel:+6281357570064" class="text-sm font-bold text-stone-900 transition-colors hover:text-brand">+62 813-5757-0064</a>
                                </div>
                            </div>
                            <div class="flex gap-3.5">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-brand-50">
                                    <x-icon name="mail-envelope" size="17" class="text-brand" />
                                </div>
                                <div>
                                    <p class="mb-0.5 text-xs font-bold uppercase tracking-wide text-stone-400">Email</p>
                                    <a href="mailto:rumput1927@gmail.com" class="break-all text-sm font-bold text-stone-900 transition-colors hover:text-brand">rumput1927@gmail.com</a>
                                </div>
                            </div>
                            <div class="flex gap-3.5">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-brand-50">
                                    <x-icon name="clock" size="17" class="text-brand" />
                                </div>
                                <div>
                                    <p class="mb-0.5 text-xs font-bold uppercase tracking-wide text-stone-400">Jam Kerja</p>
                                    <p class="text-sm font-semibold text-stone-900">Senin sampai Sabtu: 06.00-23.00 WIB</p>
                                </div>
                            </div>
                            <div class="flex gap-3.5">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-brand-50">
                                    <x-icon name="location-pin" size="17" class="text-brand" />
                                </div>
                                <div>
                                    <p class="mb-0.5 text-xs font-bold uppercase tracking-wide text-stone-400">Area Layanan</p>
                                    <p class="text-sm font-semibold text-stone-900">Seluruh Indonesia</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 border-t border-stone-100 pt-4">
                            <p class="mb-3 text-xs font-bold uppercase tracking-wide text-stone-400">Ikuti Kami</p>
                            <div class="flex gap-2">
                                @foreach ([
                                    ['icon' => 'instagram-logo', 'href' => 'https://www.instagram.com/green_deahan1927', 'label' => 'Instagram', 'hover' => 'hover:bg-pink-600'],
                                    ['icon' => 'facebook-logo', 'href' => 'https://www.facebook.com/greendeahan/', 'label' => 'Facebook', 'hover' => 'hover:bg-[#1877f2]'],
                                    ['icon' => 'tiktok-logo', 'href' => 'https://www.tiktok.com/@green_deahan', 'label' => 'TikTok', 'hover' => 'hover:bg-stone-800'],
                                    ['icon' => 'x-logo', 'href' => 'https://x.com/greendeahan1927', 'label' => 'X / Twitter', 'hover' => 'hover:bg-stone-800'],
                                    ['icon' => 'youtube-logo', 'href' => 'https://www.youtube.com/@green_deahan', 'label' => 'YouTube', 'hover' => 'hover:bg-red-600'],
                                ] as $social)
                                    <a
                                        href="{{ $social['href'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="{{ $social['label'] }}"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-stone-100 text-stone-500 transition-colors {{ $social['hover'] }} hover:text-white"
                                    >
                                        <x-icon :name="$social['icon']" size="14" />
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Quick FAQ -->
                    <div class="rounded-2xl border border-stone-200 bg-white p-6">
                        <h3 class="font-marketing-display mb-4 flex items-center gap-2 border-b border-stone-100 pb-3 text-base font-black text-stone-900">
                            <x-icon name="help-circle" size="18" class="text-brand" />
                            Pertanyaan Cepat
                        </h3>
                        <div class="space-y-2">
                            @foreach ([
                                ['q' => 'Apakah konsultasi benar-benar gratis?', 'a' => 'Ya, 100% gratis tanpa syarat. Kami tidak memungut biaya apapun untuk konsultasi, survey lokasi, maupun pembuatan RAB estimasi.'],
                                ['q' => 'Berapa lama proses dari konsultasi sampai mulai?', 'a' => 'Setelah konsultasi awal, biasanya 3 sampai 7 hari untuk survey dan desain, lalu 3 sampai 5 hari untuk penawaran harga. Setelah deal, konstruksi bisa dimulai dalam 1 sampai 2 minggu.'],
                                ['q' => 'Apakah bisa minta contoh portofolio dulu?', 'a' => 'Tentu! Kunjungi halaman Galeri kami atau minta tim kami kirim foto dan video proyek yang sesuai kebutuhan Anda via WhatsApp.'],
                            ] as $i => $faq)
                                <div class="overflow-hidden rounded-xl border border-stone-100">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between px-4 py-3 text-left text-xs font-bold text-stone-800"
                                        x-on:click="openFaq = openFaq === {{ $i }} ? null : {{ $i }}"
                                    >
                                        {{ $faq['q'] }}
                                        <span class="text-base font-black leading-none text-brand" x-text="openFaq === {{ $i }} ? '−' : '+'"></span>
                                    </button>
                                    <div class="faq-answer px-4 text-xs leading-relaxed text-stone-500" :class="{ open: openFaq === {{ $i }} }">
                                        <div class="pb-3">{{ $faq['a'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <x-site-footer />

        <script>
            function kontakApp() {
                return {
                    name: '',
                    lapanganType: '',
                    city: '',
                    budget: '',
                    message: '',
                    interests: [],
                    loading: false,
                    submitted: false,
                    error: '',
                    openFaq: null,
                    submit() {
                        this.error = '';
                        if (!this.name.trim()) { this.error = 'Nama lengkap wajib diisi.'; return; }
                        if (!this.lapanganType) { this.error = 'Pilih jenis lapangan yang diminati.'; return; }
                        if (!this.city.trim()) { this.error = 'Kota / lokasi proyek wajib diisi.'; return; }

                        this.loading = true;

                        const interestLabels = {
                            konsultasi: 'Konsultasi Gratis',
                            survey: 'Survey Lokasi',
                            rab: 'Estimasi RAB',
                            renovasi: 'Renovasi Lapangan',
                            perawatan: 'Perawatan Berkala',
                        };
                        const interestList = this.interests.length
                            ? this.interests.map((v) => interestLabels[v]).join(', ')
                            : 'Belum ditentukan';

                        const waText = encodeURIComponent(
                            'Halo kak, saya ingin tanya-tanya soal pembuatan lapangan 🙏\n\n' +
                            '━━━━━━━━━━━━━━━━━━━━\n' +
                            '👤 *INFORMASI PEMESAN*\n' +
                            `Nama Saya  : ${this.name}\n` +
                            '━━━━━━━━━━━━━━━━━━━━\n' +
                            '🏟️ *DETAIL LAPANGAN*\n' +
                            `Jenis Lapangan : ${this.lapanganType}\n` +
                            `Kota / Lokasi  : ${this.city}\n` +
                            `Estimasi Budget: ${this.budget || 'Belum tahu, minta estimasi'}\n` +
                            '━━━━━━━━━━━━━━━━━━━━\n' +
                            '✅ *SAYA BUTUH BANTUAN*\n' +
                            `${interestList}\n` +
                            '━━━━━━━━━━━━━━━━━━━━\n' +
                            '💬 *KETERANGAN TAMBAHAN*\n' +
                            `${this.message || 'Tidak ada keterangan tambahan'}\n` +
                            '━━━━━━━━━━━━━━━━━━━━\n' +
                            'Mohon info lebih lanjutnya ya kak, terima kasih! 😊'
                        );

                        setTimeout(() => {
                            this.loading = false;
                            this.submitted = true;
                            setTimeout(() => {
                                window.open(`https://wa.me/6281357570064?text=${waText}`, '_blank', 'noopener,noreferrer');
                            }, 500);
                        }, 350);
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
