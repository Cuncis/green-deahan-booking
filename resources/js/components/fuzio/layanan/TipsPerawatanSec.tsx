import TextAnimation from '../elements/TextAnimation';
import { artikelPerawatan, tipsPerawatan } from '../../../data/perawatan';

/** Day-to-day care tips (from the "Tips Perawatan" blog articles), with links to the full articles. */
export default function TipsPerawatanSec() {
    return (
        <section id="tips-perawatan" className="feature-sec bg-light sec-padding" style={{ scrollMarginTop: 90 }}>
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-3">Tips Merawat Lapangan Sendiri</h2>
                </TextAnimation>
                <p className="text-center mx-auto mb-5" style={{ maxWidth: 640 }}>
                    Perawatan harian sederhana membuat lapangan lebih awet di antara jadwal servis.
                </p>
                <div className="row g-4">
                    {tipsPerawatan.map((tip, index) => (
                        <div key={tip.judul} className="col-lg-4 col-md-6">
                            <div className="gd-tip h-100">
                                <span className="nomor" aria-hidden="true">
                                    {index + 1}
                                </span>
                                <div>
                                    <h3>{tip.judul}</h3>
                                    <p className="mb-0">{tip.teks}</p>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
                <div className="gd-artikel-perawatan mt-5">
                    <span className="fw-bold">Baca selengkapnya:</span>
                    {artikelPerawatan.map((artikel) => (
                        <a key={artikel.href} href={artikel.href}>
                            {artikel.judul} <i className="feather-icon icon-arrow-right" aria-hidden="true"></i>
                        </a>
                    ))}
                </div>
            </div>
        </section>
    );
}
