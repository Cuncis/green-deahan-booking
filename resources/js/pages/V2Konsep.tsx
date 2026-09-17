import { Head } from '@inertiajs/react';
import { useState } from 'react';
import Icon from '../components/Icon';
import Nav from '../components/Nav';
import Footer from '../components/Footer';
import Reveal from '../components/Reveal';
import PageStyles from '../components/PageStyles';
import Counter from '../components/Counter';
import BackToTop from '../components/BackToTop';
import ConceptArt from '../components/ConceptArt';
import IconBadge from '../components/IconBadge';
import GradientBlobs from '../components/GradientBlobs';
import SportsPlanner, { budgetOptions } from '../components/SportsPlanner';

const WA_NUMBER = '6281357570064';
const WA_LINK = `https://wa.me/${WA_NUMBER}`;

const konsepList = [
    {
        art: 'single-focus',
        cocok: 'Cocok untuk pemula, lahan terbatas',
        nama: 'Fokus Satu Cabang Olahraga',
        deskripsi: 'Bangun satu jenis lapangan saja, misalnya futsal atau padel. Modal lebih ringan, proses pembangunan lebih cepat, dan lebih mudah dikelola untuk pemilik bisnis baru.',
        poin: ['Kebutuhan lahan paling kecil', 'Modal awal lebih terjangkau', 'Cocok dites di satu lokasi dulu'],
    },
    {
        art: 'multi-court',
        cocok: 'Cocok untuk jangkau banyak segmen',
        nama: 'Multi-Court Kompleks',
        deskripsi: '2 sampai 4 lapangan dengan jenis olahraga berbeda dalam satu lokasi. Menjangkau lebih banyak komunitas sekaligus dan jam sibuk satu cabang bisa tertutup cabang lain.',
        poin: ['Pendapatan dari beberapa segmen', 'Jam operasional lebih merata', 'Butuh lahan dan modal lebih besar'],
    },
    {
        art: 'lifestyle-hub',
        cocok: 'Cocok untuk lokasi strategis perkotaan',
        nama: 'Sport Center Lifestyle',
        deskripsi: 'Lapangan dipadukan dengan area tongkrongan, kafe, atau tribun penonton. Pengunjung tidak cuma booking main, tapi juga menghabiskan waktu dan uang di tempat.',
        poin: ['Ada pendapatan tambahan dari F&B', 'Nilai sewa tempat untuk event lebih tinggi', 'Butuh perencanaan desain lebih matang'],
    },
] as const;

const kebutuhanLahan = [
    {
        icon: 'futsal-goal',
        nama: 'Futsal',
        ukuran: '15 x 25 m sampai 16 x 34 m',
        catatan: 'Ukuran standar lapangan indoor',
        poin: ['Cocok dibangun indoor beratap', 'Lantai interlock atau rumput sintetis', 'Ideal untuk komunitas booking rutin'],
    },
    {
        icon: 'soccer-ball',
        nama: 'Mini Soccer',
        ukuran: '25 x 40 m sampai 30 x 50 m',
        catatan: 'Ditambah area tribun bila perlu',
        poin: ['Umumnya outdoor, butuh drainase matang', 'Bisa ditambah tribun penonton', 'Cocok untuk turnamen skala kecil'],
    },
    {
        icon: 'padel-racket',
        nama: 'Padel',
        ukuran: '10 x 20 m per lapangan',
        catatan: 'Ukuran standar internasional',
        poin: ['Tren olahraga yang terus naik', 'Konstruksi standar internasional IFP', 'Butuh dinding kaca atau panel khusus'],
    },
    {
        icon: 'shuttlecock',
        nama: 'Badminton',
        ukuran: '6.1 x 13.4 m per lapangan',
        catatan: 'Perlu ruang sirkulasi tambahan',
        poin: ['Perlu jarak antar lapangan yang cukup', 'Lantai vinyl atau kayu non-slip', 'Cocok dibangun multi-court indoor'],
    },
] as const;

const kenapaMenjanjikan = [
    { icon: 'chart-trend', judul: 'Pendapatan Berulang', deskripsi: 'Model booking per jam atau membership membuat pendapatan datang terus tiap minggu, bukan cuma transaksi sekali jalan.' },
    { icon: 'user-group', judul: 'Komunitas yang Loyal', deskripsi: 'Olahraga seperti padel, futsal, dan badminton punya komunitas rutin yang booking jadwal tetap tiap minggu.' },
    { icon: 'wallet', judul: 'Bisa Mulai Bertahap', deskripsi: 'Anda bisa mulai dari satu lapangan dulu, lalu tambah cabang atau jenis lapangan lain setelah bisnis berjalan.' },
] as const;

/** Land-requirement tabs with a smooth opacity crossfade between panels, mirroring the Folio SaaS tab-feature pattern. */
function LandTabs() {
    const [active, setActive] = useState(0);
    const [fading, setFading] = useState(false);
    const item = kebutuhanLahan[active];

    function selectTab(i: number) {
        if (i === active) return;
        setFading(true);
        setTimeout(() => {
            setActive(i);
            setFading(false);
        }, 180);
    }

    return (
        <section className="bg-brand-50 px-6 py-16">
            <div className="mx-auto max-w-4xl text-center">
                <Reveal>
                    <span className="mb-3 inline-block text-xs font-bold uppercase tracking-widest text-brand">Perencanaan Lahan</span>
                    <h2 className="text-3xl font-bold text-stone-900 md:text-4xl">Kisaran Kebutuhan Lahan per Jenis Lapangan</h2>
                    <p className="mx-auto mt-3 max-w-xl text-sm text-stone-500">
                        Ukuran final tergantung hasil survey lokasi, tapi ini gambaran umum untuk bantu Anda memperkirakan lahan yang dibutuhkan.
                    </p>
                </Reveal>

                <Reveal className="mt-8 flex flex-wrap justify-center gap-3">
                    {kebutuhanLahan.map((tab, i) => (
                        <button
                            key={tab.nama}
                            type="button"
                            onClick={() => selectTab(i)}
                            className={`rounded-2xl px-6 py-4 text-sm font-semibold transition-all duration-300 ${
                                active === i ? 'scale-105 bg-brand text-white shadow-lg shadow-brand-dark/20' : 'bg-white text-stone-600 hover:-translate-y-0.5 hover:bg-brand-100'
                            }`}
                        >
                            <span className={`mx-auto mb-1 flex justify-center ${active === i ? 'text-white' : 'text-brand'}`}>
                                <Icon name={tab.icon} size={20} />
                            </span>
                            {tab.nama}
                        </button>
                    ))}
                </Reveal>

                <Reveal
                    className={`mt-8 rounded-2xl bg-white p-8 text-left shadow-sm transition-opacity duration-200 sm:flex sm:items-center sm:gap-10 ${
                        fading ? 'opacity-0' : 'opacity-100'
                    }`}
                >
                    <IconBadge tone="light" size={64} floaty>
                        <Icon name={item.icon} size={30} />
                    </IconBadge>
                    <div className="mt-4 sm:mt-0">
                        <h3 className="text-lg font-bold text-stone-900">{item.nama}</h3>
                        <p className="mt-1 text-xl font-bold text-brand">{item.ukuran}</p>
                        <p className="mt-1 text-sm text-stone-500">{item.catatan}</p>
                        <ul className="mt-4 space-y-1.5 text-sm text-stone-600">
                            {item.poin.map((poin) => (
                                <li key={poin} className="flex items-center gap-2">
                                    <Icon name="check-circle" size={16} className="flex-shrink-0 text-brand" />
                                    {poin}
                                </li>
                            ))}
                        </ul>
                    </div>
                </Reveal>
            </div>
        </section>
    );
}

export default function V2Konsep() {
    return (
        <>
            <Head title="Konsep Sport Center, Green Deahan Sport" />

            <PageStyles />

            <div className="bg-white text-stone-600 antialiased">
                <Nav />

                {/* HERO */}
                <section className="relative overflow-hidden bg-brand-50 pb-16 pt-32 text-center">
                    <GradientBlobs />
                    <Reveal className="relative z-10 mx-auto max-w-3xl px-6">
                        <span className="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm shadow-sm">
                            <span className="rounded-full bg-brand px-2 py-0.5 text-xs font-semibold text-white">Panduan</span>
                            Sebelum mulai konstruksi sport center Anda
                        </span>
                        <h1 className="mt-5 text-4xl font-bold leading-tight text-stone-900 md:text-5xl">
                            Konsep <span className="text-brand">Sport Center</span> yang Ideal untuk Bisnis Anda di Masa Depan
                        </h1>
                        <p className="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-stone-500 md:text-lg">
                            Sebelum mulai konstruksi, kenali dulu jenis konsep sport center, kebutuhan lahan, dan potensi bisnisnya. Supaya investasi Anda tepat sasaran
                            sejak awal.
                        </p>
                        <div className="mt-6 flex flex-wrap items-center justify-center gap-4">
                            <a
                                href="#kalkulator"
                                className="inline-flex rounded-full bg-gradient-to-r from-brand to-brand-dark px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-brand-dark/20 transition-transform duration-300 hover:-translate-y-0.5"
                            >
                                Coba Kalkulator Gratis
                            </a>
                            <a href={WA_LINK} target="_blank" rel="noopener noreferrer" className="text-sm font-semibold text-stone-600 transition-colors hover:text-brand">
                                atau konsultasi langsung via WhatsApp &rarr;
                            </a>
                        </div>
                    </Reveal>

                    <Reveal className="relative z-10 mx-auto mt-12 grid max-w-4xl grid-cols-2 gap-4 px-6 sm:grid-cols-4">
                        <div
                            className="float-anim rounded-2xl border border-white/60 bg-white/70 p-5 text-left shadow-lg shadow-brand-dark/5 backdrop-blur transition-all duration-300 hover:-translate-y-2 hover:shadow-xl"
                            style={{ animationDelay: '0s' }}
                        >
                            <ConceptArt variant="single-focus" size={40} />
                            <p className="mt-2 text-xs font-bold uppercase tracking-wide text-stone-600">Fokus Satu Cabang</p>
                        </div>
                        <div
                            className="float-anim rounded-2xl border border-white/60 bg-white/70 p-5 text-left shadow-lg shadow-brand-dark/5 backdrop-blur transition-all duration-300 hover:-translate-y-2 hover:shadow-xl"
                            style={{ animationDelay: '0.6s' }}
                        >
                            <ConceptArt variant="multi-court" size={40} />
                            <p className="mt-2 text-xs font-bold uppercase tracking-wide text-stone-600">Multi-Court Kompleks</p>
                        </div>
                        <div
                            className="float-anim rounded-2xl border border-white/60 bg-white/70 p-5 text-left shadow-lg shadow-brand-dark/5 backdrop-blur transition-all duration-300 hover:-translate-y-2 hover:shadow-xl"
                            style={{ animationDelay: '1.2s' }}
                        >
                            <ConceptArt variant="lifestyle-hub" size={40} />
                            <p className="mt-2 text-xs font-bold uppercase tracking-wide text-stone-600">Sport Center Lifestyle</p>
                        </div>
                        <div
                            className="float-anim rounded-2xl border border-white/60 bg-white/70 p-5 text-left shadow-lg shadow-brand-dark/5 backdrop-blur transition-all duration-300 hover:-translate-y-2 hover:shadow-xl"
                            style={{ animationDelay: '1.8s' }}
                        >
                            <ConceptArt variant="completed-badge" size={40} />
                            <div className="mt-2">
                                <span className="block text-xs text-stone-400">Proyek Selesai</span>
                                <Counter target={100} suffix="+" className="text-2xl font-bold text-stone-900" />
                            </div>
                        </div>
                    </Reveal>
                </section>

                {/* QUICK STATS */}
                <section className="py-10">
                    <Reveal className="mx-auto grid max-w-5xl grid-cols-1 divide-y divide-brand/15 border-y border-brand/15 px-6 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                        <div className="flex items-center justify-center gap-3 py-5">
                            <Counter target={16} suffix="+" className="text-3xl font-bold text-stone-900" />
                            <p className="text-sm text-stone-500">Tahun pengalaman membangun sport center</p>
                        </div>
                        <div className="flex items-center justify-center gap-3 py-5">
                            <Counter target={100} suffix="+" className="text-3xl font-bold text-stone-900" />
                            <p className="text-sm text-stone-500">Proyek lapangan sudah kami selesaikan</p>
                        </div>
                        <div className="flex items-center justify-center gap-3 py-5">
                            <Counter target={15} suffix="+" className="text-3xl font-bold text-stone-900" />
                            <p className="text-sm text-stone-500">Kota di Indonesia sudah kami layani</p>
                        </div>
                    </Reveal>
                </section>

                {/* PILIH KONSEP INTRO */}
                <section className="py-16">
                    <div className="mx-auto grid max-w-6xl items-center gap-12 px-6 lg:grid-cols-2">
                        <Reveal>
                            <span className="mb-3 inline-block text-xs font-bold uppercase tracking-widest text-brand">Sebelum Anda Mulai</span>
                            <h2 className="mb-4 text-3xl font-bold text-stone-900 md:text-4xl">Pilih Konsep yang Sesuai Lokasi dan Tujuan Bisnis Anda</h2>
                            <p className="mb-4 text-sm leading-relaxed text-stone-500">
                                Tidak ada konsep yang paling benar, hanya konsep yang paling sesuai dengan lahan, budget, dan target pasar Anda. Kenali dulu tiga pilihan
                                utamanya di bawah ini.
                            </p>
                            <a
                                href="#tiga-konsep"
                                className="inline-flex rounded-full bg-brand px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-dark"
                            >
                                Lihat 3 Konsep
                            </a>
                            <hr className="my-6 border-stone-200" />
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <h6 className="font-semibold text-stone-900">Sesuaikan dengan Lokasi</h6>
                                    <p className="mt-1 text-sm text-stone-500">Lahan sempit di tengah kota beda kebutuhan dengan lahan luas di pinggiran.</p>
                                </div>
                                <div>
                                    <h6 className="font-semibold text-stone-900">Sesuaikan dengan Budget</h6>
                                    <p className="mt-1 text-sm text-stone-500">Mulai dari satu lapangan dulu, atau langsung bangun kompleks lengkap.</p>
                                </div>
                            </div>
                        </Reveal>
                        <Reveal className="relative overflow-hidden rounded-3xl bg-brand-50 p-10">
                            <GradientBlobs />
                            <div className="relative z-10 grid grid-cols-3 gap-4">
                                <div className="float-anim flex aspect-square items-center justify-center rounded-2xl bg-white shadow-sm transition-transform duration-300 hover:-translate-y-1" style={{ animationDelay: '0s' }}>
                                    <ConceptArt variant="single-focus" size={36} />
                                </div>
                                <div className="float-anim flex aspect-square items-center justify-center rounded-2xl bg-white shadow-sm transition-transform duration-300 hover:-translate-y-1" style={{ animationDelay: '0.7s' }}>
                                    <ConceptArt variant="multi-court" size={36} />
                                </div>
                                <div className="float-anim flex aspect-square items-center justify-center rounded-2xl bg-white shadow-sm transition-transform duration-300 hover:-translate-y-1" style={{ animationDelay: '1.4s' }}>
                                    <ConceptArt variant="lifestyle-hub" size={36} />
                                </div>
                            </div>
                        </Reveal>
                    </div>
                </section>

                {/* 3 KONSEP (dark steps section) */}
                <section id="tiga-konsep" className="scroll-mt-24 bg-stone-900 py-16 text-white lg:py-24">
                    <div className="mx-auto max-w-6xl px-6">
                        <Reveal className="mb-10 text-center">
                            <span className="mb-3 inline-block text-xs font-bold uppercase tracking-widest text-brand-300">Pilihan Konsep</span>
                            <h2 className="text-3xl font-bold md:text-4xl">3 Konsep Sport Center yang Bisa Anda Pilih</h2>
                            <p className="mx-auto mt-3 max-w-xl text-sm text-stone-400">
                                Setiap konsep punya kebutuhan modal, lahan, dan target pasar yang berbeda. Sesuaikan dengan lokasi dan budget Anda.
                            </p>
                        </Reveal>

                        <div className="grid gap-6 md:grid-cols-3">
                            {konsepList.map((konsep) => (
                                <Reveal
                                    key={konsep.nama}
                                    className="rounded-2xl bg-white/5 p-6 transition-all duration-300 hover:-translate-y-1 hover:bg-white/10"
                                >
                                    <IconBadge tone="dark" size={52} className="mb-4">
                                        <ConceptArt variant={konsep.art} size={28} />
                                    </IconBadge>
                                    <span className="mb-2 inline-block text-xs font-bold uppercase tracking-wide text-brand-300">{konsep.cocok}</span>
                                    <h3 className="mb-2 text-lg font-bold">{konsep.nama}</h3>
                                    <p className="mb-4 text-sm leading-relaxed text-stone-400">{konsep.deskripsi}</p>
                                    <ul className="space-y-2 text-sm text-stone-300">
                                        {konsep.poin.map((poin) => (
                                            <li key={poin} className="flex items-start gap-2">
                                                <Icon name="check-circle" size={16} className="mt-0.5 flex-shrink-0 text-brand-300" />
                                                {poin}
                                            </li>
                                        ))}
                                    </ul>
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>

                {/* KEBUTUHAN LAHAN (tabs) */}
                <LandTabs />

                {/* KENAPA MENJANJIKAN */}
                <section className="py-16">
                    <div className="mx-auto max-w-4xl px-6 text-center">
                        <Reveal>
                            <span className="mb-3 inline-block text-xs font-bold uppercase tracking-widest text-brand">Potensi Bisnis</span>
                            <h2 className="text-3xl font-bold text-stone-900 md:text-4xl">Kenapa Sport Center Semakin Diminati</h2>
                            <p className="mx-auto mt-3 max-w-xl text-sm text-stone-500">
                                Bukan cuma soal olahraga. Ini beberapa alasan bisnis sport center terus dilirik para investor.
                            </p>
                        </Reveal>

                        <Reveal className="mt-10 flex flex-wrap justify-center gap-8">
                            {kenapaMenjanjikan.map((alasan) => (
                                <div key={alasan.judul} className="w-48">
                                    <IconBadge tone="light" size={64} floaty className="mx-auto rounded-full">
                                        <Icon name={alasan.icon} size={26} />
                                    </IconBadge>
                                    <h3 className="mt-3 text-sm font-bold text-stone-900">{alasan.judul}</h3>
                                    <p className="mt-1 text-xs leading-relaxed text-stone-500">{alasan.deskripsi}</p>
                                </div>
                            ))}
                        </Reveal>

                        <Reveal className="mt-10 flex flex-col items-center gap-4 rounded-2xl bg-brand-50 p-6 sm:flex-row">
                            <Icon name="whatsapp-logo" size={28} className="flex-shrink-0 text-brand" />
                            <p className="text-sm text-stone-900">Konsultasi gratis dengan tim kami untuk RAB dan estimasi yang sesuai kondisi Anda.</p>
                            <a href={WA_LINK} target="_blank" rel="noopener noreferrer" className="whitespace-nowrap font-semibold text-brand underline sm:ml-auto">
                                Chat sekarang
                            </a>
                        </Reveal>
                    </div>
                </section>

                {/* ESTIMASI INVESTASI (bridge to calculator) */}
                <section className="bg-brand-50 py-16 lg:py-24">
                    <div className="mx-auto max-w-6xl px-6 lg:px-8">
                        <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                            <Reveal className="lg:col-span-2">
                                <span className="mb-3 inline-block text-xs font-bold uppercase tracking-widest text-brand">Estimasi Investasi</span>
                                <h2 className="mb-2 text-3xl font-bold text-stone-900 md:text-4xl">Kisaran Budget Sesuai Skala Konsep Anda</h2>
                                <p className="mb-6 text-sm text-stone-500">
                                    Bukan patokan pasti, tiap lokasi punya kondisi berbeda. Coba kalkulator di bawah untuk estimasi sesuai lahan Anda.
                                </p>
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    {budgetOptions.map((budget) => (
                                        <div key={budget.key} className="rounded-xl bg-white px-5 py-4 text-sm font-semibold text-stone-700 shadow-sm">
                                            {budget.label}
                                        </div>
                                    ))}
                                    <div className="rounded-xl bg-white px-5 py-4 text-sm font-semibold text-stone-700 shadow-sm">Lainnya</div>
                                </div>
                            </Reveal>
                            <Reveal className="rounded-2xl bg-stone-900 p-6 text-white">
                                <h5 className="font-bold">Yang Dihitung Kalkulator</h5>
                                <ul className="mt-4 space-y-2 text-sm">
                                    <li className="flex items-center gap-2">
                                        <Icon name="check-circle" size={16} className="flex-shrink-0 text-brand-300" />
                                        Estimasi kebutuhan lahan
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Icon name="check-circle" size={16} className="flex-shrink-0 text-brand-300" />
                                        Kisaran biaya konstruksi
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Icon name="check-circle" size={16} className="flex-shrink-0 text-brand-300" />
                                        Tata letak konseptual
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Icon name="check-circle" size={16} className="flex-shrink-0 text-brand-300" />
                                        Skor kecocokan lahan &amp; budget
                                    </li>
                                </ul>
                                <a
                                    href="#kalkulator"
                                    className="mt-6 block rounded-full bg-brand py-3 text-center text-sm font-bold transition-colors hover:bg-brand-dark"
                                >
                                    Coba Sekarang
                                </a>
                            </Reveal>
                        </div>
                    </div>
                </section>

                <SportsPlanner />

                <Footer />
                <BackToTop />
            </div>
        </>
    );
}
