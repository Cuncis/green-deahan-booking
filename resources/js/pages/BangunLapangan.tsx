import '../../fuzio/css/fuzio.css';
import Header from '../components/fuzio/Header';
import Footer from '../components/fuzio/Footer';
import PageHeader from '../components/fuzio/PageHeader';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import CtaSec from '../components/fuzio/home/CtaSec';
import KalkulatorSec from '../components/fuzio/home/KalkulatorSec';
import ProductsSec from '../components/fuzio/home/ProductsSec';
import BangunFaqSec from '../components/fuzio/layanan/BangunFaqSec';
import BangunIntro from '../components/fuzio/layanan/BangunIntro';
import JenisLapanganSec from '../components/fuzio/layanan/JenisLapanganSec';
import ProsesBangunSec from '../components/fuzio/layanan/ProsesBangunSec';
import { GD_CDN } from '../data/greendeahan';

/**
 * Produk & Layanan > Bangun Lapangan. Built from Fuzio sections already on the
 * site (about intro, feature cards, process timeline, projects, ROI
 * calculator, FAQ, CTA) with copy taken from existing pages.
 */
export default function BangunLapangan() {
    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Bangun Lapangan" imgSrc={`${GD_CDN}/futsal-img4-1.png`} crumbs={[{ label: 'Produk & Layanan' }, { label: 'Bangun Lapangan' }]} />
            <BangunIntro />
            <JenisLapanganSec />
            <ProsesBangunSec />
            <ProductsSec />
            <KalkulatorSec />
            <BangunFaqSec />
            <CtaSec />
            <Footer />
            <ScrollToTop />
        </div>
    );
}
