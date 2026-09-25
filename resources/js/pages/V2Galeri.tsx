import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Icon from '../components/Icon';
import Nav from '../components/Nav';
import Footer from '../components/Footer';
import Reveal from '../components/Reveal';
import PageStyles from '../components/PageStyles';
import Counter from '../components/Counter';
import BackToTop from '../components/BackToTop';
import GradientBlobs from '../components/GradientBlobs';

const WA_NUMBER = '6281357570064';
const WA_LINK = `https://wa.me/${WA_NUMBER}`;

interface KategoriTab {
    label: string;
    icon: string;
    badgeClass: string;
}

interface GaleriItemProps {
    id: number;
    cat: string;
    tall: boolean;
    title: string;
    kota: string;
    material: string;
    desc: string;
    src: string;
    badgeLabel: string;
    badgeClass: string;
}

interface V2GaleriProps {
    kategoriTab: Record<string, KategoriTab>;
    items: GaleriItemProps[];
}

/** Fullscreen photo lightbox with keyboard nav, mirroring the Blade galeri page's Alpine lightbox. */
function Lightbox({ item, onClose, onNav }: { item: GaleriItemProps; onClose: () => void; onNav: (dir: 1 | -1) => void }) {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const timer = setTimeout(() => setVisible(true), 10);
        document.body.style.overflow = 'hidden';
        return () => {
            clearTimeout(timer);
            document.body.style.overflow = '';
        };
    }, []);

    useEffect(() => {
        function onKeyDown(e: KeyboardEvent) {
            if (e.key === 'Escape') onClose();
            if (e.key === 'ArrowLeft') onNav(-1);
            if (e.key === 'ArrowRight') onNav(1);
        }
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [onClose, onNav]);

    return (
        <div
            className={`fixed inset-0 z-[1000] flex items-center justify-center bg-black/90 p-4 transition-opacity duration-200 ${visible ? 'opacity-100' : 'opacity-0'}`}
            onClick={onClose}
        >
            <div
                className={`relative w-full max-w-3xl overflow-hidden rounded-2xl bg-[#f5f5f5] transition-all duration-200 ${
                    visible ? 'scale-100 opacity-100' : 'scale-95 opacity-0'
                }`}
                onClick={(e) => e.stopPropagation()}
            >
                <div className="relative aspect-[35/22] w-full overflow-hidden">
                    <img src={item.src} alt={item.title} className="h-full w-full object-cover" />
                    <a
                        href={item.src}
                        target="_blank"
                        rel="noopener noreferrer"
                        title="Buka gambar penuh di tab baru"
                        className="absolute bottom-2 right-2 z-10 flex h-8 w-8 items-center justify-center rounded-md bg-black/40 text-white backdrop-blur transition-colors hover:bg-brand/80"
                    >
                        <Icon name="expand" size={16} />
                    </a>
                </div>
                <div className="bg-[#f5f5f5] p-5">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <span className={`mb-2 inline-block rounded-full border px-2.5 py-1 text-xs font-bold ${item.badgeClass}`}>{item.badgeLabel}</span>
                            <h3 className="text-lg font-bold text-stone-900">{item.title}</h3>
                            <p className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-stone-500">
                                <span>{item.desc}</span>
                                <span className="inline-flex items-center gap-1">
                                    <Icon name="location-pin" size={13} className="text-brand" />
                                    {item.kota}
                                </span>
                                <span className="inline-flex items-center gap-1">
                                    <Icon name="layers" size={13} className="text-brand" />
                                    {item.material}
                                </span>
                            </p>
                        </div>
                        <a
                            href={WA_LINK}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex-shrink-0 whitespace-nowrap rounded-lg bg-brand px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-brand-dark"
                        >
                            Konsultasi &rarr;
                        </a>
                    </div>
                </div>

                <button
                    type="button"
                    onClick={onClose}
                    className="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-stone-700 hover:bg-[#f5f5f5]"
                >
                    <Icon name="x-close" size={16} />
                </button>
                <button
                    type="button"
                    onClick={(e) => {
                        e.stopPropagation();
                        onNav(-1);
                    }}
                    className="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 hover:bg-[#f5f5f5]"
                >
                    <Icon name="chevron-left" size={18} />
                </button>
                <button
                    type="button"
                    onClick={(e) => {
                        e.stopPropagation();
                        onNav(1);
                    }}
                    className="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-stone-700 hover:bg-[#f5f5f5]"
                >
                    <Icon name="chevron-right" size={18} />
                </button>
            </div>
        </div>
    );
}

export default function V2Galeri({ kategoriTab, items }: V2GaleriProps) {
    const [activeFilter, setActiveFilter] = useState('semua');
    const [fading, setFading] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

    const filteredItems = activeFilter === 'semua' ? items : items.filter((item) => item.cat === activeFilter);
    const activeItem = lightboxIndex !== null ? (filteredItems[lightboxIndex] ?? null) : null;

    function selectFilter(key: string) {
        if (key === activeFilter) return;
        setFading(true);
        setTimeout(() => {
            setActiveFilter(key);
            setLightboxIndex(null);
            setFading(false);
        }, 180);
    }

    function openLightbox(id: number) {
        const idx = filteredItems.findIndex((item) => item.id === id);
        setLightboxIndex(idx === -1 ? 0 : idx);
    }

    function navLightbox(dir: 1 | -1) {
        setLightboxIndex((current) => {
            if (current === null || filteredItems.length === 0) return current;
            return (current + dir + filteredItems.length) % filteredItems.length;
        });
    }

    return (
        <>
            <Head title="Galeri Proyek, Green Deahan Sport" />

            <PageStyles />

            <div className="bg-[#f5f5f5] text-stone-600 antialiased">
                <Nav />

                {/* HERO */}
                <section className="relative overflow-hidden bg-brand-50 pb-16 pt-32 text-center">
                    <GradientBlobs />
                    <Reveal className="relative z-10 mx-auto max-w-2xl px-6">
                        <span className="mb-4 inline-block rounded-full bg-[#f5f5f5] px-4 py-1.5 text-xs font-bold tracking-wide text-brand shadow-sm">
                            Portofolio Nyata
                        </span>
                        <h1 className="text-4xl font-bold leading-tight text-stone-900 md:text-5xl">
                            Galeri Proyek <span className="text-brand">Green Deahan Sport</span>
                        </h1>
                        <p className="mx-auto mt-4 max-w-xl text-base leading-relaxed text-stone-500 md:text-lg">
                            Lihat hasil nyata konstruksi lapangan kami di berbagai kota Indonesia. Klik foto untuk melihat detail proyek.
                        </p>
                    </Reveal>

                    <Reveal className="relative z-10 mx-auto mt-10 flex max-w-md items-center justify-center gap-6 rounded-3xl border border-white/60 bg-white/70 p-8 shadow-lg shadow-brand-dark/5 backdrop-blur">
                        <div>
                            <Counter target={100} suffix="+" className="text-3xl font-bold text-brand" />
                            <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-stone-400">Proyek Selesai</p>
                        </div>
                        <div className="h-10 w-px bg-stone-200" />
                        <div>
                            <Counter target={15} suffix="+" className="text-3xl font-bold text-brand" />
                            <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-stone-400">Kota di Indonesia</p>
                        </div>
                    </Reveal>
                </section>

                {/* FILTER */}
                <section className="border-b border-stone-200 bg-[#f5f5f5] px-6 py-6">
                    <Reveal className="mx-auto flex max-w-6xl flex-wrap items-center justify-center gap-3">
                        <span className="text-sm font-semibold text-stone-400">Filter:</span>
                        {Object.entries(kategoriTab).map(([key, tab]) => (
                            <button
                                key={key}
                                type="button"
                                onClick={() => selectFilter(key)}
                                className={`flex items-center gap-1.5 rounded-full border-2 px-5 py-2 text-sm font-bold transition-all duration-300 ${
                                    activeFilter === key
                                        ? 'scale-105 border-brand bg-brand text-white shadow-md shadow-brand-dark/20'
                                        : 'border-stone-200 text-stone-600 hover:-translate-y-0.5 hover:border-brand hover:text-brand'
                                }`}
                            >
                                <Icon name={tab.icon} size={15} />
                                {tab.label}
                            </button>
                        ))}
                    </Reveal>
                </section>

                {/* GRID */}
                <section className="mx-auto max-w-6xl px-6 py-14">
                    <Reveal className="mb-6 text-center text-xs italic text-stone-400">Klik foto untuk lihat detail proyek</Reveal>

                    {filteredItems.length > 0 ? (
                        <div className={`grid grid-cols-1 gap-6 transition-opacity duration-200 sm:grid-cols-2 lg:grid-cols-3 ${fading ? 'opacity-0' : 'opacity-100'}`}>
                            {filteredItems.map((item) => (
                                <Reveal key={item.id} className="group overflow-hidden rounded-2xl bg-brand-50 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                                    <button type="button" onClick={() => openLightbox(item.id)} className="block w-full text-left">
                                        <div className="relative aspect-[4/3] overflow-hidden">
                                            <img
                                                src={item.src}
                                                alt={item.title}
                                                className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                            />
                                            {item.tall && (
                                                <span className="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[10px] font-bold text-brand shadow-sm">
                                                    Unggulan
                                                </span>
                                            )}
                                            <div className="absolute inset-0 flex items-center justify-center bg-brand/80 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                                <span className="flex items-center gap-1.5 text-sm font-bold text-white">
                                                    Lihat detail
                                                    <Icon name="arrow-right" size={14} />
                                                </span>
                                            </div>
                                        </div>
                                        <div className="p-5">
                                            <span className={`mb-2 inline-block rounded-full border px-2.5 py-1 text-[11px] font-bold ${item.badgeClass}`}>
                                                {item.badgeLabel}
                                            </span>
                                            <h3 className="truncate text-sm font-bold text-stone-900">{item.title}</h3>
                                            <p className="mt-1 flex items-center gap-1 text-xs text-stone-400">
                                                <Icon name="location-pin" size={12} className="text-brand" />
                                                {item.kota}
                                            </p>
                                        </div>
                                    </button>
                                </Reveal>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-2xl border-2 border-dashed border-stone-200 p-12 text-center text-sm text-stone-400">
                            Belum ada proyek untuk kategori ini.
                        </div>
                    )}
                </section>

                {/* CTA */}
                <section className="px-6 pb-16 lg:pb-24">
                    <Reveal className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-6 rounded-3xl bg-brand-50 p-8 text-center sm:flex-row sm:text-left lg:p-12">
                        <div>
                            <h2 className="text-2xl font-bold text-stone-900 md:text-3xl">Tertarik Punya Lapangan Seperti Ini?</h2>
                            <p className="mt-2 text-sm text-stone-500">Konsultasi gratis dengan tim kami, respons dalam 1 jam!</p>
                        </div>
                        <a
                            href={`${WA_LINK}?text=${encodeURIComponent('Halo, saya tertarik membangun lapangan setelah lihat galeri')}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex flex-shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-full bg-brand px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-dark/20 transition-transform duration-300 hover:-translate-y-0.5 hover:bg-brand-dark"
                        >
                            <Icon name="whatsapp-logo" size={16} className="text-white" />
                            Konsultasi Gratis
                        </a>
                    </Reveal>
                </section>

                <Footer />
                <BackToTop />
            </div>

            {activeItem && <Lightbox item={activeItem} onClose={() => setLightboxIndex(null)} onNav={navLightbox} />}
        </>
    );
}
