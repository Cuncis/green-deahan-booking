import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import Icon from '../components/Icon';
import Nav from '../components/Nav';
import Footer from '../components/Footer';
import Reveal from '../components/Reveal';
import PageStyles from '../components/PageStyles';
import Counter from '../components/Counter';
import BackToTop from '../components/BackToTop';
import SportsPlanner from '../components/SportsPlanner';

const WA_NUMBER = '6281357570064';
const WA_LINK = `https://wa.me/${WA_NUMBER}`;

const konsepList = [
    {
        nama: 'Fokus Satu Cabang',
        teks: 'Bangun satu lapangan dulu, misalnya futsal atau padel. Modal ringan, cocok untuk pemula.',
        gambar: 'gambar 1 lapangan futsal',
    },
    {
        nama: 'Multi-Court Kompleks',
        teks: '2 sampai 4 lapangan berbeda dalam satu lokasi. Menjangkau lebih banyak orang.',
        gambar: 'gambar beberapa lapangan berdampingan',
    },
    {
        nama: 'Sport Center Lifestyle',
        teks: 'Lapangan dipadukan dengan kafe atau tribun. Cocok di lokasi ramai perkotaan.',
        gambar: 'gambar lapangan dengan area kafe dan tribun',
    },
] as const;

const kebutuhanLahan = [
    { icon: 'futsal-goal', nama: 'Futsal', ukuran: '15 x 25 m' },
    { icon: 'soccer-ball', nama: 'Mini Soccer', ukuran: '25 x 40 m' },
    { icon: 'padel-racket', nama: 'Padel', ukuran: '10 x 20 m' },
    { icon: 'shuttlecock', nama: 'Badminton', ukuran: '6.1 x 13.4 m' },
] as const;

/** Placeholder box for a real photo to be supplied later. */
function ImagePlaceholder({ label, className = '' }: { label: string; className?: string }) {
    return (
        <div
            className={`flex aspect-[4/3] items-center justify-center rounded-3xl border-2 border-dashed border-stone-300 bg-stone-50 px-6 text-center text-sm font-medium text-stone-400 ${className}`}
        >
            [{label}]
        </div>
    );
}

function Check({ children }: { children: ReactNode }) {
    return (
        <li className="flex items-center gap-2 text-sm text-stone-600">
            <Icon name="check-circle" size={16} className="flex-shrink-0 text-brand" />
            {children}
        </li>
    );
}

export default function V2Konsep() {
    return (
        <>
            <Head title="Konsep Sport Center, Green Deahan Sport" />

            <PageStyles />

            <div className="bg-[#f5f5f5] text-stone-600 antialiased">
                <Nav />

                {/* HERO */}
                <section className="bg-brand-50 pb-16 pt-32 lg:pt-36">
                    <div className="mx-auto max-w-6xl px-6">
                        <div className="grid grid-cols-1 items-center gap-10 lg:grid-cols-2">
                            <Reveal>
                                <span className="inline-block rounded-full bg-[#f5f5f5] px-4 py-1.5 text-xs font-bold text-brand shadow-sm">Panduan Sebelum Membangun</span>
                                <h1 className="mt-4 text-3xl font-bold leading-tight text-stone-900 md:text-4xl">Pilih Konsep Sport Center yang Tepat</h1>
                                <p className="mt-3 text-base leading-relaxed text-stone-600">
                                    Kenali 3 pilihan konsep dan kebutuhan lahannya. Lalu coba kalkulator gratis di bawah.
                                </p>
                                <div className="mt-6 flex flex-wrap items-center gap-4">
                                    <a
                                        href="#kalkulator"
                                        className="inline-flex rounded-full bg-brand px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-dark"
                                    >
                                        Coba Kalkulator Gratis
                                    </a>
                                    <a
                                        href={WA_LINK}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="group inline-flex items-center gap-1.5 text-sm font-semibold text-stone-600"
                                    >
                                        atau{' '}
                                        <span className="text-brand underline underline-offset-2 transition-colors group-hover:text-brand-dark">chat WhatsApp</span>
                                        <Icon name="arrow-right" size={14} className="text-brand" />
                                    </a>
                                </div>
                            </Reveal>
                            <Reveal>
                                <ImagePlaceholder label="gambar lapangan futsal outdoor" />
                            </Reveal>
                        </div>

                        <Reveal className="mt-14 grid grid-cols-1 items-center gap-8 border-t border-brand-200 pt-10 md:grid-cols-2">
                            <div>
                                <h6 className="font-semibold text-stone-900">3 Konsep Pilihan</h6>
                                <ul className="mt-3 space-y-2">
                                    <Check>Fokus Satu Cabang</Check>
                                    <Check>Multi-Court Kompleks</Check>
                                    <Check>Sport Center Lifestyle</Check>
                                </ul>
                            </div>
                            <div className="grid grid-cols-2 gap-6 border-l-2 border-brand pl-6">
                                <div>
                                    <Counter target={100} suffix="+" className="text-2xl font-bold text-stone-900" />
                                    <p className="text-sm text-stone-500">Proyek selesai</p>
                                </div>
                                <div>
                                    <Counter target={15} suffix="+" className="text-2xl font-bold text-stone-900" />
                                    <p className="text-sm text-stone-500">Kota di Indonesia</p>
                                </div>
                            </div>
                        </Reveal>
                    </div>
                </section>

                {/* 3 KONSEP (alternating rows) */}
                <section className="py-16 lg:py-24">
                    <div className="mx-auto max-w-5xl space-y-16 px-6">
                        {konsepList.map((konsep, i) => (
                            <Reveal key={konsep.nama} className="grid grid-cols-1 items-center gap-10 md:grid-cols-2">
                                <div className={i % 2 === 1 ? 'md:order-2' : ''}>
                                    <ImagePlaceholder label={konsep.gambar} />
                                </div>
                                <div className={i % 2 === 1 ? 'md:order-1' : ''}>
                                    <span className="text-xs font-bold uppercase tracking-widest text-brand">Konsep {i + 1}</span>
                                    <h3 className="mt-2 text-2xl font-bold text-stone-900">{konsep.nama}</h3>
                                    <p className="mt-2 text-base leading-relaxed text-stone-600">{konsep.teks}</p>
                                </div>
                            </Reveal>
                        ))}
                    </div>
                </section>

                {/* KEBUTUHAN LAHAN */}
                <section className="bg-brand-50 py-16">
                    <div className="mx-auto max-w-5xl px-6 text-center">
                        <Reveal>
                            <h2 className="text-2xl font-bold text-stone-900 md:text-3xl">Kebutuhan Lahan</h2>
                            <p className="mt-2 text-sm text-stone-500">Ukuran umum tiap jenis lapangan.</p>
                        </Reveal>
                        <Reveal className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            {kebutuhanLahan.map((item) => (
                                <div key={item.nama} className="rounded-2xl border border-stone-200 bg-[#f5f5f5] p-5 shadow-sm">
                                    <Icon name={item.icon} size={24} className="mx-auto text-brand" />
                                    <p className="mt-2 text-sm font-bold text-stone-900">{item.nama}</p>
                                    <p className="text-xs text-stone-500">{item.ukuran}</p>
                                </div>
                            ))}
                        </Reveal>
                    </div>
                </section>

                <SportsPlanner />

                <Footer />
                <BackToTop />
            </div>
        </>
    );
}
