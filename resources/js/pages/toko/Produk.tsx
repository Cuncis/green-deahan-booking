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
    const [fotoAktif, setFotoAktif] = useState(0);
    const [varianId, setVarianId] = useState<number | null>(null);
    const adaVarian = produk.variants.length > 0;
    const varian = produk.variants.find((item) => item.id === varianId) ?? null;
    const labelVarian = (produk.variantLabel ?? 'Varian').toLowerCase();
    const hargaVarian = produk.variants.map((item) => item.price);
    const hargaSatuan = varian?.price ?? produk.price;
    const hargaBeda = adaVarian && varian === null && Math.max(...hargaVarian) !== Math.min(...hargaVarian);
    const foto = produk.images.length > 0 ? produk.images : produk.imageUrl ? [produk.imageUrl] : [];
    const aturQty = (nilai: number) => setQty(Math.min(maksQty, Math.max(1, Number.isFinite(nilai) ? Math.floor(nilai) : 1)));

    const perluPilih = adaVarian && varian === null;
    const hargaTampil = hargaBeda ? Math.min(...hargaVarian) : hargaSatuan;
    const diskon = produk.comparePrice && produk.comparePrice > hargaTampil ? Math.round(((produk.comparePrice - hargaTampil) / produk.comparePrice) * 100) : null;
    const merek = produk.specs.find((spek) => /^merek|^brand/i.test(spek.nama))?.nilai ?? null;
    const spekRingkas = produk.specs.filter((spek) => spek.nilai !== merek || !/^merek|^brand/i.test(spek.nama)).slice(0, 4);

    // "Lihat semua spesifikasi" switches to the Info Tambahan tab and scrolls to it.
    const bukaInfo = () => {
        setTab('info');
        window.requestAnimationFrame(() => document.getElementById('tab-info')?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
    };

    const tambah = (langsungBeli: boolean) =>
        router.post(
            '/keranjang',
            { product_id: produk.id, ...(varian ? { variant_id: varian.id } : {}), qty },
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
                                <GambarProduk src={foto[fotoAktif] ?? null} alt={produk.name} label={produk.categoryLabel} />
                            </div>
                            {foto.length > 1 && (
                                <ul className="gd-toko-thumbs" aria-label="Foto produk">
                                    {foto.map((url, urutan) => (
                                        <li key={url}>
                                            <button
                                                type="button"
                                                className={urutan === fotoAktif ? 'aktif' : undefined}
                                                aria-label={`Lihat foto ${urutan + 1} dari ${foto.length}`}
                                                aria-current={urutan === fotoAktif}
                                                onClick={() => setFotoAktif(urutan)}
                                            >
                                                <img src={url} alt="" loading="lazy" />
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                        <div className="col-xl-6">
                            <div className="product-info gd-detail-info ps-lg-4">
                                <p className="gd-detail-eyebrow">
                                    <a href={produk.kategoriUrl}>{produk.categoryLabel}</a>
                                    {merek && (
                                        <>
                                            <span aria-hidden="true"> · </span>
                                            <span>{merek}</span>
                                        </>
                                    )}
                                </p>
                                <h2>{produk.name}</h2>
                                <div className="gd-detail-harga">
                                    {hargaBeda && <span className="gd-detail-mulai">mulai</span>}
                                    <span className="gd-detail-angka">{rupiah(hargaTampil)}</span>
                                    <span className="gd-detail-satuan">/{produk.unit}</span>
                                    {diskon !== null && produk.comparePrice && (
                                        <>
                                            <del>{rupiah(produk.comparePrice)}</del>
                                            <span className="gd-detail-diskon">-{diskon}%</span>
                                        </>
                                    )}
                                </div>

                                <div className="gd-detail-beli cart-cta">
                                    {adaVarian && (
                                        <div className="gd-varian" role="radiogroup" aria-label={produk.variantLabel ?? 'Varian'}>
                                            <span className="gd-varian-judul">
                                                {produk.variantLabel ?? 'Varian'}
                                                {varian && <strong>: {varian.name}</strong>}
                                            </span>
                                            <div className="gd-varian-pilihan">
                                                {produk.variants.map((item) => (
                                                    <button
                                                        key={item.id}
                                                        type="button"
                                                        role="radio"
                                                        aria-checked={item.id === varianId}
                                                        className={`gd-varian-tombol${item.id === varianId ? ' aktif' : ''}`}
                                                        disabled={!item.available}
                                                        onClick={() => setVarianId(item.id === varianId ? null : item.id)}
                                                    >
                                                        {item.name}
                                                        {!item.available && <small> (habis)</small>}
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    <div className="gd-detail-jumlah">
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
                                        {!perluPilih && (
                                            <div className="gd-detail-subtotal" aria-live="polite">
                                                <span>Subtotal</span>
                                                <strong>{rupiah(hargaSatuan * qty)}</strong>
                                            </div>
                                        )}
                                    </div>

                                    {perluPilih && (
                                        <p className="gd-petunjuk-varian" role="status">
                                            Pilih {labelVarian} dulu untuk menambahkan ke keranjang.
                                        </p>
                                    )}
                                    <div className="gd-detail-tombol">
                                        <button type="button" className="btn gd-btn-keranjang" onClick={() => tambah(false)} disabled={memproses !== null || perluPilih}>
                                            {memproses === 'keranjang' ? 'Menambahkan...' : 'Tambah ke Keranjang'}
                                        </button>
                                        <button type="button" className="btn btn-primary btn-cta" onClick={() => tambah(true)} disabled={memproses !== null || perluPilih}>
                                            {memproses === 'beli' ? 'Memproses...' : 'Beli Sekarang'}
                                        </button>
                                    </div>
                                </div>

                                <ul className="gd-detail-jaminan">
                                    <li>
                                        <i className="feather-icon icon-truck" aria-hidden="true"></i>
                                        <span>
                                            <strong>Kirim ke seluruh Indonesia</strong>
                                            Ongkir dihitung saat checkout
                                        </span>
                                    </li>
                                    <li>
                                        <i className="feather-icon icon-mail" aria-hidden="true"></i>
                                        <span>
                                            <strong>Resi dikirim lewat email</strong>
                                            Status pesanan bisa dicek kapan saja
                                        </span>
                                    </li>
                                    <li>
                                        <i className="feather-icon icon-shield" aria-hidden="true"></i>
                                        <span>
                                            <strong>Pembayaran aman</strong>
                                            QRIS, virtual account, e-wallet
                                        </span>
                                    </li>
                                </ul>

                                {spekRingkas.length > 0 && (
                                    <div className="gd-detail-spek">
                                        <dl>
                                            {spekRingkas.map((spek) => (
                                                <div key={spek.nama}>
                                                    <dt>{spek.nama}</dt>
                                                    <dd>{spek.nilai}</dd>
                                                </div>
                                            ))}
                                        </dl>
                                        <button type="button" className="gd-detail-spek-semua" onClick={bukaInfo}>
                                            Lihat semua spesifikasi
                                        </button>
                                    </div>
                                )}
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
                                <p className="gd-teks-deskripsi">{produk.description || 'Belum ada deskripsi untuk produk ini. Tanya detailnya ke tim kami lewat WhatsApp.'}</p>
                            </div>
                            <div className={`tab-pane fade ${tab === 'info' ? 'show active' : ''}`} id="panel-info" role="tabpanel" aria-labelledby="tab-info">
                                <div className="product-additional-info">
                                    {produk.specs.map((spek) => (
                                        <div key={spek.nama} className="item">
                                            <span className="h5">{spek.nama}</span>
                                            <span>{spek.nilai}</span>
                                        </div>
                                    ))}
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
                            <div className="row products">
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
