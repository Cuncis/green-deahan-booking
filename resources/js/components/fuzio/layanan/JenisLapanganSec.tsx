import FadeIn from '../elements/FadeIn';
import TextAnimation from '../elements/TextAnimation';
import { waLink } from '../../../data/greendeahan';
import { kartuBangun } from '../../../data/bangunLapangan';

/** The 4 court types with what is included and how long the work takes. No prices on purpose. */
export default function JenisLapanganSec() {
    return (
        <section id="jenis-lapangan" className="feature-sec bg-light sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-3">Empat jenis lapangan yang kami bangun</h2>
                </TextAnimation>
                <p className="text-center mb-5">Harga pasti disusun dalam RAB setelah survey lokasi.</p>
                <div className="row">
                    {kartuBangun.map(({ dasar, nama, poin, durasi, pesanWa }) => (
                        <div key={nama} className="col-xl-3 col-md-6">
                            <div className="feature-entry">
                                <FadeIn delay={dasar.delay}>
                                    <span className="icon rounded-circle shadow bg-info">
                                        <img src={dasar.icon} width={40} height={40} alt="" style={dasar.invert ? { filter: 'invert(1)' } : undefined} />
                                    </span>
                                    <h3 className="text-uppercase mb-2">{nama}</h3>
                                    <ul className="gd-poin-lapangan">
                                        {poin.map((item) => (
                                            <li key={item}>{item}</li>
                                        ))}
                                    </ul>
                                    <p className="gd-durasi-lapangan">{durasi}</p>
                                    <a href={waLink(pesanWa)} target="_blank" rel="noopener noreferrer">
                                        Tanya {nama.toLowerCase()} <i className="feather-icon icon-chevrons-right" aria-hidden="true"></i>
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
