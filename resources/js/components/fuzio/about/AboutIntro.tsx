import TextAnimation from '../elements/TextAnimation';
import { ChecklistIcon, StadiumIcon } from '../icons';
import { GD_CDN } from '../../../data/greendeahan';

/** Profil singkat Green Deahan ("Spesialis Lapangan Olahraga Sejak 2010"). */
export default function AboutIntro() {
    return (
        <section className="sec-padding tips-sec">
            <div className="container">
                <div className="row align-items-center">
                    <div className="col-lg-6 col-sm-10 mx-sm-auto">
                        <div className="graphic-media pe-lg-5">
                            <img
                                className="img-fluid"
                                src={`${GD_CDN}/hero-banner1.jpg`}
                                width={630}
                                height={630}
                                style={{ width: '100%', height: 'auto' }}
                                alt="Lapangan futsal indoor hasil pengerjaan Green Deahan Sport"
                            />
                        </div>
                    </div>
                    <div className="col-lg-6">
                        <div className="tips-txt ps-lg-5 mt-5 mt-lg-0">
                            <TextAnimation>
                                <h2 className="sec-title">Spesialis Lapangan Olahraga Sejak 2010</h2>
                            </TextAnimation>
                            <p>
                                Green Deahan Sport adalah jasa pembuatan lapangan olahraga profesional yang melayani seluruh Indonesia. Dengan pengalaman lebih dari 16 tahun, kami
                                spesialis konstruksi lapangan futsal, mini soccer, padel, dan badminton.
                            </p>
                            <div className="d-sm-flex my-5">
                                <div className="tips-entry pe-lg-4">
                                    <span className="icon-sm bg-primary rounded-circle">
                                        <i style={{ color: '#fff' }}>
                                            <ChecklistIcon />
                                        </i>
                                    </span>
                                    <h3 className="text-uppercase my-3">Layanan Turnkey</h3>
                                    <p>Konsultasi, desain, perizinan, konstruksi, hingga lapangan siap beroperasi.</p>
                                </div>
                                <div className="tips-entry pe-lg-4">
                                    <span className="icon-sm bg-primary rounded-circle">
                                        <i style={{ color: '#fff' }}>
                                            <StadiumIcon />
                                        </i>
                                    </span>
                                    <h3 className="text-uppercase my-3">100+ Proyek Selesai</h3>
                                    <p>Dipercaya pemilik bisnis lapangan di 15+ kota di Indonesia.</p>
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
