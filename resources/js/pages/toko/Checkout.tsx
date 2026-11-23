import '../../../fuzio/css/fuzio.css';
import { useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent, type InputHTMLAttributes } from 'react';
import Footer from '../../components/fuzio/Footer';
import Header from '../../components/fuzio/Header';
import PageHeader from '../../components/fuzio/PageHeader';
import ScrollToTop from '../../components/fuzio/elements/ScrollToTop';
import PesanFlash from '../../components/fuzio/toko/PesanFlash';
import { rupiah, type Keranjang } from '../../components/fuzio/toko/types';
import { GD_CDN } from '../../data/greendeahan';

type Kolom = 'customer_name' | 'phone' | 'email' | 'address' | 'wilayah_kode' | 'postal_code' | 'layanan';

interface OpsiKirim {
    kode: string;
    label: string;
    duration: string;
    price: number;
    /** One parcel per supplier origin; the prices add up to `price`. */
    paket: { asal: string; kodePos: string; barang: string[]; price: number }[];
}

interface Wilayah {
    kode: string;
    nama: string;
    /** Only on kelurahan/desa. */
    kodepos?: string;
}

/** Responses of /wilayah are static for a day, so each parent is fetched once per visit. */
const cacheWilayah = new Map<string, Wilayah[]>();

/**
 * Options one level below `induk` (null = provinces). Returns [] while
 * nothing is chosen above, and reports loading and error state for the select.
 */
function useWilayah(url: string, induk: string | null, aktif: boolean) {
    const [opsi, setOpsi] = useState<Wilayah[]>([]);
    const [memuat, setMemuat] = useState(false);
    const [gagal, setGagal] = useState(false);
    const [ulang, setUlang] = useState(0);

    useEffect(() => {
        if (!aktif) {
            setOpsi([]);
            setGagal(false);
            return;
        }
        const kunci = induk ?? '';
        const tersimpan = cacheWilayah.get(kunci);
        if (tersimpan) {
            setOpsi(tersimpan);
            setGagal(false);
            return;
        }

        const batal = new AbortController();
        setMemuat(true);
        setGagal(false);
        setOpsi([]);
        fetch(induk ? `${url}?induk=${encodeURIComponent(induk)}` : url, { headers: { Accept: 'application/json' }, signal: batal.signal })
            .then((respons) => (respons.ok ? (respons.json() as Promise<Wilayah[]>) : Promise.reject(new Error('gagal'))))
            .then((daftar) => {
                cacheWilayah.set(kunci, daftar);
                setOpsi(daftar);
                setMemuat(false);
            })
            .catch((galat: Error) => {
                if (galat.name !== 'AbortError') {
                    setGagal(true);
                    setMemuat(false);
                }
            });

        return () => batal.abort();
    }, [url, induk, aktif, ulang]);

    return { opsi, memuat, gagal, muatUlang: () => setUlang((nilai) => nilai + 1) };
}

function PilihWilayah({
    id,
    label,
    value,
    onChange,
    daftar,
    memuat,
    gagal,
    muatUlang,
    aktif,
    kosong,
    galat,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (kode: string) => void;
    daftar: Wilayah[];
    memuat: boolean;
    gagal: boolean;
    muatUlang: () => void;
    aktif: boolean;
    kosong: string;
    galat?: string;
}) {
    return (
        <>
            <label htmlFor={id}>{label}*</label>
            <select
                id={id}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                disabled={!aktif || memuat}
                className={galat ? 'is-invalid' : ''}
                aria-invalid={Boolean(galat)}
                aria-describedby={galat ? `${id}-galat` : undefined}
            >
                <option value="">{memuat ? 'Memuat...' : kosong}</option>
                {daftar.map((item) => (
                    <option key={item.kode} value={item.kode}>
                        {item.nama}
                    </option>
                ))}
            </select>
            {gagal && (
                <div className="galat" role="alert">
                    Daftar gagal dimuat.{' '}
                    <button type="button" className="gd-tautan-ulang" onClick={muatUlang}>
                        Coba lagi
                    </button>
                </div>
            )}
            {galat && (
                <div id={`${id}-galat`} className="galat" role="alert">
                    {galat}
                </div>
            )}
        </>
    );
}

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
 * Courier services and prices from Biteship for the chosen village. Display
 * only: the server prices the order again from the chosen service code.
 */
function useOngkir(url: string, aktif: boolean, wilayahKode: string, kodepos: string) {
    const [opsi, setOpsi] = useState<OpsiKirim[]>([]);
    const [memuat, setMemuat] = useState(false);
    const [galat, setGalat] = useState('');
    const [pesanDarurat, setPesanDarurat] = useState('');
    const [ulang, setUlang] = useState(0);
    const siap = aktif && wilayahKode !== '' && /^\d{5}$/.test(kodepos);

    useEffect(() => {
        if (!siap) {
            setOpsi([]);
            setGalat('');
            setPesanDarurat('');
            setMemuat(false);
            return;
        }

        const batal = new AbortController();
        // Wait a moment so typing a postal code does not fire a request per digit.
        const tunda = setTimeout(() => {
            setMemuat(true);
            setGalat('');
            setPesanDarurat('');
            fetch(`${url}?${new URLSearchParams({ wilayah_kode: wilayahKode, postal_code: kodepos })}`, { headers: { Accept: 'application/json' }, signal: batal.signal })
                .then(async (respons) => {
                    const data = (await respons.json().catch(() => ({}))) as { opsi?: OpsiKirim[]; message?: string; detail?: string; darurat?: boolean };
                    if (!respons.ok) throw new Error([data.message ?? 'Ongkir belum bisa dihitung.', data.detail].filter(Boolean).join(' '));

                    return { daftar: data.opsi ?? [], darurat: data.darurat ? [data.message, data.detail].filter(Boolean).join(' ') : '' };
                })
                .then(({ daftar, darurat }) => {
                    setOpsi(daftar);
                    setPesanDarurat(darurat);
                    setMemuat(false);
                })
                .catch((error: Error) => {
                    if (error.name !== 'AbortError') {
                        setOpsi([]);
                        setGalat(error.message || 'Ongkir belum bisa dihitung.');
                        setMemuat(false);
                    }
                });
        }, 350);

        return () => {
            clearTimeout(tunda);
            batal.abort();
        };
    }, [url, siap, wilayahKode, kodepos, ulang]);

    return { opsi, memuat, galat, pesanDarurat, siap, muatUlang: () => setUlang((nilai) => nilai + 1) };
}

/**
 * /checkout: port of Fuzio's CheckoutSection. Delivery details on the left,
 * order summary and payment on the right. Only the address is sent: the
 * server recalculates every price, creates the order and sends the customer
 * to Mayar's payment page.
 */
export default function Checkout({
    keranjang,
    sandbox,
    wilayahTersedia,
    wilayahUrl,
    ongkirUrl,
}: {
    keranjang: Keranjang;
    sandbox: boolean;
    wilayahTersedia: boolean;
    wilayahUrl: string;
    ongkirUrl: string;
}) {
    const form = useForm<Record<Kolom, string>>({ customer_name: '', phone: '', email: '', address: '', wilayah_kode: '', postal_code: '', layanan: '' });
    const [provinsi, setProvinsi] = useState('');
    const [kota, setKota] = useState('');
    const [kecamatan, setKecamatan] = useState('');

    const daftarProvinsi = useWilayah(wilayahUrl, null, wilayahTersedia);
    const daftarKota = useWilayah(wilayahUrl, provinsi, provinsi !== '');
    const daftarKecamatan = useWilayah(wilayahUrl, kota, kota !== '');
    const daftarKelurahan = useWilayah(wilayahUrl, kecamatan, kecamatan !== '');

    const kelurahan = daftarKelurahan.opsi.find((item) => item.kode === form.data.wilayah_kode);
    const kodeposOtomatis = kelurahan?.kodepos ?? '';

    const ongkirDinamis = keranjang.ongkirDinamis;
    const ongkir = useOngkir(ongkirUrl, ongkirDinamis && wilayahTersedia, form.data.wilayah_kode, kodeposOtomatis || form.data.postal_code);
    const layananDipilih = ongkir.opsi.find((item) => item.kode === form.data.layanan);
    const ongkirTampil = ongkirDinamis ? (layananDipilih?.price ?? null) : keranjang.ongkir;
    const total = keranjang.subtotal + (ongkirTampil ?? 0);

    // A service that disappeared after the list refreshed (other address, courier changed) must be picked again.
    useEffect(() => {
        if (form.data.layanan !== '' && !ongkir.memuat && ongkir.siap && !ongkir.opsi.some((item) => item.kode === form.data.layanan)) {
            form.setData('layanan', '');
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ongkir.opsi, ongkir.memuat]);

    // Changing a level clears everything below it, and the postal code that came with the old village.
    const pilihProvinsi = (kode: string) => {
        setProvinsi(kode);
        setKota('');
        setKecamatan('');
        form.setData((data) => ({ ...data, wilayah_kode: '', postal_code: '', layanan: '' }));
    };
    const pilihKota = (kode: string) => {
        setKota(kode);
        setKecamatan('');
        form.setData((data) => ({ ...data, wilayah_kode: '', postal_code: '', layanan: '' }));
    };
    const pilihKecamatan = (kode: string) => {
        setKecamatan(kode);
        form.setData((data) => ({ ...data, wilayah_kode: '', postal_code: '', layanan: '' }));
    };
    const pilihKelurahan = (kode: string) => {
        const dipilih = daftarKelurahan.opsi.find((item) => item.kode === kode);
        form.setData((data) => ({ ...data, wilayah_kode: kode, postal_code: dipilih?.kodepos ?? '', layanan: '' }));
    };

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
                                        {wilayahTersedia ? (
                                            <>
                                                <div className="col-lg-6">
                                                    <PilihWilayah
                                                        id="provinsi"
                                                        label="Provinsi"
                                                        value={provinsi}
                                                        onChange={pilihProvinsi}
                                                        daftar={daftarProvinsi.opsi}
                                                        memuat={daftarProvinsi.memuat}
                                                        gagal={daftarProvinsi.gagal}
                                                        muatUlang={daftarProvinsi.muatUlang}
                                                        aktif
                                                        kosong="Pilih provinsi"
                                                    />
                                                </div>
                                                <div className="col-lg-6">
                                                    <PilihWilayah
                                                        id="kota"
                                                        label="Kota/Kabupaten"
                                                        value={kota}
                                                        onChange={pilihKota}
                                                        daftar={daftarKota.opsi}
                                                        memuat={daftarKota.memuat}
                                                        gagal={daftarKota.gagal}
                                                        muatUlang={daftarKota.muatUlang}
                                                        aktif={provinsi !== ''}
                                                        kosong="Pilih kota/kabupaten"
                                                    />
                                                </div>
                                                <div className="col-lg-6">
                                                    <PilihWilayah
                                                        id="kecamatan"
                                                        label="Kecamatan"
                                                        value={kecamatan}
                                                        onChange={pilihKecamatan}
                                                        daftar={daftarKecamatan.opsi}
                                                        memuat={daftarKecamatan.memuat}
                                                        gagal={daftarKecamatan.gagal}
                                                        muatUlang={daftarKecamatan.muatUlang}
                                                        aktif={kota !== ''}
                                                        kosong="Pilih kecamatan"
                                                    />
                                                </div>
                                                <div className="col-lg-6">
                                                    <PilihWilayah
                                                        id="kelurahan"
                                                        label="Kelurahan/Desa"
                                                        value={form.data.wilayah_kode}
                                                        onChange={pilihKelurahan}
                                                        daftar={daftarKelurahan.opsi}
                                                        memuat={daftarKelurahan.memuat}
                                                        gagal={daftarKelurahan.gagal}
                                                        muatUlang={daftarKelurahan.muatUlang}
                                                        aktif={kecamatan !== ''}
                                                        kosong="Pilih kelurahan/desa"
                                                        galat={form.errors.wilayah_kode}
                                                    />
                                                </div>
                                                <div className="col-lg-6">
                                                    <Isian
                                                        {...isian('postal_code')}
                                                        label="Kode pos"
                                                        inputMode="numeric"
                                                        autoComplete="postal-code"
                                                        maxLength={5}
                                                        value={kodeposOtomatis || form.data.postal_code}
                                                        readOnly={kodeposOtomatis !== ''}
                                                        disabled={form.data.wilayah_kode === ''}
                                                        placeholder="Terisi otomatis"
                                                        wajib={false}
                                                    />
                                                    {kelurahan && !kodeposOtomatis && (
                                                        <div className="gd-petunjuk">Kode pos kelurahan ini belum ada di data kami, isi sendiri.</div>
                                                    )}
                                                </div>
                                                {ongkirDinamis && (
                                                    <div className="col-12">
                                                        <fieldset className="gd-kurir" aria-describedby={form.errors.layanan ? 'layanan-galat' : undefined}>
                                                            <legend>Pengiriman (ongkir perkiraan)*</legend>
                                                            {!ongkir.siap && <p className="gd-petunjuk">Pilih kelurahan dulu untuk melihat pilihan kurir dan ongkirnya.</p>}
                                                            {ongkir.memuat && <p className="gd-petunjuk">Menghitung ongkir...</p>}
                                                            {ongkir.galat && (
                                                                <div className="galat" role="alert">
                                                                    {ongkir.galat}{' '}
                                                                    <button type="button" className="gd-tautan-ulang" onClick={ongkir.muatUlang}>
                                                                        Coba lagi
                                                                    </button>
                                                                </div>
                                                            )}
                                                            {ongkir.pesanDarurat && !ongkir.memuat && (
                                                                <div className="gd-pesan galat" role="status">
                                                                    {ongkir.pesanDarurat}{' '}
                                                                    <button type="button" className="gd-tautan-ulang" onClick={ongkir.muatUlang}>
                                                                        Hitung ulang
                                                                    </button>
                                                                </div>
                                                            )}
                                                            {ongkir.siap && !ongkir.memuat && !ongkir.galat && ongkir.opsi.length === 0 && (
                                                                <p className="gd-petunjuk">Belum ada layanan kurir ke alamat ini. Hubungi kami lewat WhatsApp.</p>
                                                            )}
                                                            {ongkir.opsi.map((item) => (
                                                                <label key={item.kode} className={`gd-opsi-kirim${form.data.layanan === item.kode ? ' aktif' : ''}`}>
                                                                    <input
                                                                        type="radio"
                                                                        name="layanan"
                                                                        value={item.kode}
                                                                        checked={form.data.layanan === item.kode}
                                                                        onChange={() => form.setData('layanan', item.kode)}
                                                                    />
                                                                    <span className="nama">
                                                                        <strong>{item.label}</strong>
                                                                        {item.duration && <small>Estimasi {item.duration}</small>}
                                                                    </span>
                                                                    <span className="harga">{rupiah(item.price)}</span>
                                                                </label>
                                                            ))}
                                                            {layananDipilih && layananDipilih.paket.length === 0 && (
                                                                <div className="gd-paket-kirim">
                                                                    <p>
                                                                        Ongkir tetap sementara karena tarif kurir belum bisa dihitung. Kurir dipilih toko saat mengirim pesananmu.
                                                                    </p>
                                                                </div>
                                                            )}
                                                            {layananDipilih && layananDipilih.paket.length > 0 && (
                                                                <div className="gd-paket-kirim">
                                                                    <p>
                                                                        {layananDipilih.paket.length > 1
                                                                            ? `Pesanan dikirim dalam ${layananDipilih.paket.length} paket dari lokasi berbeda, jadi bisa tiba di hari yang berbeda.`
                                                                            : 'Barang dikirim dari cabang supplier yang memiliki stok.'}
                                                                    </p>
                                                                    <ul>
                                                                        {layananDipilih.paket.map((paket, indeks) => (
                                                                            <li key={`${paket.kodePos}-${indeks}`}>
                                                                                <span>
                                                                                    Paket {indeks + 1}, dari {paket.asal}: {paket.barang.join(', ')}
                                                                                </span>
                                                                                <strong>{rupiah(paket.price)}</strong>
                                                                            </li>
                                                                        ))}
                                                                    </ul>
                                                                    <small>Ongkir adalah perkiraan. Asal paket bisa berbeda tergantung stok supplier.</small>
                                                                </div>
                                                            )}
                                                            {form.errors.layanan && (
                                                                <div id="layanan-galat" className="galat" role="alert">
                                                                    {form.errors.layanan}
                                                                </div>
                                                            )}
                                                        </fieldset>
                                                    </div>
                                                )}
                                            </>
                                        ) : (
                                            <div className="col-12">
                                                <div className="gd-sandbox" role="alert">
                                                    Daftar wilayah belum tersedia, checkout belum bisa dipakai. Hubungi kami lewat WhatsApp.
                                                </div>
                                            </div>
                                        )}
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
                                                    <tr key={item.kunci} className="product-list">
                                                        <th>
                                                            {item.name}
                                                            {item.varian && ` (${item.varian})`}{' '}
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
                                                        <span className="amount">{ongkirTampil === null ? 'Pilih pengiriman' : rupiah(ongkirTampil)}</span>
                                                    </td>
                                                </tr>
                                                <tr className="total">
                                                    <th>Total</th>
                                                    <td>
                                                        <strong>
                                                            <span className="amount">{rupiah(total)}</span>
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
                                            <button
                                                type="submit"
                                                className="btn btn-primary d-block w-100"
                                                disabled={form.processing || !wilayahTersedia || (ongkirDinamis && !layananDipilih)}
                                            >
                                                {form.processing ? 'Membuat pembayaran...' : ongkirDinamis && !layananDipilih ? 'Pilih pengiriman dulu' : `Bayar ${rupiah(total)}`}
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
