import '../../../fuzio/css/fuzio.css';
import { router } from '@inertiajs/react';
import Footer from '../../components/fuzio/Footer';
import Header from '../../components/fuzio/Header';
import PageHeader from '../../components/fuzio/PageHeader';
import ScrollToTop from '../../components/fuzio/elements/ScrollToTop';
import GambarProduk from '../../components/fuzio/toko/GambarProduk';
import JumlahKeranjang from '../../components/fuzio/toko/JumlahKeranjang';
import PesanFlash from '../../components/fuzio/toko/PesanFlash';
import { rupiah, type Keranjang as DataKeranjang } from '../../components/fuzio/toko/types';
import { GD_CDN } from '../../data/greendeahan';

/** /keranjang: port of Fuzio's CartSection. Table on desktop, a list on phones, totals on the side. */
export default function Keranjang({ keranjang, maksQty }: { keranjang: DataKeranjang; maksQty: number }) {
    const hapus = (id: number) => router.delete(`/keranjang/${id}`, { preserveScroll: true, only: ['keranjang', 'jumlahKeranjang'] });

    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Keranjang" imgSrc={`${GD_CDN}/minisoccer1-img.jpeg`} crumbs={[{ label: 'Toko', href: '/toko' }, { label: 'Keranjang' }]} />

            <div className="cart-section sec-padding gd-toko">
                <div className="container">
                    <PesanFlash />
                    {keranjang.items.length === 0 ? (
                        <div className="toko-empty">
                            <h2 className="h4">Keranjang masih kosong</h2>
                            <p>Yuk pilih produk dulu.</p>
                            <a href="/toko" className="btn btn-primary">
                                Belanja di Toko
                            </a>
                        </div>
                    ) : (
                        <div className="row g-4">
                            <div className="col-lg-8">
                                <div className="woocommerce rounded gd-tabel-keranjang d-none d-md-block">
                                    <table className="table shop_table cart">
                                        <thead className="shadow">
                                            <tr>
                                                <th className="product-name" colSpan={2}>
                                                    Produk
                                                </th>
                                                <th className="product-price">Harga</th>
                                                <th className="product-quantity">Jumlah</th>
                                                <th className="product-subtotal">Total</th>
                                                <th className="product-remove">
                                                    <span className="visually-hidden">Hapus</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {keranjang.items.map((item) => (
                                                <tr key={item.id} className="cart_item">
                                                    <td className="product-thumbnail">
                                                        <a href={`/toko/${item.slug}`} className="gd-thumb-kecil" tabIndex={-1} aria-hidden="true">
                                                            <GambarProduk src={item.imageUrl} alt={item.name} />
                                                        </a>
                                                    </td>
                                                    <td className="product-name">
                                                        <a href={`/toko/${item.slug}`}>{item.name}</a>
                                                    </td>
                                                    <td className="product-price">
                                                        {rupiah(item.price)}
                                                        <small className="d-block">/{item.unit}</small>
                                                    </td>
                                                    <td className="product-quantity">
                                                        <JumlahKeranjang id={item.id} nama={item.name} qty={item.qty} maks={maksQty} />
                                                    </td>
                                                    <td className="product-subtotal">
                                                        <strong>{rupiah(item.subtotal)}</strong>
                                                    </td>
                                                    <td className="product-remove">
                                                        <button type="button" className="remove" onClick={() => hapus(item.id)} aria-label={`Hapus ${item.name}`}>
                                                            <i className="feather-icon icon-x" aria-hidden="true"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                            <tr>
                                                <td colSpan={6} className="actions cart-form-footer p-3">
                                                    <div className="d-flex justify-content-end">
                                                        <a href="/toko" className="btn btn-primary">
                                                            Lanjut Belanja
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div className="gd-kartu d-md-none">
                                    {keranjang.items.map((item) => (
                                        <div key={item.id} className="gd-keranjang-baris">
                                            <a href={`/toko/${item.slug}`} className="thumb" tabIndex={-1} aria-hidden="true">
                                                <GambarProduk src={item.imageUrl} alt={item.name} />
                                            </a>
                                            <div>
                                                <a href={`/toko/${item.slug}`} className="nama">
                                                    {item.name}
                                                </a>
                                                <div className="harga">
                                                    {rupiah(item.price)} /{item.unit}
                                                </div>
                                            </div>
                                            <div className="kanan">
                                                <JumlahKeranjang id={item.id} nama={item.name} qty={item.qty} maks={maksQty} />
                                                <span className="subtotal">{rupiah(item.subtotal)}</span>
                                                <button type="button" className="gd-hapus" onClick={() => hapus(item.id)}>
                                                    Hapus
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                    <a href="/toko" className="btn btn-outline-primary w-100 mt-3">
                                        Lanjut Belanja
                                    </a>
                                </div>
                            </div>

                            <div className="col-lg-4">
                                <aside className="cart-totals ms-lg-2 p-lg-5 p-4 rounded gd-cart-totals">
                                    <table>
                                        <tbody>
                                            <tr>
                                                <th>Produk</th>
                                                <td>Subtotal</td>
                                            </tr>
                                            {keranjang.items.map((item) => (
                                                <tr key={item.id} className="product-list">
                                                    <th>
                                                        {item.name} <span className="gd-qty-kecil">x {item.qty}</span>
                                                    </th>
                                                    <td>{rupiah(item.subtotal)}</td>
                                                </tr>
                                            ))}
                                            <tr className="subtotal">
                                                <th>Subtotal</th>
                                                <td>
                                                    <span className="amount">{rupiah(keranjang.subtotal)}</span>
                                                </td>
                                            </tr>
                                            <tr className="shipping-cost">
                                                <th>Ongkir</th>
                                                <td>
                                                    <span className="amount">{rupiah(keranjang.ongkir)}</span>
                                                </td>
                                            </tr>
                                            <tr className="total">
                                                <th>Total</th>
                                                <td>
                                                    <strong>
                                                        <span className="amount">{rupiah(keranjang.total)}</span>
                                                    </strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div className="text-center mt-4">
                                        <a href="/checkout" className="btn btn-primary d-block">
                                            Lanjut ke Checkout
                                        </a>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <Footer />
            <ScrollToTop />
        </div>
    );
}
