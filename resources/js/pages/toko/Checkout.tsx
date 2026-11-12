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

function Isian({ id, label, galat, ...props }: { id: Kolom; label: string; galat?: string } & InputHTMLAttributes<HTMLInputElement>) {
    return (
        <div>
            <label htmlFor={id}>{label}</label>
            <input id={id} name={id} className={galat ? 'is-invalid' : ''} aria-invalid={Boolean(galat)} aria-describedby={galat ? `${id}-galat` : undefined} {...props} />
            {galat && (
                <div id={`${id}-galat`} className="galat" role="alert">
                    {galat}
                </div>
            )}
        </div>
    );
}

/**
 * /checkout: shipping details, then the server creates the order and sends
 * the customer to Mayar's payment page. Only the address goes to the
 * server: prices are recalculated there from the database.
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
            <PageHeader title="Checkout" imgSrc={`${GD_CDN}/padel1-img.png`} crumbs={[{ label: 'Keranjang', href: '/keranjang' }, { label: 'Checkout' }]} />

            <section className="checkout-section gd-toko">
                <div className="container">
                    {sandbox && (
                        <div className="gd-sandbox" role="note">
                            MODE SANDBOX: pembayaran hanya uji coba, tidak ada uang asli yang diproses.
                        </div>
                    )}
                    <PesanFlash />

                    <form onSubmit={kirim} noValidate className="gd-form-toko">
                        <div className="row g-4 align-items-start">
                            <div className="col-lg-7">
                                <div className="gd-kartu">
                                    <h2>Data Pengiriman</h2>
                                    <div className="row g-3">
                                        <div className="col-12">
                                            <Isian {...isian('customer_name')} label="Nama lengkap" autoComplete="name" required />
                                        </div>
                                        <div className="col-md-6">
                                            <Isian {...isian('phone')} label="Nomor HP (WhatsApp)" type="tel" inputMode="tel" autoComplete="tel" placeholder="08xxxxxxxxxx" required />
                                        </div>
                                        <div className="col-md-6">
                                            <Isian {...isian('email')} label="Email" type="email" inputMode="email" autoComplete="email" placeholder="nama@email.com" required />
                                        </div>
                                        <div className="col-12">
                                            <label htmlFor="address">Alamat lengkap</label>
                                            <textarea
                                                id="address"
                                                value={form.data.address}
                                                onChange={(event) => form.setData('address', event.target.value)}
                                                autoComplete="street-address"
                                                placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan"
                                                className={form.errors.address ? 'is-invalid' : ''}
                                                aria-invalid={Boolean(form.errors.address)}
                                                required
                                            />
                                            {form.errors.address && (
                                                <div className="galat" role="alert">
                                                    {form.errors.address}
                                                </div>
                                            )}
                                        </div>
                                        <div className="col-md-6">
                                            <Isian {...isian('city')} label="Kota/Kabupaten" autoComplete="address-level2" required />
                                        </div>
                                        <div className="col-md-6">
                                            <Isian {...isian('province')} label="Provinsi" autoComplete="address-level1" required />
                                        </div>
                                        <div className="col-md-6">
                                            <Isian {...isian('postal_code')} label="Kode pos" inputMode="numeric" autoComplete="postal-code" maxLength={5} required />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="col-lg-5">
                                <aside className="gd-kartu">
                                    <h2>Pesanan Kamu</h2>
                                    {keranjang.items.map((item) => (
                                        <div key={item.id} className="gd-ringkasan-baris">
                                            <span>
                                                {item.qty} x {item.name}
                                            </span>
                                            <span>{rupiah(item.subtotal)}</span>
                                        </div>
                                    ))}
                                    <div className="gd-ringkasan-baris" style={{ borderTop: '1px solid #e7e5e4', marginTop: 8, paddingTop: 12 }}>
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
                                        <button type="submit" className="btn btn-primary" disabled={form.processing}>
                                            {form.processing ? 'Membuat pembayaran...' : `Bayar ${rupiah(keranjang.total)}`}
                                        </button>
                                    </div>
                                    <p className="mt-3 mb-0" style={{ fontSize: 14, color: '#555' }}>
                                        Kamu akan diarahkan ke halaman pembayaran Mayar (QRIS, e-wallet, atau transfer bank).
                                    </p>
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
