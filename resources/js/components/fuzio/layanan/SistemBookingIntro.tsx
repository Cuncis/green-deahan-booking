import TextAnimation from '../elements/TextAnimation';
import { ChecklistIcon, StadiumIcon } from '../icons';
import { GD_CDN } from '../../../data/greendeahan';

/** /sistem-booking intro, same layout as AboutIntro. */
export default function SistemBookingIntro() {
    return (
        <section className="sec-padding tips-sec">
            <div className="container">
                <div className="row align-items-center">
                    <div className="col-lg-6 col-sm-10 mx-sm-auto">
                        <div className="graphic-media pe-lg-5">
                            <img
                                className="img-fluid"
                                src={`${GD_CDN}/badminton1-img.png`}
                                width={630}
                                height={630}
                                style={{ width: '100%', height: 'auto' }}
                                alt="Lapangan badminton yang bisa dibooking online"
                            />
                        </div>
                    </div>
                    <div className="col-lg-6">
                        <div className="tips-txt ps-lg-5 mt-5 mt-lg-0">
                            <span className="lead text-primary">Website Booking Lapangan</span>
                            <TextAnimation>
                                <h2 className="sec-title mt-2">Pelanggan Booking Sendiri 24 Jam, Anda Tinggal Pantau</h2>
                            </TextAnimation>
                            <p>
                                Punya lapangan? Kelola booking-nya lewat website sendiri. Pelanggan melihat jadwal yang kosong, memilih jam, lalu membayar online. Semua paket
                                sudah termasuk setup dan support 1 tahun.
                            </p>
                            <div className="d-sm-flex my-5">
                                <div className="tips-entry pe-lg-4">
                                    <span className="icon-sm bg-primary rounded-circle">
                                        <i style={{ color: '#fff' }}>
                                            <ChecklistIcon />
                                        </i>
                                    </span>
                                    <h3 className="text-uppercase my-3">Tanpa Bentrok</h3>
                                    <p>Jam yang sudah dipilih atau dibayar otomatis terkunci untuk pelanggan lain.</p>
                                </div>
                                <div className="tips-entry pe-lg-4">
                                    <span className="icon-sm bg-primary rounded-circle">
                                        <i style={{ color: '#fff' }}>
                                            <StadiumIcon />
                                        </i>
                                    </span>
                                    <h3 className="text-uppercase my-3">Bayar Online</h3>
                                    <p>QRIS, virtual account, dan e-wallet, dikonfirmasi otomatis.</p>
                                </div>
                            </div>
                            <div className="d-flex flex-wrap gap-3">
                                <a href="#paket" className="btn btn-primary">
                                    Lihat Paket
                                </a>
                                <a href="/daftar" className="btn btn-outline-primary">
                                    Daftar Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
