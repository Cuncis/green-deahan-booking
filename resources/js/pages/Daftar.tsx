import '../../fuzio/css/fuzio.css';
import { useForm } from '@inertiajs/react';
import type { ChangeEvent, FormEvent, InputHTMLAttributes, ReactNode } from 'react';
import Footer from '../components/fuzio/Footer';
import Header from '../components/fuzio/Header';
import PageHeader from '../components/fuzio/PageHeader';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import TextAnimation from '../components/fuzio/elements/TextAnimation';
import type { PaketSistem } from '../components/fuzio/layanan/PaketSistemSec';
import { rupiah } from '../components/fuzio/toko/types';
import { GD_CDN, waLink } from '../data/greendeahan';

type Kolom = 'nama_bisnis' | 'subdomain' | 'custom_domain_diminta' | 'nama_pic' | 'email_pic' | 'whatsapp_pic';
type DataForm = Record<Kolom | 'paket', string>;

function Isian({
    id,
    label,
    galat,
    wajib = true,
    petunjuk,
    ...props
}: { id: Kolom; label: string; galat?: string; wajib?: boolean; petunjuk?: ReactNode } & InputHTMLAttributes<HTMLInputElement>) {
    const keterangan = [petunjuk ? `${id}-petunjuk` : null, galat ? `${id}-galat` : null].filter(Boolean).join(' ') || undefined;

    return (
        <>
            <label htmlFor={id}>
                {label}
                {wajib && '*'}
            </label>
            <input id={id} name={id} required={wajib} className={galat ? 'is-invalid' : ''} aria-invalid={Boolean(galat)} aria-describedby={keterangan} {...props} />
            {petunjuk && (
                <p id={`${id}-petunjuk`} className="gd-petunjuk">
                    {petunjuk}
                </p>
            )}
            {galat && (
                <div id={`${id}-galat`} className="galat" role="alert">
                    {galat}
                </div>
            )}
        </>
    );
}

/** 1500000 -> "Rp1,5 juta" */
const juta = (nilai: number) => `Rp${(nilai / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} juta`;

/**
 * /daftar: sign-up for the booking system, a port of Fuzio's register page
 * (AccountSec). The total shown is an estimate; the server recalculates the
 * invoice (Tenant::hitungHargaLangganan) and sends the visitor to Mayar.
 */
export default function Daftar({ paket, paketTerpilih, hargaAddonDomain }: { paket: PaketSistem[]; paketTerpilih: string; hargaAddonDomain: number }) {
    const form = useForm<DataForm>({ paket: paketTerpilih, nama_bisnis: '', subdomain: '', custom_domain_diminta: '', nama_pic: '', email_pic: '', whatsapp_pic: '' });
    const paketAktif = paket.find((item) => item.kunci === form.data.paket) ?? paket[0];
    const bisaCustomDomain = form.data.paket !== 'basic';
    const pakaiCustomDomain = bisaCustomDomain && form.data.custom_domain_diminta.trim() !== '';
    const estimasi = paketAktif.harga + (pakaiCustomDomain ? hargaAddonDomain : 0);

    const pilihPaket = (kunci: string) => {
        // Basic has no custom domain: clear it so a hidden value is never sent.
        form.setData((data) => ({ ...data, paket: kunci, custom_domain_diminta: kunci === 'basic' ? '' : data.custom_domain_diminta }));
    };

    const isian = (id: Kolom, ubah: (nilai: string) => string = (nilai) => nilai) => ({
        id,
        value: form.data[id],
        galat: form.errors[id],
        onChange: (event: ChangeEvent<HTMLInputElement>) => form.setData(id, ubah(event.target.value)),
    });

    const kirim = (event: FormEvent) => {
        event.preventDefault();
        form.post('/daftar', { preserveScroll: true });
    };

    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader
                title="Daftar Sistem Booking"
                imgSrc={`${GD_CDN}/badminton1-img.png`}
                crumbs={[{ label: 'Layanan' }, { label: 'Sistem Booking', href: '/sistem-booking' }, { label: 'Daftar' }]}
            />

            <section className="account-sec sec-padding gd-daftar">
                <div className="container">
                    <div className="row">
                        <div className="col-xl-8 offset-xl-2 col-lg-10 offset-lg-1">
                            <div className="text-center mb-5">
                                <TextAnimation>
                                    <h2 className="sec-title line primary">Daftarkan Lapangan Anda</h2>
                                </TextAnimation>
                                <p>Isi data di bawah, lalu bayar lewat QRIS, virtual account, atau e-wallet. Website booking Anda aktif otomatis setelah pembayaran diterima.</p>
                            </div>

                            <div className="account-form gd-kartu-daftar rounded-4">
                                <form className="gd-form-daftar" onSubmit={kirim} noValidate>
                                    <fieldset>
                                        <legend className="gd-judul-form">Pilih Paket</legend>
                                        <div className="row g-3" role="radiogroup">
                                            {paket.map((item) => {
                                                const dipilih = item.kunci === form.data.paket;

                                                return (
                                                    <div key={item.kunci} className="col-md-4">
                                                        <label className={`gd-pilih-paket${dipilih ? ' dipilih' : ''}`}>
                                                            <input type="radio" name="paket" value={item.kunci} checked={dipilih} onChange={() => pilihPaket(item.kunci)} />
                                                            <span className="gd-pilih-paket-nama">
                                                                {item.nama}
                                                                {item.unggulan && <span className="gd-pilih-paket-label">Populer</span>}
                                                            </span>
                                                            <span className="gd-pilih-paket-harga">
                                                                {juta(item.harga)}
                                                                <small>/tahun</small>
                                                            </span>
                                                            <span className="gd-pilih-paket-lapangan">
                                                                {item.batasLapangan === null ? 'Lapangan tanpa batas' : `Sampai ${item.batasLapangan} lapangan`}
                                                            </span>
                                                        </label>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                        {form.errors.paket && (
                                            <div className="galat" role="alert">
                                                {form.errors.paket}
                                            </div>
                                        )}
                                    </fieldset>

                                    <fieldset>
                                        <legend className="gd-judul-form">Data Bisnis</legend>
                                        <div className="row g-3">
                                            <div className="col-md-6">
                                                <Isian {...isian('nama_bisnis')} label="Nama bisnis" autoComplete="organization" placeholder="Contoh: Futsal Merdeka" />
                                            </div>
                                            <div className="col-md-6">
                                                <label htmlFor="subdomain">Alamat website*</label>
                                                <div className={`gd-subdomain${form.errors.subdomain ? ' is-invalid' : ''}`}>
                                                    <input
                                                        id="subdomain"
                                                        name="subdomain"
                                                        required
                                                        value={form.data.subdomain}
                                                        onChange={(event) => form.setData('subdomain', event.target.value.toLowerCase().replace(/\s+/g, '-'))}
                                                        placeholder="namabisnis"
                                                        autoCapitalize="none"
                                                        autoCorrect="off"
                                                        spellCheck={false}
                                                        aria-invalid={Boolean(form.errors.subdomain)}
                                                        aria-describedby={form.errors.subdomain ? 'subdomain-petunjuk subdomain-galat' : 'subdomain-petunjuk'}
                                                    />
                                                    <span>.greendeahan.com</span>
                                                </div>
                                                <p id="subdomain-petunjuk" className="gd-petunjuk">
                                                    Huruf kecil, angka, dan tanda hubung, minimal 3 karakter.
                                                </p>
                                                {form.errors.subdomain && (
                                                    <div id="subdomain-galat" className="galat" role="alert">
                                                        {form.errors.subdomain}
                                                    </div>
                                                )}
                                            </div>
                                            <div className="col-12">
                                                {bisaCustomDomain ? (
                                                    <Isian
                                                        {...isian('custom_domain_diminta', (nilai) => nilai.trim().toLowerCase())}
                                                        label={`Domain sendiri (opsional, +${rupiah(hargaAddonDomain)}/tahun)`}
                                                        wajib={false}
                                                        placeholder="Contoh: namabisnis.com"
                                                        autoCapitalize="none"
                                                        spellCheck={false}
                                                        petunjuk="Kosongkan kalau cukup memakai alamat .greendeahan.com. Kalau diisi, tim kami menghubungi Anda untuk verifikasi domain sebelum diaktifkan."
                                                    />
                                                ) : (
                                                    <p className="gd-catatan-paket">Domain sendiri tersedia di paket Pro dan Premium.</p>
                                                )}
                                            </div>
                                        </div>
                                    </fieldset>

                                    <fieldset>
                                        <legend className="gd-judul-form">Penanggung Jawab</legend>
                                        <div className="row g-3">
                                            <div className="col-md-6">
                                                <Isian {...isian('nama_pic')} label="Nama lengkap" autoComplete="name" placeholder="Contoh: Budi Santoso" />
                                            </div>
                                            <div className="col-md-6">
                                                <Isian {...isian('whatsapp_pic')} label="Nomor WhatsApp" type="tel" inputMode="tel" autoComplete="tel" placeholder="08xxxxxxxxxx" />
                                            </div>
                                            <div className="col-12">
                                                <Isian
                                                    {...isian('email_pic')}
                                                    label="Email"
                                                    type="email"
                                                    inputMode="email"
                                                    autoComplete="email"
                                                    placeholder="nama@email.com"
                                                    petunjuk="Link untuk membuat akun pemilik dikirim ke email ini."
                                                />
                                            </div>
                                        </div>
                                    </fieldset>

                                    <div className="gd-estimasi" aria-live="polite">
                                        <div>
                                            <span>Paket {paketAktif.nama}</span>
                                            <span>{rupiah(paketAktif.harga)}</span>
                                        </div>
                                        {pakaiCustomDomain && (
                                            <div>
                                                <span>Domain sendiri</span>
                                                <span>{rupiah(hargaAddonDomain)}</span>
                                            </div>
                                        )}
                                        <div className="total">
                                            <span>Estimasi total</span>
                                            <span>
                                                {rupiah(estimasi)}
                                                <small>/tahun</small>
                                            </span>
                                        </div>
                                    </div>

                                    <div className="gd-pembayaran mt-4">
                                        <strong>Pembayaran online lewat Mayar</strong>
                                        <p className="mb-0">
                                            Setelah menekan tombol di bawah, Anda diarahkan ke halaman pembayaran. Sudah termasuk setup awal dan support 1 tahun.
                                        </p>
                                    </div>

                                    <button type="submit" className="btn btn-primary w-100 mt-4" disabled={form.processing}>
                                        {form.processing ? 'Memproses pendaftaran...' : 'Daftar dan Bayar'}
                                    </button>
                                </form>

                                <div className="login-message text-center mt-4">
                                    <p>
                                        Ingin membandingkan fitur dulu?{' '}
                                        <a href="/sistem-booking#paket" className="gd-tautan">
                                            Lihat detail paket
                                        </a>
                                    </p>
                                    <p>
                                        Ada pertanyaan?{' '}
                                        <a href={waLink('Halo, saya ingin tanya soal sistem booking lapangan.')} target="_blank" rel="noopener noreferrer" className="gd-tautan">
                                            Chat WhatsApp
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <Footer />
            <ScrollToTop />
        </div>
    );
}
