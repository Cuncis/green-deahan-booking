import '../../fuzio/css/fuzio.css';
import Header from '../components/fuzio/Header';
import Footer from '../components/fuzio/Footer';
import PageHeader from '../components/fuzio/PageHeader';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import AlurUpgradeSec from '../components/fuzio/layanan/AlurUpgradeSec';
import LayananUpgradeSec from '../components/fuzio/layanan/LayananUpgradeSec';
import UpgradeFaqSec from '../components/fuzio/layanan/UpgradeFaqSec';
import UpgradeIntro from '../components/fuzio/layanan/UpgradeIntro';
import { GD_CDN } from '../data/greendeahan';

/**
 * Layanan > Upgrade Lapangan: lighting, roof, fencing, new surface, sport
 * conversion and drainage for any court. Copy lives in resources/js/data/upgradeLapangan.ts.
 */
export default function UpgradeLapangan() {
    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Upgrade Lapangan" imgSrc={`${GD_CDN}/futsal-court1.webp`} crumbs={[{ label: 'Layanan' }, { label: 'Upgrade Lapangan' }]} />
            <UpgradeIntro />
            <LayananUpgradeSec />
            <AlurUpgradeSec />
            <UpgradeFaqSec />
            <Footer />
            <ScrollToTop />
        </div>
    );
}
