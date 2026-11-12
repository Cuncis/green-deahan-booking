import '../../../fuzio/css/fuzio.css';
import { router } from '@inertiajs/react';
import Footer from '../../components/fuzio/Footer';
import Header from '../../components/fuzio/Header';
import PageHeader from '../../components/fuzio/PageHeader';
import ScrollToTop from '../../components/fuzio/elements/ScrollToTop';
import GambarProduk from '../../components/fuzio/toko/GambarProduk';
import PesanFlash from '../../components/fuzio/toko/PesanFlash';
import { rupiah, type Keranjang as DataKeranjang } from '../../components/fuzio/toko/types';
import { GD_CDN } from '../../data/greendeahan';

/** /keranjang: cart (session) with quantity controls and totals. Port of Fuzio's CartSection. */
export default function Keranjang({ keranjang, maksQty }: { keranjang: DataKeranjang; maksQty: number }) {
    const ubah = (id: number, qty: number) => router.patch(`/keranjang/${id}`, { qty }, { preserveScroll: true, only: ['keranjang', 'jumlahKeranjang'] });
    const hapus = (id: number) => router.delete(`/keranjang/${id}`, { preserveScroll: true, only: ['keranjang', 'jumlahKeranjang'] });

    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Keranjang" imgSrc={`${GD_CDN}/padel1-img.png`} crumbs={[{ label: 'Toko', href: '/toko' }, { label: 'Keranjang' }]} />

            <section className="cart-section gd-toko">
                <div className="container">
                    <PesanFlash />
                    {keranjang.items.length === 0 ? (
                        <div className="gd-kartu gd-kosong">
                            <h2>Keranjang masih kosong</h2>
                            <p>Yuk pilih perlengkapan olahraga dulu.</p>
                            <a href="/toko" className="btn btn-primary">
                                Belanja di Toko
                            </a>
                        </div>
                    ) : (
                        <div className="row g-4 align-items-start">
                            <div className="col-lg-8">
                                <div className="gd-kartu">
                                    <h2>{keranjang.jumlahItem} barang</h2>
                                    {keranjang.items.map((item) => (
                                        <div key={item.id} className="gd-keranjang-baris">
                                            <a href={`/toko/${item.slug}`} className="thumb" tabIndex={-1} aria-hidden="true">
                                                <GambarProduk src={item.imageUrl} alt={item.name} />
                                            </a>
                                            <div>
                                                <a href={`/toko/${item.slug}`} className="nama">
                                                    {item.name}
                                                </a>
                                                <div className="harga">{rupiah(item.price)} per barang</div>
                                            </div>
                                            <div className="kanan">
                                                <div className="cart-counter" role="group" aria-label={`Jumlah ${item.name}`}>
                                                    <button type="button" onClick={() => ubah(item.id, item.qty - 1)} disabled={item.qty <= 1} aria-label="Kurangi jumlah">
                                                        <i className="feather-icon icon-minus" aria-hidden="true"></i>
                                                    </button>
                                                    <input value={item.qty} readOnly aria-label="Jumlah" />
                                                    <button type="button" onClick={() => ubah(item.id, item.qty + 1)} disabled={item.qty >= maksQty} aria-label="Tambah jumlah">
                                                        <i className="feather-icon icon-plus" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                                <span className="subtotal">{rupiah(item.subtotal)}</span>
                                                <button type="button" className="gd-hapus" onClick={() => hapus(item.id)}>
                                                    Hapus
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div className="col-lg-4">
                                <aside className="gd-kartu">
                                    <h2>Ringkasan</h2>
                                    <div className="gd-ringkasan-baris">
                                        <span>Subtotal</span>
                                        <span>{rupiah(keranjang.subtotal)}</span>
                                    </div>
                                    <div className="gd-ringkasan-baris">
                                        <span>Ongkir</span>
                                        <span>{rupiah(keranjang.ongkir)}</span>
                                    </div>
                                    <div className="gd-ringkasan-baris total">
                                        <span>Total</span>
                                        <span>{rupiah(keranjang.total)}</span>
                                    </div>
                                    <div className="gd-ringkasan-aksi">
                                        <a href="/checkout" className="btn btn-primary">
                                            Lanjut ke Checkout
                                        </a>
                                        <a href="/toko" className="btn btn-outline-primary">
                                            Belanja Lagi
                                        </a>
                                    </div>
                                </aside>
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
