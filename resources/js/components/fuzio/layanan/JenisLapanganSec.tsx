import FadeIn from '../elements/FadeIn';
import TextAnimation from '../elements/TextAnimation';
import { waLink } from '../../../data/greendeahan';
import { jenisLapangan } from '../../../data/jenisLapangan';

/** The 4 court types, same cards as the homepage FeatureSec. */
export default function JenisLapanganSec() {
    return (
        <section className="feature-sec bg-light sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-5">Lapangan yang Kami Bangun</h2>
                </TextAnimation>
                <div className="row">
                    {jenisLapangan.map((item) => (
                        <div key={item.nama} className="col-xl-3 col-md-6">
                            <div className="feature-entry">
                                <FadeIn delay={item.delay}>
                                    <span className="icon rounded-circle shadow bg-info">
                                        <img src={item.icon} width={40} height={40} alt="" style={item.invert ? { filter: 'invert(1)' } : undefined} />
                                    </span>
                                    <h3 className="text-uppercase mb-2">{item.nama}</h3>
                                    <p>{item.deskripsi}</p>
                                    <a href={waLink(`Halo, saya ingin konsultasi ${item.topik}`)} target="_blank" rel="noopener noreferrer">
                                        Konsultasi {item.nama} <i className="feather-icon icon-chevrons-right" aria-hidden="true"></i>
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
