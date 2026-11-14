import '../../../fuzio/css/fuzio.css';
import { useForm } from '@inertiajs/react';
import type { FormEvent, InputHTMLAttributes } from 'react';
import Footer from '../../components/fuzio/Footer';
import Header from '../../components/fuzio/Header';
import PageHeader from '../../components/fuzio/PageHeader';
import ScrollToTop from '../../components/fuzio/elements/ScrollToTop';
import PesanFlash from '../../components/fuzio/toko/PesanFlash';
import { rupiah, type Keranjang } from '../../components/fuzio/toko/types';
import { GD_CDN } from '../../data/greendeahan';

type Kolom = 'customer_name' | 'phone' | 'email' | 'address' | 'city' | 'province' | 'postal_code';

function Isian({ id, label, galat, wajib = true, ...props }: { id: Kolom; label: string; galat?: string; wajib?: boolean } & InputHTMLAttributes<HTMLInputElement>) {
    return (
        <>
            <label htmlFor={id}>
                {label}
                {wajib && '*'}
            </label>
            <input id={id} name={id} className={galat ? 'is-invalid' : ''} aria-invalid={Boolean(galat)} aria-describedby={galat ? `${id}-galat` : undefined} {...props} />
            {galat && (
                <div id={`${id}-galat`} className="galat" role="alert">
                    {galat}
                </div>
            )}
        </>
    );
}

/**
 * /checkout: port of Fuzio's CheckoutSection. Delivery details on the left,
 * order summary and payment on the right. Only the address is sent: the
 * server recalculates every price, creates the order and sends the customer
 * to Mayar's payment page.
 */
export default function Checkout({ keranjang, sandbox }: { keranjang: Keranjang; sandbox: boolean }) {
    const form = useForm<Record<Kolom, string>>({ customer_name: '', phone: '', email: '', address: '', city: '', province: '', postal_code: '' });

    const kirim = (event: FormEvent) => {
        event.preventDefault();
        form.post('/checkout', { preserveScroll: true });
    };

    const isian = (id: Kolom) => ({
        id,
        value: form.data[id],
        galat: form.errors[id],
        onChange: (event: React.ChangeEvent<HTMLInputElement>) => form.setData(id, event.target.value),
    });

    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Checkout" imgSrc={`${GD_CDN}/minisoccer1-img.jpeg`} crumbs={[{ label: 'Keranjang', href: '/keranjang' }, { label: 'Checkout' }]} />

            <section className="checkout-section sec-padding gd-toko">
                <div className="container">
                    {sandbox && (
                        <div className="gd-sandbox" role="note">
                            MODE SANDBOX: pembayaran hanya uji coba, tidak ada uang asli yang diproses.
                        </div>
                    )}
                    <PesanFlash />

                    <form className="form checkout-form gd-form-toko" onSubmit={kirim} noValidate>
                        <div className="row g-4">
                            <div className="col-lg-8">
                                <h3 className="sub-title">Detail Pengiriman</h3>
                                <div className="checkout-billingform gd-kartu-form rounded p-lg-5 p-3 mt-4">
                                    <div className="row g-3">
                                        <div className="col-12">
                                            <Isian {...isian('customer_name')} label="Nama lengkap" autoComplete="name" placeholder="Nama penerima" />
                                        </div>
                                        <div className="col-lg-6">
                                            <Isian {...isian('phone')} label="Nomor HP (WhatsApp)" type="tel" inputMode="tel" autoComplete="tel" placeholder="08xxxxxxxxxx" />
                                        </div>
                                        <div className="col-lg-6">
                                            <Isian {...isian('email')} label="Email" type="email" inputMode="email" autoComplete="email" placeholder="nama@email.com" />
                                        </div>
                                        <div className="col-12">
                                            <label htmlFor="address">Alamat lengkap*</label>
                                            <textarea
                                                id="address"
                                                value={form.data.address}
                                                onChange={(event) => form.setData('address', event.target.value)}
                                                autoComplete="street-address"
                                                placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan"
                                                className={form.errors.address ? 'is-invalid' : ''}
                                                aria-invalid={Boolean(form.errors.address)}
                                            />
                                            {form.errors.address && (
                                                <div className="galat" role="alert">
                                                    {form.errors.address}
                                                </div>
                                            )}
                                        </div>
                                        <div className="col-lg-6">
                                            <Isian {...isian('city')} label="Kota/Kabupaten" autoComplete="address-level2" />
                                        </div>
                                        <div className="col-lg-6">
                                            <Isian {...isian('province')} label="Provinsi" autoComplete="address-level1" />
                                        </div>
                                        <div className="col-lg-6">
                                            <Isian {...isian('postal_code')} label="Kode pos" inputMode="numeric" autoComplete="postal-code" maxLength={5} />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="col-lg-4">
                                <aside className="order-summery ms-xl-3">
                                    <h3 className="sub-title">Pesanan Anda</h3>
                                    <div className="cart-totals gd-cart-totals mt-4 p-4 rounded">
                                        <table>
                                            <tbody>
                                                <tr>
                                                    <th>Produk</th>
                                                    <td>Subtotal</td>
                                                </tr>
                                                {keranjang.items.map((item) => (
                                                    <tr key={item.id} className="product-list">
                                                        <th>
                                                            {item.name}{' '}
                                                            <span className="gd-qty-kecil">
                                                                x {item.qty} {item.unit}
                                                            </span>
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

                                        <div className="gd-pembayaran mt-4">
                                            <strong>Pembayaran online lewat Mayar</strong>
                                            <p className="mb-0">
                                                QRIS, transfer virtual account bank, atau e-wallet. Anda diarahkan ke halaman pembayaran setelah menekan tombol di bawah.
                                            </p>
                                        </div>

                                        <div className="text-center mt-4">
                                            <button type="submit" className="btn btn-primary d-block w-100" disabled={form.processing}>
                                                {form.processing ? 'Membuat pembayaran...' : `Bayar ${rupiah(keranjang.total)}`}
                                            </button>
                                        </div>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <Footer />
            <ScrollToTop />
        </div>
    );
}
