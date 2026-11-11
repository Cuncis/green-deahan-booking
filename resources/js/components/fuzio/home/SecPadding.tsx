import FadeIn from '../elements/FadeIn';
import RevealAnimation from '../elements/RevealAnimation';
import TextAnimation from '../elements/TextAnimation';
import { GD_CDN } from '../../../data/greendeahan';

/** Shield with a check, same icon as resources/views/icons/shield.blade.php. */
function ShieldCheckIcon() {
    return (
        <svg viewBox="0 0 24 24" width={34} height={34} fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z" />
            <path d="M9 12l2 2 4-4" />
        </svg>
    );
}

/** Itemized receipt (outline from Lucide "receipt", ISC license), for a detailed price breakdown (RAB). */
function ReceiptIcon() {
    return (
        <svg viewBox="0 0 24 24" width={30} height={30} fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z" />
            <path d="M8 8h8" />
            <path d="M8 12h8" />
            <path d="M8 16h5" />
        </svg>
    );
}

export default function SecPadding() {
    return (
        <section className="sec-padding tips-sec">
            <div className="container">
                <div className="row align-items-center">
                    <div className="col-lg-6 col-sm-10 mx-sm-auto">
                        <RevealAnimation className="graphic-media pe-5">
                            <div>
                                <img className="img-fluid" src={`${GD_CDN}/profile-img1.jpg`} width={573} height={573} alt="Kantor dan Workshop Green Deahan Sport" />
                            </div>
                        </RevealAnimation>
                    </div>
                    <div className="col-lg-6">
                        <div className="tips-txt ps-lg-5 mt-5 mt-lg-0">
                            <TextAnimation>
                                <h2 className="sec-title">Dari Nol Sampai Jadi, Anda Santai Saja</h2>
                            </TextAnimation>
                            <FadeIn delay={100}>
                                <p>
                                    Survey, desain, perizinan, konstruksi, hingga serah terima, semua kami urus. Lebih dari 100 proyek di 15+ kota sejak 2010. Anda cukup pantau progresnya.
                                </p>
                            </FadeIn>
                            <div className="d-sm-flex my-5">
                                <div className="tips-entry pe-lg-4">
                                    <FadeIn delay={100}>
                                        <span className="icon-sm bg-primary rounded-circle mb-2">
                                            <i style={{ color: '#fff' }}>
                                                <ShieldCheckIcon />
                                            </i>
                                        </span>
                                        <h3 className="text-uppercase my-3">Garansi Resmi</h3>
                                        <p>Konstruksi &amp; material bergaransi. Pelunasan setelah lapangan 100% selesai.</p>
                                    </FadeIn>
                                </div>
                                <div className="tips-entry pe-lg-4">
                                    <FadeIn delay={300}>
                                        <span className="icon-sm bg-primary rounded-circle mb-2">
                                            <i style={{ color: '#fff' }}>
                                                <ReceiptIcon />
                                            </i>
                                        </span>
                                        <h3 className="text-uppercase my-3">Harga Transparan</h3>
                                        <p>RAB detail sebelum mulai, material bisa disesuaikan dengan budget Anda.</p>
                                    </FadeIn>
                                </div>
                            </div>
                            <a href="/galeri" className="btn btn-dark text-primary">
                                Lihat Portofolio
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
