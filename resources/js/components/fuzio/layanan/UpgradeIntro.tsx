import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { ChecklistIcon, StadiumIcon } from '../icons';
import { GD_CDN, waLink } from '../../../data/greendeahan';

/** /upgrade-lapangan intro, same layout as PerawatanIntro. Open to every court. */
export default function UpgradeIntro() {
    return (
        <section className="sec-padding tips-sec">
            <div className="container">
                <div className="row align-items-center">
                    <div className="col-lg-6 col-sm-10 mx-sm-auto">
                        <div className="graphic-media pe-lg-5">
                            <img
                                className="img-fluid"
                                src={`${GD_CDN}/futsal-img2.png`}
                                width={630}
                                height={630}
                                style={{ width: '100%', height: 'auto' }}
                                alt="Lapangan futsal dengan pencahayaan dan jaring pengaman"
                            />
                        </div>
                    </div>
                    <div className="col-lg-6">
                        <div className="tips-txt ps-lg-5 mt-5 mt-lg-0">
                            <span className="lead text-primary">Untuk Semua Lapangan</span>
                            <TextAnimation>
                                <h2 className="sec-title mt-2">Lapangan Lama, Tampil Baru</h2>
                            </TextAnimation>
                            <p>
                                Tidak perlu membangun dari nol untuk membuat lapangan lebih laku. Upgrade lampu, atap, jaring, permukaan, atau ubah ke olahraga lain. Terbuka untuk
                                semua lapangan, termasuk yang dibangun kontraktor lain.
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
                                    <h3 className="text-uppercase my-3">Sejak 2010</h3>
                                    <p>Dikerjakan tim yang sudah membangun lapangan futsal, mini soccer, padel, dan badminton.</p>
                                </div>
                            </div>
                            <div className="d-flex flex-wrap gap-3">
                                <a
                                    href={waLink('Halo Green Deahan, saya ingin pengecekan gratis untuk upgrade lapangan saya.')}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="btn btn-primary btn-whatsapp"
                                >
                                    <WhatsAppIcon className="me-2" />
                                    Jadwalkan Pengecekan
                                </a>
                                <a href="#pilihan-upgrade" className="btn btn-outline-primary">
                                    Pilihan Upgrade
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
