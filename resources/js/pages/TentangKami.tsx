import '../../fuzio/css/fuzio.css';
import Header from '../components/fuzio/Header';
import Footer from '../components/fuzio/Footer';
import PageHeader from '../components/fuzio/PageHeader';
import BrandStrip from '../components/fuzio/BrandStrip';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import AboutIntro from '../components/fuzio/about/AboutIntro';
import WhyUsSec from '../components/fuzio/about/WhyUsSec';
import AboutTab from '../components/fuzio/about/AboutTab';
import { GD_CDN } from '../data/greendeahan';

/**
 * Halaman Tentang Kami, porting dari template Fuzio "/about"
 * (~/remove-later/fuzio/src/app/(pages)/about/page.tsx). Menggantikan /konsep.
 */
export default function TentangKami() {
    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Tentang Kami" imgSrc={`${GD_CDN}/minisoccer1-img.jpeg`} />
            <AboutIntro />
            <BrandStrip speed={1600} />
            <WhyUsSec />
            <AboutTab />
            <Footer />
            <ScrollToTop />
        </div>
    );
}
