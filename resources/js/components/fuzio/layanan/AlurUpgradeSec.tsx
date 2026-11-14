import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { waLink } from '../../../data/greendeahan';
import { alurUpgrade } from '../../../data/upgradeLapangan';

/** How an upgrade job works, same step cards as AlurPerawatanSec. */
export default function AlurUpgradeSec() {
    return (
        <section className="about-tab sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-5">Cara Kerja Upgrade</h2>
                </TextAnimation>
                <div className="row g-4">
                    {alurUpgrade.map((langkah, index) => (
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

                <div className="text-center mt-5">
                    <a
                        href={waLink('Halo Green Deahan, saya ingin konsultasi upgrade lapangan.')}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn btn-primary btn-whatsapp"
                    >
                        <WhatsAppIcon className="me-2" />
                        Konsultasi Upgrade
                    </a>
                </div>
            </div>
        </section>
    );
}
