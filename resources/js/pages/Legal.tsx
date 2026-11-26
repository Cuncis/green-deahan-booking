import '../../fuzio/css/fuzio.css';
import Header from '../components/fuzio/Header';
import Footer from '../components/fuzio/Footer';
import PageHeader from '../components/fuzio/PageHeader';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import { GD_CDN } from '../data/greendeahan';
import { TANGGAL_LEGAL, dokumenLegal } from '../data/legal';

/**
 * Halaman legal (Kebijakan Privasi, Syarat & Ketentuan, Pengiriman &
 * Pengembalian, Kebijakan Cookie). Isi di data/legal.ts, slug dari rute.
 */
export default function Legal({ slug }: { slug: string }) {
    const dokumen = dokumenLegal.find((item) => item.slug === slug) ?? dokumenLegal[0];

    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title={dokumen.judul} imgSrc={`${GD_CDN}/futsal1-outdoor.png`} crumbs={[{ label: 'Informasi' }, { label: dokumen.judul }]} />
            <main className="sec-padding gd-legal">
                <div className="container">
                    <div className="row">
                        <nav className="col-lg-3 mb-4" aria-label="Dokumen legal">
                            <ul className="gd-legal-nav">
                                {dokumenLegal.map((item) => (
                                    <li key={item.slug}>
                                        <a href={`/${item.slug}`} aria-current={item.slug === dokumen.slug ? 'page' : undefined}>
                                            {item.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                        <article className="col-lg-9 gd-legal-isi">
                            <p className="gd-legal-tanggal">Terakhir diperbarui: {TANGGAL_LEGAL}</p>
                            <p className="gd-legal-pembuka">{dokumen.pembuka}</p>
                            {dokumen.bagian.map((bagian, nomor) => (
                                <section key={bagian.judul}>
                                    <h2>
                                        {nomor + 1}. {bagian.judul}
                                    </h2>
                                    {bagian.isi.map((isi, indeks) =>
                                        typeof isi === 'string' ? (
                                            <p key={indeks}>{isi}</p>
                                        ) : (
                                            <ul key={indeks}>
                                                {isi.daftar.map((butir) => (
                                                    <li key={butir}>{butir}</li>
                                                ))}
                                            </ul>
                                        ),
                                    )}
                                </section>
                            ))}
                        </article>
                    </div>
                </div>
            </main>
            <Footer />
            <ScrollToTop />
        </div>
    );
}
