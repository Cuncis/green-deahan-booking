import FadeIn from '../elements/FadeIn';
import TextAnimation from '../elements/TextAnimation';
import { GD_CDN } from '../../../data/greendeahan';

/** "Kenapa Pilih Kami?", shortened into plain words. */
const alasan = [
    { judul: 'Semua Kami Urus', teks: 'Dari survey sampai lapangan jadi. Anda tinggal pantau.' },
    { judul: 'Bergaransi Resmi', teks: 'Ada masalah? Kami perbaiki tanpa biaya tambahan.' },
    { judul: 'Harga Jelas', teks: 'Rincian biaya dikirim di awal dan bisa disesuaikan budget.' },
    { judul: 'Tepat Waktu', teks: 'Jadwal tertulis di kontrak. Terlambat, kami yang tanggung.' },
    { judul: 'Seluruh Indonesia', teks: 'Kami datang ke lokasi Anda, di mana pun.' },
];

export default function WhyUsSec() {
    return (
        <section className="testimonial-sec why-us-sec sec-padding bg-light">
            <div className="container">
                <div className="row align-items-center">
                    <div className="col-md-6 order-2 order-md-1">
                        <TextAnimation>
                            <h2 className="sec-title mb-5">Dipercaya Ratusan Pemilik Lapangan</h2>
                        </TextAnimation>
                        <ul className="why-us-list list-unstyled mb-0">
                            {alasan.map((item, index) => (
                                <li key={item.judul}>
                                    <FadeIn delay={50 + index * 80}>
                                        <div className="d-flex">
                                            <i className="feather-icon icon-check-circle" aria-hidden="true" />
                                            <div>
                                                <h3>{item.judul}</h3>
                                                <p>{item.teks}</p>
                                            </div>
                                        </div>
                                    </FadeIn>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="col-md-6 order-1 order-md-2 mb-5 mb-md-0">
                        <div className="testimonial-media ps-lg-5" style={{ animation: 'none' }}>
                            <img
                                className="img-fluid"
                                src={`${GD_CDN}/photo_28_2024-07-23_10-52-44.webp`}
                                alt="Tim Green Deahan mengerjakan struktur dasar lapangan"
                                width={600}
                                height={600}
                                loading="lazy"
                                style={{ aspectRatio: '1 / 1', objectFit: 'cover' }}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
