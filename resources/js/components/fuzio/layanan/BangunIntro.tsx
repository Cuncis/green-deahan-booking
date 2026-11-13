import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { ChecklistIcon, StadiumIcon } from '../icons';
import { GD_CDN, waLink } from '../../../data/greendeahan';

/** /bangun-lapangan intro, same layout as AboutIntro (image left, text right). */
export default function BangunIntro() {
    return (
        <section className="sec-padding tips-sec">
            <div className="container">
                <div className="row align-items-center">
                    <div className="col-lg-6 col-sm-10 mx-sm-auto">
                        <div className="graphic-media pe-lg-5">
                            <img
                                className="img-fluid"
                                src={`${GD_CDN}/futsal-court1.webp`}
                                width={630}
                                height={630}
                                style={{ width: '100%', height: 'auto' }}
                                alt="Lapangan futsal hasil pembangunan Green Deahan Sport"
                            />
                        </div>
                    </div>
                    <div className="col-lg-6">
                        <div className="tips-txt ps-lg-5 mt-5 mt-lg-0">
                            <span className="lead text-primary">Jasa Pembuatan Lapangan Olahraga</span>
                            <TextAnimation>
                                <h2 className="sec-title mt-2">Bangun Lapangan, Kami Urus dari Awal sampai Siap Main</h2>
                            </TextAnimation>
                            <p>
                                Survey, desain, perizinan, konstruksi, hingga finishing, semua kami urus. Anda tidak perlu pusing koordinasi dengan banyak kontraktor. Lapangan futsal,
                                mini soccer, padel, dan badminton sudah kami bangun di 15+ kota di Indonesia sejak 2010.
                            </p>
                            <div className="d-sm-flex my-5">
                                <div className="tips-entry pe-lg-4">
                                    <span className="icon-sm bg-primary rounded-circle">
                                        <i style={{ color: '#fff' }}>
                                            <ChecklistIcon />
                                        </i>
                                    </span>
                                    <h3 className="text-uppercase my-3">Konsultasi Gratis</h3>
                                    <p>Konsultasi, survey lokasi, dan RAB estimasi 100% gratis tanpa syarat.</p>
                                </div>
                                <div className="tips-entry pe-lg-4">
                                    <span className="icon-sm bg-primary rounded-circle">
                                        <i style={{ color: '#fff' }}>
                                            <StadiumIcon />
                                        </i>
                                    </span>
                                    <h3 className="text-uppercase my-3">Bergaransi Resmi</h3>
                                    <p>Garansi tertulis untuk pekerjaan dan bahan. Pelunasan setelah lapangan selesai 100%.</p>
                                </div>
                            </div>
                            <div className="d-flex flex-wrap gap-3">
                                <a
                                    href={waLink('Halo Green Deahan, saya ingin konsultasi gratis untuk pembangunan lapangan.')}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="btn btn-primary btn-whatsapp"
                                >
                                    <WhatsAppIcon className="me-2" />
                                    Konsultasi Gratis
                                </a>
                                <a href="#kalkulator" className="btn btn-outline-primary">
                                    Hitung Balik Modal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
