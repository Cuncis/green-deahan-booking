import CaraKerjaTimeline from '../about/CaraKerjaTimeline';
import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { waLink } from '../../../data/greendeahan';

/** Process: the 5-step timeline from Tentang Kami, with the timings and payment terms already on the site. */
export default function ProsesBangunSec() {
    return (
        <section className="about-tab sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-5">Proses Pembangunan</h2>
                </TextAnimation>
                <div className="row align-items-center">
                    <div className="col-lg-6">
                        <CaraKerjaTimeline />
                    </div>
                    <div className="col-lg-6">
                        <div className="tab-txt ps-lg-4 mt-5 mt-lg-0">
                            <h3>Proses Mudah, Anda Cukup Duduk Santai</h3>
                            <p>
                                Kami handle semua proses dari awal sampai lapangan siap beroperasi, dengan transparansi penuh di setiap tahap. Setelah konsultasi awal, survey dan
                                desain biasanya 3 sampai 7 hari, penawaran harga 3 sampai 5 hari, lalu konstruksi bisa dimulai 1 sampai 2 minggu setelah deal.
                            </p>
                            <ul>
                                <li>Bayar DP di awal, pelunasan setelah lapangan selesai 100%</li>
                                <li>Rincian biaya (RAB) dikirim di awal, tanpa biaya tersembunyi</li>
                                <li>Jadwal tertulis di kontrak, progress bisa dipantau setiap hari</li>
                                <li>Garansi resmi konstruksi & material</li>
                            </ul>
                            <a
                                href={waLink('Halo Green Deahan, saya ingin konsultasi proses pembangunan lapangan.')}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="btn btn-primary btn-whatsapp mt-4"
                            >
                                <WhatsAppIcon className="me-2" />
                                Konsultasi Gratis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
