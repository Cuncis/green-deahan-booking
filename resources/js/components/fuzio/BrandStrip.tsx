import FadeIn from './elements/FadeIn';
import Slider from './elements/Slider';
import Br1 from '../../../fuzio/images/br1.png';
import Br1Hover from '../../../fuzio/images/br1-hover.png';
import Br2 from '../../../fuzio/images/br2.png';
import Br2Hover from '../../../fuzio/images/br2-hover.png';
import Br3 from '../../../fuzio/images/br3.png';
import Br3Hover from '../../../fuzio/images/br3-hover.png';
import Br4 from '../../../fuzio/images/br4.png';
import Br4Hover from '../../../fuzio/images/br4-hover.png';
import Br5 from '../../../fuzio/images/br5.png';
import Br5Hover from '../../../fuzio/images/br5-hover.png';

/** 5 logos in order, doubled, so each logo reappears only after the other four. */
const brandLogos = [
    { image: Br1, hoverImage: Br1Hover },
    { image: Br2, hoverImage: Br2Hover },
    { image: Br3, hoverImage: Br3Hover },
    { image: Br4, hoverImage: Br4Hover },
    { image: Br5, hoverImage: Br5Hover },
];
const brandSlides = [...brandLogos, ...brandLogos].map((brand, index) => (
    <div key={index} className="single-brand">
        {/* <a> without href: not clickable/focusable, but the template's hover CSS (.single-brand a img) still applies */}
        <a>
            <img src={brand.image} alt="Brand" />
            <img className="hover-img" src={brand.hoverImage} alt="Brand" />
        </a>
    </div>
));

/** Swiper-style breakpoints: min window width => slides per view. Two logos on phones instead of the template's one, so the strip is not a tall block with a single logo. */
const brandBreakpoints = { 0: 2, 576: 3, 992: 5 };

interface BrandStripProps {
    /** ms per slide transition (homepage 600, Tentang Kami 1600, as in Fuzio) */
    speed: number;
    /** fade the strip in when it scrolls into view (homepage only) */
    fadeIn?: boolean;
}

/** Green strip with the auto-scrolling brand logos (Fuzio's brand-sec). */
export default function BrandStrip({ speed, fadeIn = false }: BrandStripProps) {
    const slider = <Slider slides={brandSlides} autoplayDelay={1500} speed={speed} spaceBetween={20} breakpoints={brandBreakpoints} />;

    return (
        <div className="brand-sec bg-primary">
            <div className="container">
                <div className="row">
                    <div className="col-lg-12">
                        <div className="swiper brand-slider">{fadeIn ? <FadeIn delay={100}>{slider}</FadeIn> : slider}</div>
                    </div>
                </div>
            </div>
        </div>
    );
}
