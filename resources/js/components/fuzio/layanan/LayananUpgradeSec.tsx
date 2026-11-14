import FadeIn from '../elements/FadeIn';
import TextAnimation from '../elements/TextAnimation';
import { waLink } from '../../../data/greendeahan';
import { layananUpgrade } from '../../../data/upgradeLapangan';

/** The 6 upgrade options, same cards as LayananPerawatanSec, each with the benefit for the owner. */
export default function LayananUpgradeSec() {
    return (
        <section id="pilihan-upgrade" className="feature-sec bg-light sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-5">Pilihan Upgrade</h2>
                </TextAnimation>
                <div className="row">
                    {layananUpgrade.map((item, index) => (
                        <div key={item.judul} className="col-xl-4 col-md-6">
                            <div className="feature-entry">
                                <FadeIn delay={((index % 3) + 1) * 100}>
                                    <span className="icon rounded-circle shadow bg-info">
                                        <i className={`feather-icon ${item.ikon}`} style={{ fontSize: 32, color: '#004d00' }} aria-hidden="true"></i>
                                    </span>
                                    <h3 className="text-uppercase mb-2">{item.judul}</h3>
                                    <p>{item.deskripsi}</p>
                                    <p className="gd-manfaat-upgrade">
                                        <strong>Untungnya:</strong> {item.manfaat}
                                    </p>
                                    <a href={waLink(item.pesanWa)} target="_blank" rel="noopener noreferrer">
                                        Tanya via WhatsApp <i className="feather-icon icon-chevrons-right" aria-hidden="true"></i>
                                    </a>
                                </FadeIn>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
