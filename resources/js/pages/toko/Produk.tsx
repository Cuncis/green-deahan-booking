import '../../../fuzio/css/fuzio.css';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import Footer from '../../components/fuzio/Footer';
import Header from '../../components/fuzio/Header';
import PageHeader from '../../components/fuzio/PageHeader';
import ScrollToTop from '../../components/fuzio/elements/ScrollToTop';
import GambarProduk from '../../components/fuzio/toko/GambarProduk';
import PesanFlash from '../../components/fuzio/toko/PesanFlash';
import ProdukCard from '../../components/fuzio/toko/ProdukCard';
import { rupiah, type ProdukPublik } from '../../components/fuzio/toko/types';
import { GD_CDN } from '../../data/greendeahan';

interface Props {
    produk: ProdukPublik & { weightGram: number };
    lainnya: ProdukPublik[];
    maksQty: number;
}

/** /toko/{slug}: product detail. Port of Fuzio's single-product page. */
export default function Produk({ produk, lainnya, maksQty }: Props) {
    const [qty, setQty] = useState(1);
    const [memproses, setMemproses] = useState<'keranjang' | 'beli' | null>(null);

    const tambah = (langsungBeli: boolean) =>
        router.post(
            '/keranjang',
            { product_id: produk.id, qty },
            {
                preserveScroll: true,
                onStart: () => setMemproses(langsungBeli ? 'beli' : 'keranjang'),
                onFinish: () => setMemproses(null),
                onSuccess: () => {
                    if (langsungBeli) {
                        router.visit('/checkout');
                    }
                },
            },
        );

    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title={produk.name} imgSrc={`${GD_CDN}/padel1-img.png`} crumbs={[{ label: 'Toko', href: '/toko' }, { label: produk.categoryLabel }]} />

            <section className="product-details gd-toko">
                <div className="container">
                    <PesanFlash />
                    <div className="row g-4 g-lg-5 align-items-start">
                        <div className="col-lg-6">
                            <div className="gd-toko-detail-media">
                                <GambarProduk src={produk.imageUrl} alt={produk.name} label={produk.categoryLabel} />
                            </div>
                        </div>
                        <div className="col-lg-6">
                            <div className="product-info ps-lg-4">
                                <span className="text-uppercase fw-semibold" style={{ color: '#006400', letterSpacing: '0.04em' }}>
                                    {produk.categoryLabel}
                                </span>
                                <h1>{produk.name}</h1>
                                <div className="product-price">{rupiah(produk.price)}</div>
                                {produk.description && <p>{produk.description}</p>}

                                <div className="gd-toko-aksi">
                                    <div className="cart-counter" role="group" aria-label="Jumlah">
                                        <button type="button" onClick={() => setQty((n) => Math.max(1, n - 1))} disabled={qty <= 1} aria-label="Kurangi jumlah">
                                            <i className="feather-icon icon-minus" aria-hidden="true"></i>
                                        </button>
                                        <input value={qty} readOnly aria-label="Jumlah" />
                                        <button type="button" onClick={() => setQty((n) => Math.min(maksQty, n + 1))} disabled={qty >= maksQty} aria-label="Tambah jumlah">
                                            <i className="feather-icon icon-plus" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <button type="button" className="btn btn-primary" onClick={() => tambah(false)} disabled={memproses !== null}>
                                        {memproses === 'keranjang' ? 'Menambahkan...' : 'Tambah ke Keranjang'}
                                    </button>
                                    <button type="button" className="btn btn-aksen" onClick={() => tambah(true)} disabled={memproses !== null}>
                                        {memproses === 'beli' ? 'Memproses...' : 'Beli Sekarang'}
                                    </button>
                                </div>

                                <div className="product-meta">
                                    {produk.weightGram > 0 && (
                                        <p className="sku">
                                            <strong>Berat:</strong> {produk.weightGram.toLocaleString('id-ID')} gram
                                        </p>
                                    )}
                                    <p className="sku">
                                        <strong>Pengiriman:</strong> ke seluruh Indonesia, nomor resi dikirim lewat email.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {lainnya.length > 0 && (
                        <div className="mt-5 pt-4">
                            <h2 className="mb-4" style={{ fontSize: 26 }}>
                                Produk {produk.categoryLabel} lainnya
                            </h2>
                            <div className="row g-4">
                                {lainnya.map((item) => (
                                    <div key={item.id} className="col-6 col-md-4">
                                        <ProdukCard produk={item} />
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </section>

            <Footer />
            <ScrollToTop />
        </div>
    );
}
