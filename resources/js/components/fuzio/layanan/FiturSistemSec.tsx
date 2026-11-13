import FadeIn from '../elements/FadeIn';
import TextAnimation from '../elements/TextAnimation';
import { fiturUnggulan } from '../../../data/sistemBooking';

/** Key features, same cards as JenisLapanganSec (no link, so always fully visible). */
export default function FiturSistemSec() {
    return (
        <section className="feature-sec bg-light sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-5">Fitur Utama</h2>
                </TextAnimation>
                <div className="row">
                    {fiturUnggulan.map((item, index) => (
                        <div key={item.judul} className="col-xl-3 col-md-6">
                            <div className="feature-entry">
                                <FadeIn delay={(index + 1) * 100}>
                                    <span className="icon rounded-circle shadow bg-info">
                                        <i className={`feather-icon ${item.ikon}`} style={{ fontSize: 32, color: '#004d00' }} aria-hidden="true"></i>
                                    </span>
                                    <h3 className="text-uppercase mb-2">{item.judul}</h3>
                                    <p className="mb-0">{item.teks}</p>
                                </FadeIn>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
