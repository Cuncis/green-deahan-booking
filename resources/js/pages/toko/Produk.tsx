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
    produk: ProdukPublik & { weightGram: number; kategoriUrl: string };
    lainnya: ProdukPublik[];
    maksQty: number;
}

type Tab = 'deskripsi' | 'info';

/**
 * /toko/{slug}: port of Fuzio's single-product page (ProductReview +
 * ProductDetails): image, price per unit, quantity, add to cart / buy now,
 * then description and additional-info tabs. Reviews tab left out until
 * there are real customer reviews.
 */
export default function Produk({ produk, lainnya, maksQty }: Props) {
    const [qty, setQty] = useState(1);
    const [memproses, setMemproses] = useState<'keranjang' | 'beli' | null>(null);
    const [tab, setTab] = useState<Tab>('deskripsi');
    const aturQty = (nilai: number) => setQty(Math.min(maksQty, Math.max(1, Number.isFinite(nilai) ? Math.floor(nilai) : 1)));

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
            <PageHeader
                title="Detail Produk"
                imgSrc={`${GD_CDN}/minisoccer1-img.jpeg`}
                crumbs={[
                    { label: 'Toko', href: '/toko' },
                    { label: produk.categoryLabel, href: produk.kategoriUrl },
                ]}
            />

            <section className="product-details sec-padding gd-toko">
                <div className="container">
                    <PesanFlash />
                    <div className="row g-4 g-xl-5">
                        <div className="col-xl-6">
                            <div className="gd-toko-detail-media position-relative">
                                {produk.badge && <div className="gd-detail-badge text-uppercase">{produk.badge}</div>}
                                <GambarProduk src={produk.imageUrl} alt={produk.name} label={produk.categoryLabel} />
                            </div>
                        </div>
                        <div className="col-xl-6 z-2">
                            <div className="product-info ps-lg-4">
                                <h2>{produk.name}</h2>
                                <h3 className="product-price">
                                    {rupiah(produk.price)} <small>/{produk.unit}</small>
                                    {produk.comparePrice && <del>{rupiah(produk.comparePrice)}</del>}
                                </h3>
                                {produk.description && <p>{produk.description}</p>}

                                <div className="my-4 cart-cta">
                                    <div className="cart-number">
                                        <label htmlFor="jumlah-produk">Jumlah ({produk.unit})</label>
                                        <div className="d-flex align-items-center cart-counter mt-2">
                                            <button type="button" className="qty-minus" onClick={() => aturQty(qty - 1)} disabled={qty <= 1} aria-label="Kurangi jumlah">
                                                <i className="feather-icon icon-minus" aria-hidden="true"></i>
                                            </button>
                                            <input
                                                id="jumlah-produk"
                                                className="qty-count"
                                                type="number"
                                                inputMode="numeric"
                                                min={1}
                                                max={maksQty}
                                                value={qty}
                                                onChange={(event) => aturQty(Number(event.target.value))}
                                                onFocus={(event) => event.currentTarget.select()}
                                            />
                                            <button type="button" className="qty-plus" onClick={() => aturQty(qty + 1)} disabled={qty >= maksQty} aria-label="Tambah jumlah">
                                                <i className="feather-icon icon-plus" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div className="d-flex flex-wrap gap-2 mt-3">
                                        <button type="button" className="btn btn-dark text-primary" onClick={() => tambah(false)} disabled={memproses !== null}>
                                            {memproses === 'keranjang' ? 'Menambahkan...' : 'Tambah ke Keranjang'}
                                        </button>
                                        <button type="button" className="btn btn-primary" onClick={() => tambah(true)} disabled={memproses !== null}>
                                            {memproses === 'beli' ? 'Memproses...' : 'Beli Sekarang'}
                                        </button>
                                    </div>
                                </div>
                                <p className="gd-subtotal-produk">
                                    Subtotal: <strong>{rupiah(produk.price * qty)}</strong>
                                </p>

                                <div className="product-meta mt-4">
                                    <p className="sku">
                                        <strong>Kategori:</strong> <a href={produk.kategoriUrl}>{produk.categoryLabel}</a>
                                    </p>
                                    <p className="sku">
                                        <strong>Satuan:</strong> per {produk.unit}
                                    </p>
                                    <p className="sku">
                                        <i className="feather-icon icon-truck me-2" aria-hidden="true"></i>
                                        <strong>Pengiriman:</strong> ke seluruh Indonesia, nomor resi dikirim lewat email
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="product-details-tab mt-5 pt-lg-4">
                        <ul className="nav nav-tabs bg-dark border-0 rounded-pill justify-content-center p-3" role="tablist">
                            {(
                                [
                                    ['deskripsi', 'Deskripsi'],
                                    ['info', 'Info Tambahan'],
                                ] as [Tab, string][]
                            ).map(([nilai, label]) => (
                                <li key={nilai} className="nav-item" role="presentation">
                                    <button
                                        className={`nav-link ${tab === nilai ? 'active' : ''}`}
                                        id={`tab-${nilai}`}
                                        type="button"
                                        role="tab"
                                        aria-controls={`panel-${nilai}`}
                                        aria-selected={tab === nilai}
                                        onClick={() => setTab(nilai)}
                                    >
                                        {label}
                                    </button>
                                </li>
                            ))}
                        </ul>
                        <div className="tab-content mt-5">
                            <div className={`tab-pane fade ${tab === 'deskripsi' ? 'show active' : ''}`} id="panel-deskripsi" role="tabpanel" aria-labelledby="tab-deskripsi">
                                <p>{produk.description || 'Belum ada deskripsi untuk produk ini. Tanya detailnya ke tim kami lewat WhatsApp.'}</p>
                            </div>
                            <div className={`tab-pane fade ${tab === 'info' ? 'show active' : ''}`} id="panel-info" role="tabpanel" aria-labelledby="tab-info">
                                <div className="product-additional-info">
                                    <div className="item">
                                        <span className="h5">Kategori</span>
                                        <span>{produk.categoryLabel}</span>
                                    </div>
                                    <div className="item">
                                        <span className="h5">Satuan jual</span>
                                        <span>per {produk.unit}</span>
                                    </div>
                                    {produk.weightGram > 0 && (
                                        <div className="item">
                                            <span className="h5">Berat</span>
                                            <span>{produk.weightGram.toLocaleString('id-ID')} gram</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {lainnya.length > 0 && (
                        <div className="mt-5 pt-4">
                            <h2 className="sec-title text-center mb-5">Produk Terkait</h2>
                            <div className="row">
                                {lainnya.map((item) => (
                                    <div key={item.id} className="col-lg-4 col-md-6 col-6">
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
