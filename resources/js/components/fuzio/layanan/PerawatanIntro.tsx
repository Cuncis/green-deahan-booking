import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { ChecklistIcon, StadiumIcon } from '../icons';
import { GD_CDN, waLink } from '../../../data/greendeahan';

/** /perawatan intro, same layout as AboutIntro. Maintenance is for courts Green Deahan built. */
export default function PerawatanIntro() {
    return (
        <section className="sec-padding tips-sec">
            <div className="container">
                <div className="row align-items-center">
                    <div className="col-lg-6 col-sm-10 mx-sm-auto">
                        <div className="graphic-media pe-lg-5">
                            <img
                                className="img-fluid"
                                src={`${GD_CDN}/interlock1.jpg`}
                                width={630}
                                height={630}
                                style={{ width: '100%', height: 'auto' }}
                                alt="Lantai interlock lapangan hasil pengerjaan Green Deahan Sport"
                            />
                        </div>
                    </div>
                    <div className="col-lg-6">
                        <div className="tips-txt ps-lg-5 mt-5 mt-lg-0">
                            <span className="lead text-primary">Khusus Klien Green Deahan</span>
                            <TextAnimation>
                                <h2 className="sec-title mt-2">Lapangan Tetap Prima, Pelanggan Tetap Betah</h2>
                            </TextAnimation>
                            <p>
                                Lapangan yang terawat lebih awet dan lebih nyaman dipakai. Untuk lapangan yang kami bangun, kami sediakan perawatan berkala, perbaikan kerusakan,
                                hingga renovasi, dimulai dengan pengecekan gratis.
                            </p>
                            <div className="d-sm-flex my-5">
                                <div className="tips-entry pe-lg-4">
                                    <span className="icon-sm bg-primary rounded-circle">
                                        <i style={{ color: '#fff' }}>
                                            <ChecklistIcon />
                                        </i>
                                    </span>
                                    <h3 className="text-uppercase my-3">Cek Gratis</h3>
                                    <p>Kondisi lapangan dicek tanpa biaya, estimasi biaya tertulis sebelum mulai.</p>
                                </div>
                                <div className="tips-entry pe-lg-4">
                                    <span className="icon-sm bg-primary rounded-circle">
                                        <i style={{ color: '#fff' }}>
                                            <StadiumIcon />
                                        </i>
                                    </span>
                                    <h3 className="text-uppercase my-3">Garansi Resmi</h3>
                                    <p>Kerusakan yang masuk garansi kami perbaiki tanpa biaya tambahan.</p>
                                </div>
                            </div>
                            <div className="d-flex flex-wrap gap-3">
                                <a
                                    href={waLink('Halo Green Deahan, saya ingin pengecekan gratis untuk lapangan saya.')}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="btn btn-primary btn-whatsapp"
                                >
                                    <WhatsAppIcon className="me-2" />
                                    Jadwalkan Pengecekan
                                </a>
                                <a href="#tips-perawatan" className="btn btn-outline-primary">
                                    Tips Perawatan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
