import '../../../fuzio/css/fuzio.css';
import Footer from '../../components/fuzio/Footer';
import Header from '../../components/fuzio/Header';
import PageHeader from '../../components/fuzio/PageHeader';
import ScrollToTop from '../../components/fuzio/elements/ScrollToTop';
import PesanFlash from '../../components/fuzio/toko/PesanFlash';
import ProdukCard from '../../components/fuzio/toko/ProdukCard';
import type { ProdukPublik } from '../../components/fuzio/toko/types';
import { GD_CDN } from '../../data/greendeahan';

interface Props {
    produk: ProdukPublik[];
    kategori: { nilai: string; label: string }[];
    kategoriAktif: string | null;
}

/** /toko: product grid with a category filter. Port of Fuzio's ShopSection. */
export default function Index({ produk, kategori, kategoriAktif }: Props) {
    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Toko" imgSrc={`${GD_CDN}/padel1-img.png`} crumbs={[{ label: 'Toko' }]} />

            <section className="shop-section gd-toko">
                <div className="container">
                    <PesanFlash />
                    <nav className="gd-toko-kategori" aria-label="Kategori produk">
                        <a href="/toko" className={kategoriAktif === null ? 'aktif' : ''} aria-current={kategoriAktif === null ? 'page' : undefined}>
                            Semua
                        </a>
                        {kategori.map((item) => (
                            <a
                                key={item.nilai}
                                href={`/toko?kategori=${item.nilai}`}
                                className={kategoriAktif === item.nilai ? 'aktif' : ''}
                                aria-current={kategoriAktif === item.nilai ? 'page' : undefined}
                            >
                                {item.label}
                            </a>
                        ))}
                    </nav>

                    {produk.length === 0 ? (
                        <div className="gd-kartu gd-kosong">
                            <p className="mb-0">Belum ada produk di kategori ini.</p>
                        </div>
                    ) : (
                        <div className="row g-4 products">
                            {produk.map((item) => (
                                <div key={item.id} className="col-6 col-md-4 col-xl-3">
                                    <ProdukCard produk={item} />
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </section>

            <Footer />
            <ScrollToTop />
        </div>
    );
}
