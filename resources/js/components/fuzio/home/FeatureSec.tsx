import { useEffect, useState } from 'react';
import FadeIn from '../elements/FadeIn';
import ParallaxSection from '../elements/ParallaxSection';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { GD_CDN, waLink } from '../../../data/greendeahan';
import SoccerWhite from '../../../../fuzio/images/icons/soccer-w.png';
import MiniSoccerIcon from '../../../../fuzio/images/mini-soccer.png';
import PadelIcon from '../../../../fuzio/images/padel.png';
import BadmintonIcon from '../../../../fuzio/images/badminton.png';
import GolfBg from '../../../../fuzio/images/golf-bg.jpg';

/** 4 jenis lapangan Green Deahan. soccer-w.png is white, so it is inverted to match the other (black) icons. */
const layanan = [
    { nama: 'Futsal', icon: SoccerWhite, invert: true, delay: 100, topik: 'pembuatan lapangan futsal', deskripsi: 'Lantai interlock atau rumput sintetis, lengkap dengan LED, pagar, gawang & net.' },
    { nama: 'Mini Soccer', icon: MiniSoccerIcon, invert: false, delay: 200, topik: 'pembuatan lapangan mini soccer', deskripsi: 'Rumput sintetis premium grade A dengan sistem drainase profesional.' },
    { nama: 'Padel', icon: PadelIcon, invert: false, delay: 300, topik: 'pembuatan lapangan padel', deskripsi: 'Standar internasional IFF: rangka baja galvanis & kaca tempered.' },
    { nama: 'Badminton', icon: BadmintonIcon, invert: false, delay: 400, topik: 'pembuatan lapangan badminton', deskripsi: 'Lantai interlock atau kayu keras, non-slip, LED anti-silau.' },
];

export default function FeatureSec() {
    const [scrollY, setScrollY] = useState(0);

    useEffect(() => {
        const handleScroll = () => setScrollY(window.scrollY);
        window.addEventListener('scroll', handleScroll, { passive: true });

        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    return (
        <>
            <ParallaxSection
                imgSrc={GolfBg}
                overlayOpacity={0.1}
                className="golf-banner"
                speed={0.4}
                absoluteContent={
                    <div
                        className="golfer-img bg-gof-banner d-none d-md-block"
                        style={{
                            transform: `translateY(${scrollY * 0.3}px)`,
                            backgroundImage: `linear-gradient(rgba(0, 0, 0, 0.25), rgba(0, 0, 0, 0.25)), url(${GD_CDN}/photo_24_2024-07-23_10-52-44.jpg)`,
                        }}
                    />
                }
            >
                <div className="container">
                    <div className="row">
                        <div className="col-md-8">
                            <div className="banner-txt">
                                <FadeIn delay={100}>
                                    <span className="lead text-primary">Sejak 2010 &middot; Melayani Seluruh Indonesia</span>
                                    <h1 className="text-uppercase text-info mt-3 mb-5">Bangun Lapangan Olahraga dari Nol</h1>
                                    <div className="hero-actions">
                                        <a href={waLink()} target="_blank" rel="noopener noreferrer" className="btn btn-primary btn-whatsapp">
                                            <WhatsAppIcon className="me-2" />
                                            Konsultasi Gratis
                                        </a>
                                        <a href="#kalkulator" className="btn gd-btn-outline">
                                            <i className="feather-icon icon-bar-chart-2 me-2" aria-hidden="true"></i>
                                            Hitung Balik Modal
                                        </a>
                                    </div>
                                </FadeIn>
                            </div>
                        </div>
                    </div>
                </div>
            </ParallaxSection>

            <section className="feature-sec bg-light sec-padding">
                <div className="container">
                    <div className="row">
                        {layanan.map((item) => (
                            <div key={item.nama} className="col-xl-3 col-md-6">
                                <div className="feature-entry">
                                    <FadeIn delay={item.delay}>
                                        <span className="icon rounded-circle shadow bg-info">
                                            <img src={item.icon} width={40} height={40} alt={item.nama} style={item.invert ? { filter: 'invert(1)' } : undefined} />
                                        </span>
                                        <h3 className="text-uppercase mb-2">{item.nama}</h3>
                                        <p>{item.deskripsi}</p>
                                        <a href={waLink(`Halo, saya ingin konsultasi ${item.topik}`)} target="_blank" rel="noopener noreferrer">
                                            Tanya Harga <i className="feather-icon icon-chevrons-right"></i>
                                        </a>
                                    </FadeIn>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>
        </>
    );
}
