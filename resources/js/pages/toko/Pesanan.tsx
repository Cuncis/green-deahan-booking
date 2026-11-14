import '../../../fuzio/css/fuzio.css';
import { usePoll } from '@inertiajs/react';
import Footer from '../../components/fuzio/Footer';
import Header from '../../components/fuzio/Header';
import PageHeader from '../../components/fuzio/PageHeader';
import ScrollToTop from '../../components/fuzio/elements/ScrollToTop';
import { rupiah } from '../../components/fuzio/toko/types';
import { GD_CDN } from '../../data/greendeahan';

interface Pesanan {
    kode: string;
    status: string;
    statusLabel: string;
    nama: string;
    alamat: string;
    items: { nama: string; qty: number; harga: number; subtotal: number }[];
    subtotal: number;
    ongkir: number;
    total: number;
    linkBayar: string | null;
    kurir: string | null;
    resi: string | null;
    dibuat: string;
    dibayar: string | null;
}

interface Props {
    pesanan: Pesanan;
    langkah: { nilai: string; label: string }[];
}

const STATUS_BATAL = ['cancelled', 'expired'];

/** /pesanan/{code}: order status for the customer. Refreshes itself while waiting for payment. */
export default function PesananToko({ pesanan, langkah }: Props) {
    const menungguBayar = pesanan.status === 'pending_payment';
    const batal = STATUS_BATAL.includes(pesanan.status);
    const posisi = langkah.findIndex((item) => item.nilai === pesanan.status);

    // The payment webhook updates the order; check again every 5 seconds while unpaid.
    usePoll(5000, { only: ['pesanan'] }, { autoStart: menungguBayar });

    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Status Pesanan" imgSrc={`${GD_CDN}/padel1-img.png`} crumbs={[{ label: 'Toko', href: '/toko' }, { label: 'Pesanan' }]} />

            <section className="gd-toko">
                <div className="container">
                    <div className="row g-4 align-items-start">
                        <div className="col-lg-7">
                            <div className="gd-kartu">
                                <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <h2 className="mb-0">Pesanan {pesanan.kode}</h2>
                                    <span className={`gd-status-badge${batal ? ' batal' : ''}`}>{pesanan.statusLabel}</span>
                                </div>

                                {!batal && (
                                    <ol className="gd-langkah" aria-label="Tahap pesanan">
                                        {langkah.map((item, index) => (
                                            <li
                                                key={item.nilai}
                                                className={index < posisi ? 'selesai' : index === posisi ? (index === langkah.length - 1 ? 'selesai' : 'sekarang') : ''}
                                                aria-current={index === posisi ? 'step' : undefined}
                                            >
                                                <span className="titik">
                                                    {index < posisi || (index === posisi && index === langkah.length - 1) ? (
                                                        <svg
                                                            width="16"
                                                            height="16"
                                                            viewBox="0 0 24 24"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            strokeWidth="3"
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            aria-hidden="true"
                                                        >
                                                            <path d="M20 6 9 17l-5-5" />
                                                        </svg>
                                                    ) : (
                                                        index + 1
                                                    )}
                                                </span>
                                                {item.label}
                                            </li>
                                        ))}
                                    </ol>
                                )}

                                {menungguBayar && (
                                    <div className="gd-pesan sukses" role="status" aria-live="polite">
                                        Kami menunggu pembayaran kamu. Halaman ini otomatis berubah begitu pembayaran diterima.
                                        {pesanan.linkBayar && (
                                            <div className="mt-3">
                                                <a href={pesanan.linkBayar} className="btn btn-primary">
                                                    Bayar Sekarang
                                                </a>
                                            </div>
                                        )}
                                    </div>
                                )}
                                {batal && <div className="gd-pesan galat">Pesanan ini {pesanan.statusLabel.toLowerCase()}. Silakan pesan ulang dari toko.</div>}
                                {pesanan.resi && (
                                    <div className="gd-pesan sukses">
                                        Dikirim lewat <strong>{pesanan.kurir}</strong>, nomor resi <strong>{pesanan.resi}</strong>.
                                    </div>
                                )}

                                <p className="mb-1">
                                    <strong>Dikirim ke:</strong> {pesanan.nama}
                                </p>
                                <p className="mb-1">{pesanan.alamat}</p>
                                <p className="mb-0" style={{ fontSize: 14, color: '#555' }}>
                                    Dipesan {pesanan.dibuat}
                                    {pesanan.dibayar && `, dibayar ${pesanan.dibayar}`}
                                </p>
                            </div>
                        </div>
                        <div className="col-lg-5">
                            <aside className="gd-kartu">
                                <h2>Rincian</h2>
                                {pesanan.items.map((item) => (
                                    <div key={item.nama} className="gd-ringkasan-baris">
                                        <span>
                                            {item.qty} x {item.nama}
                                        </span>
                                        <span>{rupiah(item.subtotal)}</span>
                                    </div>
                                ))}
                                <div className="gd-ringkasan-baris" style={{ borderTop: '1px solid #e7e5e4', marginTop: 8, paddingTop: 12 }}>
                                    <span>Subtotal</span>
                                    <span>{rupiah(pesanan.subtotal)}</span>
                                </div>
                                <div className="gd-ringkasan-baris">
                                    <span>Ongkir</span>
                                    <span>{rupiah(pesanan.ongkir)}</span>
                                </div>
                                <div className="gd-ringkasan-baris total">
                                    <span>Total</span>
                                    <span>{rupiah(pesanan.total)}</span>
                                </div>
                                <p className="mt-3 mb-0" style={{ fontSize: 14, color: '#555' }}>
                                    Simpan link halaman ini untuk mengecek status pesanan.
                                </p>
                            </aside>
                        </div>
                    </div>
                </div>
            </section>

            <Footer />
            <ScrollToTop />
        </div>
    );
}
