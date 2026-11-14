import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { waLink } from '../../../data/greendeahan';
import { langkahBangun } from '../../../data/bangunLapangan';

/** How a build works: 5 step cards, same style as AlurPerawatanSec. */
export default function ProsesBangunSec() {
    return (
        <section className="about-tab sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-3">Cara kerja</h2>
                </TextAnimation>
                <p className="text-center mb-5">Satu kontrak, satu tim. Anda bisa pantau progres setiap hari.</p>
                <div className="row g-4 justify-content-center">
                    {langkahBangun.map((langkah, index) => (
                        <div key={langkah.judul} className="col-lg col-sm-6">
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
                        href={waLink('Halo Green Deahan, saya ingin minta penawaran pembangunan lapangan.')}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn btn-primary btn-whatsapp"
                    >
                        <WhatsAppIcon className="me-2" />
                        Minta penawaran
                    </a>
                </div>
            </div>
        </section>
    );
}
