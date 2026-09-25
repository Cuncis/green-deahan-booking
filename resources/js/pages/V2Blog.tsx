import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import Icon from '../components/Icon';
import Nav from '../components/Nav';
import Footer from '../components/Footer';
import Reveal from '../components/Reveal';
import PageStyles from '../components/PageStyles';
import BackToTop from '../components/BackToTop';

const WA_NUMBER = '6281357570064';
const WA_LINK = `https://wa.me/${WA_NUMBER}`;
const PAGE_SIZE = 6;

interface ArtikelProps {
    judul: string;
    slug: string;
    kategori: string;
    ringkasan: string;
    fotoUrl: string;
    tanggal: string;
}

interface V2BlogProps {
    artikel: ArtikelProps[];
}

export default function V2Blog({ artikel }: V2BlogProps) {
    const [searchQuery, setSearchQuery] = useState('');
    const [activeCategory, setActiveCategory] = useState('semua');
    const [page, setPage] = useState(1);

    const categories = useMemo(() => ['semua', ...Array.from(new Set(artikel.map((a) => a.kategori)))], [artikel]);

    const filtered = useMemo(() => {
        let result = artikel;
        if (activeCategory !== 'semua') {
            result = result.filter((a) => a.kategori === activeCategory);
        }
        if (searchQuery.trim()) {
            const q = searchQuery.trim().toLowerCase();
            result = result.filter((a) => a.judul.toLowerCase().includes(q) || a.ringkasan.toLowerCase().includes(q));
        }
        return result;
    }, [artikel, activeCategory, searchQuery]);

    const showFeatured = !searchQuery.trim() && activeCategory === 'semua';
    const featured = showFeatured ? filtered[0] : null;
    const gridSource = showFeatured ? filtered.slice(1) : filtered;

    const totalPages = Math.max(1, Math.ceil(gridSource.length / PAGE_SIZE));
    const pageItems = gridSource.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

    useEffect(() => {
        setPage(1);
    }, [activeCategory, searchQuery]);

    useEffect(() => {
        if (page > totalPages) setPage(totalPages);
    }, [page, totalPages]);

    return (
        <>
            <Head title="Blog dan Tips Lapangan Olahraga, Green Deahan Sport" />

            <PageStyles />

            <div className="bg-[#f5f5f5] text-stone-600 antialiased">
                <Nav />

                {/* HERO */}
                <section className="bg-brand-50 pb-14 pt-32 text-center">
                    <Reveal className="mx-auto max-w-2xl px-6">
                        <p className="text-xs text-stone-500">
                            <a href="/v2" className="hover:underline">
                                Beranda
                            </a>{' '}
                            / Blog
                        </p>
                        <h1 className="mt-3 text-4xl font-bold leading-tight text-stone-900 md:text-5xl">
                            Blog <span className="text-brand">Green Deahan Sport</span>
                        </h1>
                        <p className="mx-auto mt-3 max-w-xl text-base leading-relaxed text-stone-500">
                            Tips membangun lapangan, panduan memilih material, estimasi biaya, dan insight bisnis lapangan olahraga di Indonesia.
                        </p>
                        <div className="mx-auto mt-6 flex max-w-md items-center gap-2 rounded-full bg-[#f5f5f5] p-1.5 shadow-sm">
                            <span className="pl-2.5 text-stone-400">
                                <Icon name="search" size={16} />
                            </span>
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Cari artikel..."
                                className="flex-1 bg-transparent px-1 py-2 text-sm outline-none placeholder:text-stone-400"
                            />
                            <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-brand text-white">
                                <Icon name="search" size={15} />
                            </span>
                        </div>
                    </Reveal>
                </section>

                {/* CATEGORY FILTER */}
                {categories.length > 1 && (
                    <section className="mx-auto mb-8 mt-8 max-w-5xl px-6">
                        <Reveal className="flex flex-wrap justify-center gap-2">
                            {categories.map((cat) => (
                                <button
                                    key={cat}
                                    type="button"
                                    onClick={() => setActiveCategory(cat)}
                                    className={`rounded-full border-2 px-4 py-2 text-xs font-bold transition-all duration-300 ${
                                        activeCategory === cat
                                            ? 'border-brand bg-brand text-white shadow-md shadow-brand-dark/20'
                                            : 'border-stone-200 text-stone-600 hover:border-brand hover:text-brand'
                                    }`}
                                >
                                    {cat === 'semua' ? 'Semua' : cat}
                                </button>
                            ))}
                        </Reveal>
                    </section>
                )}

                {/* BLOG LIST */}
                <section className="mx-auto max-w-5xl px-6 pb-16">
                    {filtered.length === 0 ? (
                        <div className="py-20 text-center">
                            <h3 className="mb-2 text-xl font-bold text-stone-700">{searchQuery ? 'Artikel tidak ditemukan' : 'Belum ada artikel'}</h3>
                            <p className="text-sm text-stone-400">{searchQuery ? 'Coba kata kunci lain.' : 'Artikel akan segera hadir.'}</p>
                        </div>
                    ) : (
                        <>
                            {/* Featured post */}
                            {featured && (
                                <Reveal className="mb-10">
                                    <a href={`/blog/${featured.slug}`} className="group block overflow-hidden rounded-3xl">
                                        <div className="grid gap-6 md:grid-cols-2 md:items-center">
                                            <div className="relative overflow-hidden rounded-3xl" style={{ minHeight: 260 }}>
                                                <img
                                                    src={featured.fotoUrl}
                                                    alt={featured.judul}
                                                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                />
                                            </div>
                                            <div>
                                                <div className="mb-3 flex flex-wrap items-center gap-2">
                                                    <span className="rounded-full bg-stone-900 px-3 py-1 text-xs font-bold text-white">Artikel Pilihan</span>
                                                    <span className="rounded-full bg-brand-100 px-2.5 py-1 text-xs font-bold text-brand">{featured.kategori}</span>
                                                </div>
                                                <h2 className="mb-3 text-2xl font-bold leading-tight text-stone-900 transition-colors group-hover:text-brand md:text-3xl">
                                                    {featured.judul}
                                                </h2>
                                                <p className="mb-4 line-clamp-3 text-sm leading-relaxed text-stone-500">{featured.ringkasan}</p>
                                                <div className="flex items-center gap-3 text-xs text-stone-400">
                                                    <span>{featured.tanggal}</span>
                                                    <span className="inline-flex items-center gap-1 font-bold text-brand group-hover:underline">
                                                        Baca selengkapnya <Icon name="arrow-right" size={13} />
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </Reveal>
                            )}

                            {/* Grid */}
                            <div className="grid grid-cols-1 gap-x-8 gap-y-10 md:grid-cols-2">
                                {pageItems.map((post) => (
                                    <Reveal key={post.slug}>
                                        <a href={`/blog/${post.slug}`} className="group block overflow-hidden">
                                            <div className="relative overflow-hidden rounded-3xl">
                                                <span className="absolute left-4 top-4 rounded bg-stone-900 px-2 py-1 text-xs font-semibold text-white">
                                                    {post.kategori}
                                                </span>
                                                <span className="absolute left-4 top-12 rounded bg-[#f5f5f5] px-2 py-1 text-xs font-semibold text-stone-700 shadow-sm">
                                                    {post.tanggal}
                                                </span>
                                                <img
                                                    src={post.fotoUrl}
                                                    alt={post.judul}
                                                    className="aspect-[16/10] w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                />
                                            </div>
                                            <h3 className="mt-3 line-clamp-2 text-base font-bold leading-tight text-stone-900 transition-colors group-hover:text-brand">
                                                {post.judul}
                                            </h3>
                                            <p className="mt-1 line-clamp-2 text-xs leading-relaxed text-stone-500">{post.ringkasan}</p>
                                            <span className="mt-2 inline-flex items-center gap-1 text-sm font-bold text-brand">
                                                Baca lebih <Icon name="arrow-right" size={13} />
                                            </span>
                                        </a>
                                    </Reveal>
                                ))}
                            </div>

                            {/* Pagination */}
                            {totalPages > 1 && (
                                <Reveal className="mt-10 flex justify-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setPage((p) => Math.max(1, p - 1))}
                                        disabled={page === 1}
                                        className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-stone-600 transition-colors hover:bg-brand-100 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        <Icon name="chevron-left" size={16} />
                                    </button>
                                    {Array.from({ length: totalPages }, (_, i) => i + 1).map((n) => (
                                        <button
                                            key={n}
                                            type="button"
                                            onClick={() => setPage(n)}
                                            className={`flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold transition-colors ${
                                                page === n ? 'bg-brand text-white' : 'bg-brand-50 text-stone-600 hover:bg-brand-100'
                                            }`}
                                        >
                                            {n}
                                        </button>
                                    ))}
                                    <button
                                        type="button"
                                        onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                                        disabled={page === totalPages}
                                        className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-50 text-stone-600 transition-colors hover:bg-brand-100 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        <Icon name="chevron-right" size={16} />
                                    </button>
                                </Reveal>
                            )}
                        </>
                    )}

                    {/* WhatsApp update opt-in (repurposed from Folio's newsletter box: no real email backend, so this is a genuine working CTA instead) */}
                    <Reveal className="mx-auto mt-14 max-w-2xl rounded-3xl bg-brand-50 p-8 text-center">
                        <h3 className="text-2xl font-bold text-stone-900">
                            Dapatkan update <span className="text-brand">artikel terbaru</span>
                        </h3>
                        <p className="mx-auto mt-2 max-w-md text-sm text-stone-500">
                            Chat tim kami di WhatsApp untuk dapat kabar setiap ada tips dan panduan baru seputar lapangan olahraga.
                        </p>
                        <a
                            href={`${WA_LINK}?text=${encodeURIComponent('Halo, saya ingin dapat update artikel dan tips terbaru dari Green Deahan Sport')}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="mt-4 inline-flex items-center gap-2 rounded-full bg-brand px-6 py-3 text-sm font-bold text-white shadow-lg shadow-brand-dark/20 transition-transform duration-300 hover:-translate-y-0.5 hover:bg-brand-dark"
                        >
                            <Icon name="whatsapp-logo" size={16} className="text-white" />
                            Chat via WhatsApp
                        </a>
                    </Reveal>
                </section>

                {/* CTA */}
                <section className="px-6 pb-16 lg:pb-24">
                    <Reveal className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-6 rounded-3xl bg-brand-50 p-8 text-center sm:flex-row sm:text-left lg:p-12">
                        <div>
                            <h2 className="text-2xl font-bold text-stone-900 md:text-3xl">Siap Membangun Lapangan Impian?</h2>
                            <p className="mt-2 text-sm text-stone-500">Konsultasi gratis dengan tim ahli kami, respons dalam 1 jam!</p>
                        </div>
                        <a
                            href={`${WA_LINK}?text=${encodeURIComponent('Halo, saya ingin konsultasi lapangan setelah baca blog')}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex flex-shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-full bg-brand px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-brand-dark/20 transition-transform duration-300 hover:-translate-y-0.5 hover:bg-brand-dark"
                        >
                            <Icon name="whatsapp-logo" size={16} className="text-white" />
                            Konsultasi Gratis Sekarang
                        </a>
                    </Reveal>
                </section>

                <Footer />
                <BackToTop />
            </div>
        </>
    );
}
