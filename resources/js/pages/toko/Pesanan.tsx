import '../../../fuzio/css/fuzio.css';
import { usePoll } from '@inertiajs/react';
import { useState } from 'react';
import Footer from '../../components/fuzio/Footer';
import Header from '../../components/fuzio/Header';
import PageHeader from '../../components/fuzio/PageHeader';
import ScrollToTop from '../../components/fuzio/elements/ScrollToTop';
import WhatsAppIcon from '../../components/fuzio/elements/WhatsAppIcon';
import GelembungUjiBayar from '../../components/fuzio/toko/GelembungUjiBayar';
import { rupiah } from '../../components/fuzio/toko/types';
import { GD_CDN, waLink } from '../../data/greendeahan';

interface Pesanan {
    kode: string;
    status: string;
    statusLabel: string;
    nama: string;
    alamat: string;
    items: { nama: string; varian: string | null; qty: number; harga: number; subtotal: number }[];
    subtotal: number;
    diskon: number;
    kodePromo: string | null;
    ongkir: number;
    layanan: string | null;
    total: number;
    linkBayar: string | null;
    alasanBatal: string | null;
    statusRefund: 'diproses' | 'selesai' | null;
    pengiriman: { kurir: string; resi: string; dikirimDari: string | null }[];
    dibuat: string;
    dibayar: string | null;
}

interface LangkahTimeline {
    kunci: string;
    judul: string;
    keterangan: string;
    waktu: string | null;
    keadaan: 'selesai' | 'sekarang' | 'nanti' | 'batal';
}

interface Props {
    pesanan: Pesanan;
    timeline: LangkahTimeline[];
    bypassBayarUrl?: string | null;
}

const STATUS_BATAL = ['cancelled', 'expired'];

const IkonCentang = () => (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M20 6 9 17l-5-5" />
    </svg>
);

const IkonSilang = () => (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M18 6 6 18M6 6l12 12" />
    </svg>
);

/** Tracking number with a copy button, so customers can paste it on the courier's site. */
function Resi({ paket, nomor }: { paket: Pesanan['pengiriman'][number]; nomor: number | null }) {
    const [tersalin, setTersalin] = useState(false);

    const salin = async () => {
        try {
            await navigator.clipboard.writeText(paket.resi);
            setTersalin(true);
            window.setTimeout(() => setTersalin(false), 2000);
        } catch {
            // Clipboard blocked: the number is still visible to copy by hand.
        }
    };

    return (
        <div className="gd-resi">
            <div className="gd-resi-info">
                <span className="gd-resi-kurir">
                    {nomor !== null && `Paket ${nomor}: `}
                    {paket.kurir}
                    {paket.dikirimDari && <span className="gd-resi-asal"> dari {paket.dikirimDari}</span>}
                </span>
                <span className="gd-resi-nomor">{paket.resi}</span>
            </div>
            <button type="button" className="gd-resi-salin" onClick={salin} aria-label={`Salin nomor resi ${paket.resi}`}>
                {tersalin ? 'Tersalin' : 'Salin'}
            </button>
        </div>
    );
}

/** /pesanan/{token}: order status for the customer. Refreshes itself while waiting for payment. */
export default function PesananToko({ pesanan, timeline, bypassBayarUrl }: Props) {
    const menungguBayar = pesanan.status === 'pending_payment';
    const batal = STATUS_BATAL.includes(pesanan.status);

    // The payment webhook updates the order; check again every 5 seconds while unpaid.
    usePoll(5000, { only: ['pesanan', 'timeline'] }, { autoStart: menungguBayar });

    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Status Pesanan" imgSrc={`${GD_CDN}/padel1-img.png`} crumbs={[{ label: 'Toko', href: '/toko' }, { label: 'Pesanan' }]} />

            <section className="gd-toko">
                <div className="container">
                    <div className="row g-4 align-items-start">
                        <div className="col-lg-7">
                            <div className="gd-kartu">
                                <div className="gd-status-kepala">
                                    <div>
                                        <span className="gd-status-kode-label">Kode pesanan</span>
                                        <h2 className="gd-status-kode">{pesanan.kode}</h2>
                                    </div>
                                    <span className={`gd-status-badge${batal ? ' batal' : ''}`}>{pesanan.statusLabel}</span>
                                </div>

                                <ol className="gd-timeline" aria-label="Perjalanan pesanan">
                                    {timeline.map((langkah) => (
                                        <li key={langkah.kunci} className={`gd-timeline-langkah ${langkah.keadaan}`} aria-current={langkah.keadaan === 'sekarang' ? 'step' : undefined}>
                                            <span className="gd-timeline-titik">
                                                {langkah.keadaan === 'selesai' && <IkonCentang />}
                                                {langkah.keadaan === 'batal' && <IkonSilang />}
                                            </span>
                                            <div className="gd-timeline-isi">
                                                <div className="gd-timeline-judul">
                                                    <h3>{langkah.judul}</h3>
                                                    {langkah.keadaan === 'sekarang' && <span className="gd-timeline-sekarang">Sekarang</span>}
                                                </div>
                                                {langkah.waktu && <time className="gd-timeline-waktu">{langkah.waktu}</time>}
                                                {langkah.keterangan && <p aria-live={langkah.kunci === 'dibayar' ? 'polite' : undefined}>{langkah.keterangan}</p>}
                                                {langkah.kunci === 'dibayar' && langkah.keadaan === 'sekarang' && pesanan.linkBayar && (
                                                    <a href={pesanan.linkBayar} className="btn btn-primary btn-cta mt-3">
                                                        Bayar Sekarang {rupiah(pesanan.total)}
                                                    </a>
                                                )}
                                                {langkah.kunci === 'dikirim' && langkah.keadaan !== 'nanti' && pesanan.pengiriman.length > 0 && (
                                                    <div className="gd-resi-daftar">
                                                        {pesanan.pengiriman.length > 1 && (
                                                            <p className="gd-resi-catatan">
                                                                Dikirim dalam {pesanan.pengiriman.length} paket dari lokasi berbeda, jadi bisa tiba di hari yang berbeda.
                                                            </p>
                                                        )}
                                                        {pesanan.pengiriman.map((paket, indeks) => (
                                                            <Resi key={`${paket.resi}-${indeks}`} paket={paket} nomor={pesanan.pengiriman.length > 1 ? indeks + 1 : null} />
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        </li>
                                    ))}
                                </ol>

                                {batal && pesanan.statusRefund && (
                                    <div className="gd-pesan galat mt-3 mb-0">
                                        {pesanan.statusRefund === 'diproses' ? (
                                            <>
                                                Pembayaranmu sebesar {rupiah(pesanan.total)} sedang kami proses pengembaliannya. Hubungi kami lewat WhatsApp kalau dana belum
                                                diterima dalam beberapa hari.
                                            </>
                                        ) : (
                                            <>Dana {rupiah(pesanan.total)} sudah kami kembalikan ke metode pembayaranmu.</>
                                        )}
                                    </div>
                                )}
                            </div>

                            <div className="gd-kartu mt-4">
                                <h2>Alamat Pengiriman</h2>
                                <p className="mb-1">
                                    <strong>{pesanan.nama}</strong>
                                </p>
                                <p className="mb-0">{pesanan.alamat}</p>
                                {pesanan.layanan && <p className="gd-status-layanan">Kurir: {pesanan.layanan}</p>}
                            </div>
                        </div>
                        <div className="col-lg-5">
                            <aside className="gd-kartu">
                                <h2>Rincian</h2>
                                {pesanan.items.map((item) => (
                                    <div key={item.nama} className="gd-ringkasan-baris">
                                        <span>
                                            {item.qty} x {item.nama}
                                            {item.varian && ` (${item.varian})`}
                                        </span>
                                        <span>{rupiah(item.subtotal)}</span>
                                    </div>
                                ))}
                                <div className="gd-ringkasan-baris" style={{ borderTop: '1px solid #e7e5e4', marginTop: 8, paddingTop: 12 }}>
                                    <span>Subtotal</span>
                                    <span>{rupiah(pesanan.subtotal)}</span>
                                </div>
                                {pesanan.diskon > 0 && (
                                    <div className="gd-ringkasan-baris" style={{ color: '#006400' }}>
                                        <span>Diskon{pesanan.kodePromo ? ` (${pesanan.kodePromo})` : ''}</span>
                                        <span>-{rupiah(pesanan.diskon)}</span>
                                    </div>
                                )}
                                <div className="gd-ringkasan-baris">
                                    <span>Ongkir{pesanan.layanan ? ` (${pesanan.layanan})` : ''}</span>
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

                            <aside className="gd-kartu mt-4">
                                <h2>Butuh Bantuan?</h2>
                                <p>Ada pertanyaan soal pesanan ini? Chat tim kami dan sebutkan kode pesananmu.</p>
                                <a
                                    href={waLink(`Halo Green Deahan, saya mau tanya soal pesanan ${pesanan.kode}.`)}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="btn btn-primary btn-whatsapp w-100"
                                >
                                    <WhatsAppIcon className="me-2" />
                                    Chat WhatsApp
                                </a>
                            </aside>
                        </div>
                    </div>
                </div>
            </section>

            {bypassBayarUrl && <GelembungUjiBayar url={bypassBayarUrl} />}
            <Footer />
            <ScrollToTop />
        </div>
    );
}
