import FadeIn from '../elements/FadeIn';
import ParallaxSection from '../elements/ParallaxSection';
import TextAnimation from '../elements/TextAnimation';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import YoutubePopup from '../elements/YoutubePopup';
import { GD_CDN, waLink } from '../../../data/greendeahan';

export default function CtaSec() {
    return (
        <>
            <ParallaxSection overlayOpacity={0.25} className="video-sec golf-video" imgSrc={`${GD_CDN}/IMG-20181216-WA0019.jpg`}>
                <div className="container">
                    <div className="row">
                        <div className="col-lg-12 text-center">
                            <FadeIn delay={100}>
                                <YoutubePopup className="btn btn-outline-info cover-video" video="https://www.youtube.com/shorts/683ay_y6SE0" aria-label="Putar video">
                                    <i className="feather-icon icon-play"></i>
                                </YoutubePopup>
                            </FadeIn>
                        </div>
                    </div>
                </div>
            </ParallaxSection>

            <section className="cta-sec bg-cta bg-cover sec-padding">
                <div className="container">
                    <div className="row">
                        <div className="col-lg-12">
                            <div className="cta-txt text-center">
                                <span className="lead text-primary">Siap Mulai Proyek Anda?</span>
                                <TextAnimation>
                                    <h2 className="sec-title text-info mb-5 mt-2">Punya Lapangan Sendiri? Bisa Banget!</h2>
                                </TextAnimation>
                                <a
                                    href={waLink('Halo GreenDeahan, saya ingin konsultasi pembuatan lapangan')}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="btn btn-primary btn-whatsapp rrounded-pill"
                                >
                                    <WhatsAppIcon className="me-2" />
                                    Chat WhatsApp Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
