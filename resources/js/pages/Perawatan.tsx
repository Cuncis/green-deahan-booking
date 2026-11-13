import '../../fuzio/css/fuzio.css';
import Header from '../components/fuzio/Header';
import Footer from '../components/fuzio/Footer';
import PageHeader from '../components/fuzio/PageHeader';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import AlurPerawatanSec from '../components/fuzio/layanan/AlurPerawatanSec';
import LayananPerawatanSec from '../components/fuzio/layanan/LayananPerawatanSec';
import PerawatanFaqSec from '../components/fuzio/layanan/PerawatanFaqSec';
import PerawatanIntro from '../components/fuzio/layanan/PerawatanIntro';
import TipsPerawatanSec from '../components/fuzio/layanan/TipsPerawatanSec';
import { GD_CDN } from '../data/greendeahan';

/**
 * Produk & Layanan > Perawatan: maintenance for courts Green Deahan built.
 * Copy lives in resources/js/data/perawatan.ts.
 */
export default function Perawatan() {
    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Perawatan Lapangan" imgSrc={`${GD_CDN}/futsal1-outdoor.png`} crumbs={[{ label: 'Produk & Layanan' }, { label: 'Perawatan' }]} />
            <PerawatanIntro />
            <LayananPerawatanSec />
            <AlurPerawatanSec />
            <TipsPerawatanSec />
            <PerawatanFaqSec />
            <Footer />
            <ScrollToTop />
        </div>
    );
}
