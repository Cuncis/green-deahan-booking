import '../../../fuzio/css/fuzio.css';
import { Link, router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Footer from '../../components/fuzio/Footer';
import Header from '../../components/fuzio/Header';
import PageHeader from '../../components/fuzio/PageHeader';
import ScrollToTop from '../../components/fuzio/elements/ScrollToTop';
import WhatsAppIcon from '../../components/fuzio/elements/WhatsAppIcon';
import PesanFlash from '../../components/fuzio/toko/PesanFlash';
import ProdukCard from '../../components/fuzio/toko/ProdukCard';
import type { ProdukPublik } from '../../components/fuzio/toko/types';
import { GD_CDN, waLink } from '../../data/greendeahan';

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Filter {
    kategori: string | null;
    q: string | null;
    urut: string;
}

interface Props {
    produk: Paginated<ProdukPublik>;
    kategori: { nilai: string; label: string; jumlah: number }[];
    totalProduk: number;
    filter: Filter;
    pilihanUrutan: { nilai: string; label: string }[];
}

/**
 * /toko, port of the Toko reference (Fuzio's Shop page): product grid with a
 * sidebar (search, categories with counts, WhatsApp help), sorting and page
 * numbers. Filtering happens on the server through the query string.
 */
export default function Index({ produk, kategori, totalProduk, filter, pilihanUrutan }: Props) {
    const [cari, setCari] = useState(filter.q ?? '');

    const kunjungi = (perubahan: Partial<Filter>) => {
        const gabungan = { ...filter, ...perubahan };
        const query = Object.fromEntries(
            Object.entries({ kategori: gabungan.kategori, q: gabungan.q, urut: gabungan.urut === 'bawaan' ? null : gabungan.urut }).filter(([, nilai]) => nilai),
        );
        router.get('/toko', query, { preserveScroll: true, preserveState: true });
    };

    const kirimCari = (event: FormEvent) => {
        event.preventDefault();
        kunjungi({ q: cari.trim() || null });
    };

    const halamanAngka = produk.links.slice(1, -1);

    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Toko" imgSrc={`${GD_CDN}/minisoccer1-img.jpeg`} crumbs={[{ label: 'Produk & Layanan' }, { label: 'Toko' }]} />

            <section className="shop-section toko-section gd-toko">
                <div className="container">
                    <PesanFlash />
                    <div className="row">
                        <div className="col-lg-9 pe-lg-4">
                            <div className="shop-wrapper">
                                <div className="shop-filters d-sm-flex justify-content-between align-items-center">
                                    <p className="m-0" aria-live="polite">
                                        {produk.total === 0 ? 'Tidak ada produk' : `Menampilkan ${produk.from}–${produk.to} dari ${produk.total} produk`}
                                    </p>
                                    <div className="filter d-flex mt-3 mt-sm-0">
                                        <label htmlFor="urut-produk" className="visually-hidden">
                                            Urutkan
                                        </label>
                                        <select id="urut-produk" className="gd-urut" value={filter.urut} onChange={(event) => kunjungi({ urut: event.target.value })}>
                                            {pilihanUrutan.map((pilihan) => (
                                                <option key={pilihan.nilai} value={pilihan.nilai}>
                                                    {pilihan.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div className="row products mt-5">
                                    {produk.data.map((item) => (
                                        <div key={item.id} className="col-xl-4 col-md-6 col-6">
                                            <ProdukCard produk={item} />
                                        </div>
                                    ))}

                                    {produk.total === 0 && (
                                        <div className="col-12">
                                            <p className="toko-empty">
                                                {filter.q || filter.kategori
                                                    ? 'Produk yang Anda cari belum ada. Coba kata lain atau pilih kategori lain.'
                                                    : 'Belum ada produk yang dijual.'}
                                            </p>
                                        </div>
                                    )}

                                    {produk.last_page > 1 && (
                                        <div className="col-lg-12">
                                            <nav className="pager text-center mt-4" aria-label="Halaman produk">
                                                {produk.links[0].url ? (
                                                    <Link href={produk.links[0].url} preserveScroll={false} className="next-btn" aria-label="Halaman sebelumnya">
                                                        <i className="feather-icon icon-arrow-left" aria-hidden="true"></i>
                                                    </Link>
                                                ) : null}
                                                {halamanAngka.map((tautan) =>
                                                    tautan.url ? (
                                                        <Link
                                                            key={tautan.label}
                                                            href={tautan.url}
                                                            className={tautan.active ? 'current' : ''}
                                                            aria-current={tautan.active ? 'page' : undefined}
                                                        >
                                                            {tautan.label}
                                                        </Link>
                                                    ) : (
                                                        <span key={tautan.label}>{tautan.label}</span>
                                                    ),
                                                )}
                                                {produk.links[produk.links.length - 1].url ? (
                                                    <Link href={produk.links[produk.links.length - 1].url!} className="prev-btn" aria-label="Halaman berikutnya">
                                                        <i className="feather-icon icon-arrow-right" aria-hidden="true"></i>
                                                    </Link>
                                                ) : null}
                                            </nav>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="col-lg-3">
                            <aside className="sidebar">
                                <div className="widget search-widget">
                                    <h3 className="widget-title">Cari Produk</h3>
                                    <form className="position-relative" role="search" onSubmit={kirimCari}>
                                        <input
                                            type="search"
                                            placeholder="Cari di sini..."
                                            aria-label="Cari produk"
                                            value={cari}
                                            onChange={(event) => setCari(event.target.value)}
                                        />
                                        <button type="submit" className="bg-primary text-center" aria-label="Cari">
                                            <i className="feather-icon icon-search" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>

                                <div className="widget">
                                    <h3 className="widget-title">Kategori</h3>
                                    <div className="widget-inner rounded-2">
                                        <ul className="toko-kategori">
                                            {[{ nilai: null, label: 'Semua Produk', jumlah: totalProduk }, ...kategori].map((item) => (
                                                <li key={item.nilai ?? 'semua'}>
                                                    <a
                                                        href={item.nilai ? `/toko?kategori=${item.nilai}` : '/toko'}
                                                        className={filter.kategori === item.nilai ? 'active' : ''}
                                                        aria-current={filter.kategori === item.nilai ? 'true' : undefined}
                                                        onClick={(event) => {
                                                            event.preventDefault();
                                                            // Picking a category starts a fresh browse: clear the search.
                                                            setCari('');
                                                            kunjungi({ kategori: item.nilai, q: null });
                                                        }}
                                                    >
                                                        {item.label} <span>({item.jumlah})</span>
                                                    </a>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                </div>

                                <div className="widget">
                                    <h3 className="widget-title">Butuh Bantuan?</h3>
                                    <div className="widget-inner layanan-cta p-4 rounded-2">
                                        <p>Bingung pilih material atau butuh jumlah banyak? Tanya tim kami.</p>
                                        <a
                                            href={waLink('Halo Green Deahan, saya ingin tanya produk di toko.')}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="btn btn-primary btn-whatsapp w-100"
                                        >
                                            <WhatsAppIcon className="me-2" />
                                            Chat WhatsApp
                                        </a>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>
                </div>
            </section>

            <Footer />
            <ScrollToTop />
        </div>
    );
}
