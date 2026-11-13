import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { waLink } from '../../../data/greendeahan';
import { alurPerawatan } from '../../../data/perawatan';

/** How a maintenance request works, plus warranty vs after-warranty, in the about-tab text style. */
export default function AlurPerawatanSec() {
    return (
        <section className="about-tab sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-5">Cara Kerja Perawatan</h2>
                </TextAnimation>
                <div className="row g-4">
                    {alurPerawatan.map((langkah, index) => (
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

                <div className="row g-4 mt-4">
                    <div className="col-lg-6">
                        <div className="tab-txt gd-kartu-garansi h-100">
                            <h3>Selama Masa Garansi</h3>
                            <ul>
                                <li>Garansi resmi tertulis untuk pekerjaan dan bahan</li>
                                <li>Masalah yang masuk garansi diperbaiki tanpa biaya tambahan</li>
                                <li>Cakupan dan masa garansi sesuai dokumen garansi lapangan Anda</li>
                            </ul>
                        </div>
                    </div>
                    <div className="col-lg-6">
                        <div className="tab-txt gd-kartu-garansi h-100">
                            <h3>Setelah Masa Garansi</h3>
                            <ul>
                                <li>Perawatan berkala, perbaikan, dan renovasi tetap kami layani</li>
                                <li>Pengecekan kondisi lapangan tetap gratis</li>
                                <li>Estimasi biaya tertulis dikirim sebelum pekerjaan dimulai</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div className="text-center mt-5">
                    <a href={waLink('Halo Green Deahan, saya ingin konsultasi perawatan lapangan.')} target="_blank" rel="noopener noreferrer" className="btn btn-primary btn-whatsapp">
                        <WhatsAppIcon className="me-2" />
                        Konsultasi Perawatan
                    </a>
                </div>
            </div>
        </section>
    );
}
