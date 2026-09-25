import { Head } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Icon from '../components/Icon';
import Nav from '../components/Nav';
import Footer from '../components/Footer';
import Reveal from '../components/Reveal';
import PageStyles from '../components/PageStyles';
import Counter from '../components/Counter';

const WA_NUMBER = '6281357570064';
const WA_LINK = `https://wa.me/${WA_NUMBER}`;

function waLink(topik: string) {
    return `${WA_LINK}?text=${encodeURIComponent(`Halo, saya ingin konsultasi ${topik}`)}`;
}

/** Classic typewriter: types a word letter by letter, pauses, deletes it, then types the next. */
function TypingWord({
    words,
    typingSpeedMs = 90,
    deletingSpeedMs = 45,
    pauseMs = 1400,
}: {
    words: string[];
    typingSpeedMs?: number;
    deletingSpeedMs?: number;
    pauseMs?: number;
}) {
    const [wordIndex, setWordIndex] = useState(0);
    const [charCount, setCharCount] = useState(0);
    const [phase, setPhase] = useState<'typing' | 'deleting'>('typing');

    useEffect(() => {
        const currentWord = words[wordIndex];
        let timeout: ReturnType<typeof setTimeout>;

        if (phase === 'typing') {
            if (charCount < currentWord.length) {
                timeout = setTimeout(() => setCharCount((c) => c + 1), typingSpeedMs);
            } else {
                timeout = setTimeout(() => setPhase('deleting'), pauseMs);
            }
        } else {
            if (charCount > 0) {
                timeout = setTimeout(() => setCharCount((c) => c - 1), deletingSpeedMs);
            } else {
                timeout = setTimeout(() => {
                    setWordIndex((i) => (i + 1) % words.length);
                    setPhase('typing');
                }, 200);
            }
        }

        return () => clearTimeout(timeout);
    }, [charCount, phase, wordIndex, words, typingSpeedMs, deletingSpeedMs, pauseMs]);

    return (
        <span className="inline-block border-b-2 border-brand pb-1 text-brand">
            {words[wordIndex].slice(0, charCount)}
            <span className="ml-0.5 inline-block w-[3px] animate-pulse bg-brand align-middle" style={{ height: '0.85em' }} />
        </span>
    );
}

/** FAQ row that expands/collapses to its real content height, not a guessed max-height. */
function FaqItem({ q, a, isOpen, onToggle }: { q: string; a: string; isOpen: boolean; onToggle: () => void }) {
    const innerRef = useRef<HTMLDivElement>(null);
    const [maxHeight, setMaxHeight] = useState(0);

    useEffect(() => {
        const recalc = () => {
            if (innerRef.current) setMaxHeight(isOpen ? innerRef.current.scrollHeight : 0);
        };
        recalc();
        if (!isOpen) return;
        // Re-measure if the viewport resizes while open (text can reflow to more/fewer lines).
        window.addEventListener('resize', recalc);
        return () => window.removeEventListener('resize', recalc);
    }, [isOpen]);

    return (
        <div className="overflow-hidden rounded-xl bg-[#f5f5f5]">
            <button
                type="button"
                className="flex w-full items-center justify-between p-5 text-left text-sm font-semibold text-stone-900"
                onClick={onToggle}
                aria-expanded={isOpen}
            >
                {q}
                <Icon
                    name="chevron-down"
                    size={16}
                    className={`ml-3 flex-shrink-0 text-brand transition-transform duration-300 ease-in-out ${isOpen ? 'rotate-180' : ''}`}
                />
            </button>
            <div
                style={{ maxHeight }}
                className="overflow-hidden px-5 text-sm leading-relaxed text-stone-600 transition-[max-height] duration-300 ease-in-out"
            >
                <div ref={innerRef} className="pb-5" dangerouslySetInnerHTML={{ __html: a }} />
            </div>
        </div>
    );
}

const sportWords = ['Futsal', 'Mini Soccer', 'Padel', 'Badminton'];

const layanan = [
    { icon: 'futsal-goal', color: 'bg-brand', nama: 'Lapangan Futsal', deskripsi: 'Lantai interlock atau rumput sintetis, standar internasional.', topik: 'pembuatan lapangan futsal' },
    { icon: 'soccer-ball', color: 'bg-blue-500', nama: 'Mini Soccer', deskripsi: 'Rumput sintetis premium dengan sistem drainase profesional.', topik: 'pembuatan lapangan mini soccer' },
    { icon: 'padel-racket', color: 'bg-orange-500', nama: 'Lapangan Padel', deskripsi: 'Konstruksi standar internasional IFF, tren terbaru 2025.', topik: 'pembuatan lapangan padel' },
    { icon: 'shuttlecock', color: 'bg-purple-500', nama: 'Lapangan Badminton', deskripsi: 'Lantai interlock atau kayu keras, non-slip dan ramah lutut.', topik: 'pembuatan lapangan badminton' },
] as const;

const proses = [
    { no: '01', judul: 'Konsultasi Gratis', deskripsi: 'Ceritakan kebutuhan, lokasi, dan budget. Respon dalam 1 jam.' },
    { no: '02', judul: 'Survey & Desain', deskripsi: 'Tim datang ke lokasi, ukur lahan, buat desain sesuai kebutuhan.' },
    { no: '03', judul: 'Penawaran Harga', deskripsi: 'RAB & penawaran detail, transparan tanpa biaya tersembunyi.' },
    { no: '04', judul: 'Konstruksi', deskripsi: 'Pengerjaan tim ahli, tepat waktu, progress bisa dipantau.' },
];

const keunggulan = [
    { icon: 'build-hammer', judul: 'Dari Nol Sampai Jadi', deskripsi: 'Survey, desain, perizinan, konstruksi, finishing, semua kami urus.' },
    { icon: 'shield', judul: 'Garansi Resmi', deskripsi: 'Setiap proyek dilindungi garansi konstruksi & material resmi.' },
    { icon: 'wallet', judul: 'Harga Transparan', deskripsi: 'RAB detail dikirim sebelum mulai, sesuai budget Anda.' },
    { icon: 'zap', judul: 'Pengerjaan Tepat Waktu', deskripsi: 'Jadwal tertulis di kontrak, keterlambatan tanggung jawab kami.' },
    { icon: 'location-pin', judul: 'Seluruh Indonesia', deskripsi: 'Jawa, Sumatera, Kalimantan, Sulawesi, dan wilayah lainnya.' },
    { icon: 'star', judul: 'Rating 5.0', deskripsi: 'Dipercaya dari 100+ ulasan klien di seluruh Indonesia.' },
] as const;

const testimoni = [
    { foto: 'https://cdn.libradigital.id/site-assets/foto1.webp', nama: 'Budi Santoso', peran: 'Owner Futsal, Jakarta Selatan', ulasan: 'GreenDeahan handle semuanya dengan profesional. Lapangan futsal saya sudah balik modal dalam 8 bulan.' },
    { foto: 'https://cdn.libradigital.id/site-assets/foto2.jpg', nama: 'Ahmad Wijaya', peran: 'Pengusaha, Surabaya', ulasan: 'Tim yang sangat responsif, bantu pilih material sesuai budget. Lapangan mini soccer saya jadi yang paling ramai.' },
    { foto: 'https://cdn.libradigital.id/site-assets/foto3.jpg', nama: 'Ricky Pratama', peran: 'Investor, Medan', ulasan: 'Proyek lapangan padel pertama di kota kami, standar internasional. Garansi dan after sales-nya top!' },
];

/** Tracks whether the viewport is below Tailwind's `sm` breakpoint (640px). */
function useIsMobile(breakpoint = 640) {
    const [isMobile, setIsMobile] = useState(() => (typeof window !== 'undefined' ? window.innerWidth < breakpoint : false));

    useEffect(() => {
        const mql = window.matchMedia(`(max-width: ${breakpoint - 1}px)`);
        const update = () => setIsMobile(mql.matches);
        update();
        mql.addEventListener('change', update);
        return () => mql.removeEventListener('change', update);
    }, [breakpoint]);

    return isMobile;
}

const faqs = [
    { q: 'Apakah GreenDeahan mengerjakan proyek dari nol?', a: 'Ya, kami mengerjakan proyek dari nol sampai selesai. Mulai dari konsultasi awal, survey lokasi, desain, perizinan, pengadaan material, konstruksi, hingga lapangan siap beroperasi.' },
    { q: 'Lantai apa saja yang tersedia dan apa bedanya?', a: 'Kami menyediakan <strong>Lantai Interlock</strong> (awet, mudah diperbaiki, cocok indoor) dan <strong>Rumput Sintetis</strong> (estetik, tahan cuaca, cocok outdoor). Khusus padel tersedia <strong>Artificial Grass Padel</strong> standar internasional.' },
    { q: 'Berapa lama waktu pengerjaan konstruksi lapangan?', a: 'Futsal sekitar 14 sampai 21 hari, Mini Soccer 21 sampai 30 hari, Padel 14 sampai 21 hari, Badminton multi-court 21 sampai 35 hari. Jadwal pasti ditentukan saat survey.' },
    { q: 'Apakah ada garansi setelah lapangan selesai?', a: 'Ya, semua proyek dilengkapi garansi resmi untuk konstruksi dan material. Jika ada kerusakan akibat pengerjaan, tim kami memperbaiki tanpa biaya tambahan.' },
    { q: 'Bagaimana sistem pembayarannya?', a: 'Bayar uang muka (DP) setelah sepakat di kontrak, lalu progress payment sesuai milestone. <strong>Pelunasan dilakukan SETELAH lapangan selesai 100%.</strong> Tidak ada biaya tersembunyi.' },
    { q: 'Apakah melayani daerah luar Jawa?', a: 'Kami melayani seluruh Indonesia: Jabodetabek, Jawa Tengah & Timur, Sumatera, Kalimantan, Sulawesi, Bali, NTB, dan wilayah lainnya.' },
];

export default function V2Home() {
    const [openFaq, setOpenFaq] = useState<number | null>(null);
    const isMobile = useIsMobile();

    return (
        <>
            <Head title="Green Deahan Sport, Jasa Pembuatan Lapangan Futsal, Mini Soccer, Padel dan Badminton Se-Indonesia" />

            <PageStyles />
            <style>{`
                .ticker-wrap { overflow: hidden; }
                .ticker-inner { display: flex; gap: 3.5rem; animation: ticker 28s linear infinite; white-space: nowrap; }
                @keyframes ticker { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
            `}</style>

            <div className="bg-[#f5f5f5] text-stone-600 antialiased">
                <Nav />

                {/* HERO */}
                <section className="mx-auto max-w-6xl px-6 pb-16 pt-32">
                    <div className="mx-auto max-w-3xl text-center">
                        <span className="mb-4 inline-flex items-center gap-2 rounded-full bg-brand-100 px-4 py-1.5 text-xs font-bold tracking-wide text-brand">
                            <Icon name="star" size={14} />
                            Spesialis Lapangan Olahraga Sejak 2010
                        </span>
                        <h1 className="mb-5 text-4xl font-bold leading-tight text-stone-900 md:text-5xl">
                            Bangun Lapangan{' '}
                            <TypingWord
                                words={sportWords}
                                typingSpeedMs={isMobile ? 140 : 90}
                                deletingSpeedMs={isMobile ? 70 : 45}
                                pauseMs={isMobile ? 1800 : 1400}
                            />
                            <br className="hidden sm:block" />
                            <span className="sm:hidden"> </span>
                            dari Nol sampai Siap Beroperasi
                        </h1>
                        <p className="mx-auto mb-8 max-w-xl text-base leading-relaxed text-stone-500 md:text-lg">
                            Satu mitra, semua dikerjakan. Survey, desain, konstruksi, hingga serah terima. Anda tinggal pantau hasilnya.
                        </p>
                        <div className="flex flex-wrap items-center justify-center gap-4">
                            <a
                                href={WA_LINK}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="rounded-full bg-brand px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-brand-dark/20 transition-colors hover:bg-brand-dark"
                            >Konsultasi Gratis Sekarang</a>
                            <a
                                href="/galeri"
                                className="inline-flex items-center gap-2 rounded-full border border-stone-200 px-7 py-3.5 text-sm font-semibold text-stone-900 transition-colors hover:border-brand hover:text-brand"
                            >
                                Lihat Portofolio
                                <Icon name="arrow-right" size={16} />
                            </a>
                        </div>
                    </div>

                    <Reveal className="relative mt-12">
                        <img
                            src="https://cdn.libradigital.id/site-assets/hero-banner1.jpg"
                            alt="Lapangan Olahraga Green Deahan Sport"
                            className="aspect-[16/7] w-full rounded-3xl border border-stone-100 object-cover shadow-xl"
                        />
                        <div className="absolute -bottom-6 left-6 hidden items-center gap-3 rounded-2xl bg-[#f5f5f5] px-5 py-4 shadow-xl sm:flex">
                            <div className="flex items-center gap-0.5 text-yellow-400">
                                {Array.from({ length: 5 }).map((_, i) => <Icon key={i} name="star" size={14} />)}
                            </div>
                            <div className="border-l border-stone-200 pl-3">
                                <p className="text-sm font-bold text-stone-900">5.0 dari 100+ ulasan</p>
                                <p className="text-xs text-stone-400">Klien di seluruh Indonesia</p>
                            </div>
                        </div>
                    </Reveal>
                </section>

                {/* TICKER */}
                <div className="ticker-wrap border-y border-brand-dark bg-brand py-3">
                    <div className="ticker-inner">
                        {[0, 1].map((row) => (
                            ['FUTSAL', 'MINI SOCCER', 'PADEL', 'BADMINTON', 'GARANSI RESMI', 'SE-INDONESIA', 'KONSULTASI GRATIS', 'SEJAK 2010'].map((kata) => (
                                <span key={`${row}-${kata}`} className="flex items-center gap-14 text-sm font-bold tracking-widest text-white">
                                    {kata}
                                    <span className="text-brand-300">&bull;</span>
                                </span>
                            ))
                        ))}
                    </div>
                </div>

                {/* LAYANAN */}
                <section id="layanan" className="mx-auto max-w-6xl px-6 py-20">
                    <Reveal className="mb-14 text-center">
                        <span className="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Layanan Kami</span>
                        <h2 className="mb-3 text-3xl font-bold text-stone-900 md:text-4xl">4 Jenis Lapangan yang Kami Bangun</h2>
                        <p className="mx-auto max-w-xl text-sm text-stone-500 md:text-base">Semua dikerjakan oleh tim profesional berpengalaman dengan material berkualitas dan garansi resmi.</p>
                    </Reveal>

                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {layanan.map((item) => (
                            <Reveal key={item.nama} className="rounded-2xl bg-[#f5f5f5] p-6 text-center transition-colors hover:bg-brand-50">
                                <div className={`mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full ${item.color} text-white`}>
                                    <Icon name={item.icon} size={26} />
                                </div>
                                <h6 className="font-semibold text-stone-900">{item.nama}</h6>
                                <p className="mt-1 text-sm text-stone-500">{item.deskripsi}</p>
                                <a href={waLink(item.topik)} target="_blank" rel="noopener noreferrer" className="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand">
                                    Tanya harga <Icon name="arrow-right" size={14} />
                                </a>
                            </Reveal>
                        ))}
                    </div>
                </section>

                {/* TENTANG */}
                <section className="bg-[#f5f5f5] py-20">
                    <div className="mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2">
                        <Reveal>
                            <img
                                src="https://cdn.libradigital.id/site-assets/profile-img1.jpg"
                                alt="Kantor dan Workshop Green Deahan Sport"
                                className="aspect-[4/3] w-full rounded-3xl border border-stone-200 object-cover"
                            />
                        </Reveal>
                        <Reveal>
                            <span className="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Green Deahan Sport</span>
                            <h2 className="mb-4 text-3xl font-bold text-stone-900 md:text-4xl">Spesialis Lapangan Olahraga Sejak 2010</h2>
                            <p className="mb-3 text-sm leading-relaxed text-stone-600">
                                Melayani seluruh Indonesia sejak 2010. Dengan pengalaman lebih dari <strong>16 tahun</strong>, kami spesialis konstruksi lapangan futsal, mini soccer, padel, dan badminton.
                            </p>
                            <p className="mb-6 text-sm leading-relaxed text-stone-600">
                                Layanan <strong>turnkey</strong> dari konsultasi awal, desain, perizinan, konstruksi, hingga lapangan siap beroperasi. Anda cukup duduk dan pantau progress, kami yang kerjakan semuanya.
                            </p>
                            <ul className="mb-6 space-y-2 text-sm font-semibold text-stone-900">
                                <li className="flex items-center gap-2"><Icon name="check-circle" size={16} className="flex-shrink-0 text-brand" />Survey, desain, dan perizinan diurus lengkap</li>
                                <li className="flex items-center gap-2"><Icon name="check-circle" size={16} className="flex-shrink-0 text-brand" />Garansi konstruksi &amp; material resmi</li>
                                <li className="flex items-center gap-2"><Icon name="check-circle" size={16} className="flex-shrink-0 text-brand" />Pelunasan setelah lapangan 100% selesai</li>
                            </ul>
                            <a href={WA_LINK} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-3 text-sm font-semibold text-white hover:bg-brand-dark">
                                Konsultasi sekarang <Icon name="arrow-right" size={16} />
                            </a>
                        </Reveal>
                    </div>
                </section>

                {/* PROSES */}
                <section className="mx-auto max-w-6xl px-6 py-20">
                    <Reveal className="mb-14 text-center">
                        <span className="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Cara Kerja</span>
                        <h2 className="mb-3 text-3xl font-bold text-stone-900 md:text-4xl">Proses Mudah, Anda Cukup Duduk Santai</h2>
                        <p className="mx-auto max-w-xl text-sm text-stone-500">Kami handle semua proses dari awal sampai lapangan siap beroperasi.</p>
                    </Reveal>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        {proses.map((langkah) => (
                            <Reveal key={langkah.no} className="rounded-2xl bg-[#f5f5f5] p-6 text-center transition-colors hover:bg-brand-50">
                                <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand text-sm font-bold text-white">{langkah.no}</div>
                                <h6 className="mb-1 font-semibold text-stone-900">{langkah.judul}</h6>
                                <p className="text-xs leading-relaxed text-stone-500">{langkah.deskripsi}</p>
                            </Reveal>
                        ))}
                        <Reveal className="rounded-2xl bg-brand p-6 text-center text-white">
                            <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-white/20 text-sm font-bold">05</div>
                            <h6 className="mb-1 font-semibold">Lapangan Siap!</h6>
                            <p className="text-xs leading-relaxed text-brand-200">Serah terima, pelunasan setelah 100% selesai, garansi aktif.</p>
                        </Reveal>
                    </div>
                </section>

                {/* STATISTIK */}
                <section className="bg-brand py-16">
                    <div className="mx-auto max-w-5xl px-6">
                        <div className="grid grid-cols-2 gap-8 text-center text-white md:grid-cols-4">
                            <Reveal>
                                <Counter target={16} suffix="+" className="mb-1 text-5xl font-bold" />
                                <p className="text-xs font-bold uppercase tracking-widest text-brand-300">Tahun Pengalaman</p>
                            </Reveal>
                            <Reveal>
                                <Counter target={100} suffix="+" className="mb-1 text-5xl font-bold" />
                                <p className="text-xs font-bold uppercase tracking-widest text-brand-300">Proyek Selesai</p>
                            </Reveal>
                            <Reveal>
                                <Counter target={15} suffix="+" className="mb-1 text-5xl font-bold" />
                                <p className="text-xs font-bold uppercase tracking-widest text-brand-300">Kota di Indonesia</p>
                            </Reveal>
                            <Reveal>
                                <div className="mb-1 text-5xl font-bold">24/7</div>
                                <p className="text-xs font-bold uppercase tracking-widest text-brand-300">Support &amp; Konsultasi</p>
                            </Reveal>
                        </div>
                    </div>
                </section>

                {/* KEUNGGULAN */}
                <section className="mx-auto max-w-6xl px-6 py-20">
                    <Reveal className="mb-14 text-center">
                        <span className="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Kenapa Pilih Kami?</span>
                        <h2 className="mb-3 text-3xl font-bold text-stone-900 md:text-4xl">Dipercaya Ratusan Pemilik Bisnis Lapangan</h2>
                    </Reveal>
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {keunggulan.map((item) => (
                            <Reveal key={item.judul} className="rounded-2xl bg-[#f5f5f5] p-6 text-center transition-colors hover:bg-brand-50">
                                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-100 text-brand">
                                    <Icon name={item.icon} size={24} />
                                </div>
                                <h6 className="font-semibold text-stone-900">{item.judul}</h6>
                                <p className="mt-1 text-sm text-stone-500">{item.deskripsi}</p>
                            </Reveal>
                        ))}
                    </div>
                </section>

                {/* TESTIMONI */}
                <section className="bg-[#f5f5f5] py-20">
                    <div className="mx-auto max-w-6xl px-6">
                        <Reveal className="mb-14 text-center">
                            <span className="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">Testimoni Klien</span>
                            <h2 className="mb-2 text-3xl font-bold text-stone-900 md:text-4xl">Apa Kata Klien Kami?</h2>
                            <div className="mt-3 flex items-center justify-center gap-1">
                                <span className="flex items-center gap-0.5 text-yellow-400">
                                    {Array.from({ length: 5 }).map((_, i) => <Icon key={i} name="star" size={16} />)}
                                </span>
                                <span className="ml-2 font-bold text-stone-800">5.0</span>
                                <span className="ml-1 text-sm text-stone-400">dari 100+ ulasan</span>
                            </div>
                        </Reveal>

                        <div className="grid gap-6 md:grid-cols-3">
                            {testimoni.map((item) => (
                                <Reveal key={item.nama} className="rounded-2xl bg-[#f5f5f5] p-6 shadow-sm">
                                    <div className="mb-3 flex items-center gap-0.5 text-yellow-400">
                                        {Array.from({ length: 5 }).map((_, i) => <Icon key={i} name="star" size={14} />)}
                                    </div>
                                    <p className="mb-5 text-sm italic leading-relaxed text-stone-700">&ldquo;{item.ulasan}&rdquo;</p>
                                    <div className="flex items-center gap-3 border-t border-stone-100 pt-4">
                                        <img src={item.foto} alt={item.nama} className="h-11 w-11 flex-shrink-0 rounded-full object-cover" />
                                        <div>
                                            <p className="text-sm font-bold text-stone-900">{item.nama}</p>
                                            <p className="text-xs text-stone-400">{item.peran}</p>
                                        </div>
                                    </div>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>

                {/* FAQ */}
                <section className="mx-auto max-w-3xl px-6 py-20">
                    <Reveal className="mb-12 text-center">
                        <span className="mb-4 inline-block rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">FAQ</span>
                        <h2 className="mb-2 text-3xl font-bold text-stone-900 md:text-4xl">Pertanyaan yang Sering Ditanyakan</h2>
                        <p className="text-sm text-stone-500">
                            Tidak menemukan jawaban yang Anda cari?{' '}
                            <a href={WA_LINK} className="font-semibold text-brand underline">Tanya langsung via WhatsApp.</a>
                        </p>
                    </Reveal>

                    <Reveal className="space-y-3">
                        {faqs.map((faq, i) => (
                            <FaqItem
                                key={faq.q}
                                q={faq.q}
                                a={faq.a}
                                isOpen={openFaq === i}
                                onToggle={() => setOpenFaq(openFaq === i ? null : i)}
                            />
                        ))}
                    </Reveal>
                </section>

                {/* CTA FINAL */}
                <section className="bg-gradient-to-br from-brand to-brand-dark px-6 py-20">
                    <div className="mx-auto max-w-3xl text-center">
                        <span className="mb-5 inline-block rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold tracking-wide text-white">Siap Mulai Proyek Anda?</span>
                        <h2 className="mb-4 text-3xl font-bold leading-tight text-white md:text-5xl">Wujudkan Lapangan Impian Anda Sekarang!</h2>
                        <p className="mx-auto mb-3 max-w-xl text-base leading-relaxed text-brand-100">
                            Konsultasi <strong className="text-white">GRATIS</strong>, survey lokasi, desain profesional, konstruksi berkualitas. Garansi resmi &amp; pelunasan setelah lapangan 100% selesai.
                        </p>
                        <div className="mt-6 flex flex-col justify-center gap-4 sm:flex-row">
                            <a
                                href={`${WA_LINK}?text=${encodeURIComponent('Halo GreenDeahan, saya ingin konsultasi pembuatan lapangan')}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex items-center justify-center gap-2 rounded-full bg-[#f5f5f5] px-8 py-4 text-sm font-bold text-brand shadow-xl transition-colors hover:bg-brand-50"
                            >
                                <Icon name="whatsapp-logo" size={16} className="text-brand" />
                                Chat WhatsApp Sekarang
                            </a>
                            <a
                                href={`tel:+${WA_NUMBER}`}
                                className="flex items-center justify-center gap-2 rounded-full border border-white/30 px-8 py-4 text-sm font-semibold text-white transition-colors hover:bg-white/10"
                            >+62 813-5757-0064</a>
                        </div>
                        <p className="mt-5 text-xs text-brand-200">*Konsultasi 100% gratis, tidak ada kewajiban apapun</p>
                    </div>
                </section>

                <Footer />
            </div>
        </>
    );
}
