import FaqAccordion from '../elements/FaqAccordion';
import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { waLink } from '../../../data/greendeahan';
import { langkahMulai, sistemBookingFaqs } from '../../../data/sistemBooking';

/** How to get started (same step cards as /perawatan), then the FAQ and final CTA. */
export default function MulaiSistemSec() {
    return (
        <>
            <section className="about-tab sec-padding">
                <div className="container">
                    <TextAnimation>
                        <h2 className="sec-title text-center mb-5">Cara Mulai</h2>
                    </TextAnimation>
                    <div className="row g-4">
                        {langkahMulai.map((langkah, index) => (
                            <div key={langkah.judul} className="col-lg-3 col-sm-6">
                                <div className="gd-langkah-perawatan h-100">
                                    <span className="nomor" aria-hidden="true">
                                        {String(index + 1).padStart(2, '0')}
                                    </span>
                                    <h3>{langkah.judul}</h3>
                                    <p className="mb-0">{langkah.teks}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                    <div className="d-flex flex-wrap justify-content-center gap-3 mt-5">
                        <a href="/daftar" className="btn btn-primary">
                            Daftar Sekarang
                        </a>
                        <a
                            href={waLink('Halo Green Deahan, saya ingin tanya soal sistem booking lapangan.')}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="btn btn-outline-primary"
                        >
                            <WhatsAppIcon className="me-2" />
                            Tanya Dulu via WhatsApp
                        </a>
                    </div>
                </div>
            </section>

            <section className="faq-page kontak-faq-sec sec-padding">
                <div className="container">
                    <TextAnimation>
                        <h2 className="sec-title text-center mb-5">Pertanyaan Seputar Sistem Booking</h2>
                    </TextAnimation>
                    <FaqAccordion items={sistemBookingFaqs} idPrefix="sistem-faq" twoColumns />
                </div>
            </section>
        </>
    );
}
