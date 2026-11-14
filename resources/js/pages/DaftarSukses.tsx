import '../../fuzio/css/fuzio.css';
import Footer from '../components/fuzio/Footer';
import Header from '../components/fuzio/Header';
import PageHeader from '../components/fuzio/PageHeader';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import { AlertIcon, CheckCircleIcon, ClockIcon } from '../components/fuzio/icons';
import { GD_CDN, waLink } from '../data/greendeahan';

interface Pendaftaran {
    status: 'aktif' | 'menunggu' | 'gagal_bayar';
    namaBisnis: string;
    domain: string;
    email: string;
    customDomainDiminta: string | null;
    pesan: string | null;
}

/**
 * /daftar/sukses (back from Mayar) and the fallback after /daftar when the
 * payment link could not be created. The status comes from the tenant
 * record, since Mayar's webhook may arrive after the browser returns.
 */
export default function DaftarSukses({ pendaftaran }: { pendaftaran: Pendaftaran }) {
    const { status, namaBisnis, domain, email, customDomainDiminta, pesan } = pendaftaran;

    const isi = {
        aktif: {
            ikon: <CheckCircleIcon />,
            judul: 'Pembayaran Diterima',
            teks: `"${namaBisnis}" di ${domain} sudah aktif. Cek email ${email} untuk link pembuatan akun pemilik.`,
        },
        menunggu: {
            ikon: <ClockIcon />,
            judul: 'Menunggu Konfirmasi Pembayaran',
            teks: `Terima kasih. Setelah pembayaran untuk "${namaBisnis}" kami terima, biasanya dalam beberapa menit, ${domain} aktif otomatis dan link pembuatan akun pemilik dikirim ke ${email}.`,
        },
        gagal_bayar: {
            ikon: <AlertIcon />,
            judul: 'Pendaftaran Tersimpan',
            teks: pesan ?? '',
        },
    }[status];

    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader
                title="Status Pendaftaran"
                imgSrc={`${GD_CDN}/badminton1-img.png`}
                crumbs={[{ label: 'Sistem Booking', href: '/sistem-booking' }, { label: 'Status Pendaftaran' }]}
            />

            <section className="account-sec sec-padding gd-daftar">
                <div className="container">
                    <div className="row">
                        <div className="col-lg-8 offset-lg-2">
                            <div className={`account-form gd-kartu-daftar gd-status-daftar ${status} rounded-4 text-center`}>
                                <div className="gd-status-ikon">{isi.ikon}</div>
                                <h2 className="h3 mb-3">{isi.judul}</h2>
                                <p>{isi.teks}</p>

                                {customDomainDiminta && (
                                    <p className="gd-catatan-paket text-start mt-4">
                                        Anda juga meminta domain <strong>{customDomainDiminta}</strong>. Tim kami menghubungi Anda untuk verifikasi kepemilikan domain sebelum
                                        diaktifkan.
                                    </p>
                                )}

                                <div className="d-flex flex-wrap justify-content-center gap-2 mt-4">
                                    <a href="/" className="btn btn-primary">
                                        Kembali ke Beranda
                                    </a>
                                    <a
                                        href={waLink(`Halo, saya baru mendaftar sistem booking untuk "${namaBisnis}" (${domain}).`)}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="btn btn-outline-primary"
                                    >
                                        Chat WhatsApp
                                    </a>
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
