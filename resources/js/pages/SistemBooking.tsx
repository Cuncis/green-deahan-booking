import '../../fuzio/css/fuzio.css';
import Header from '../components/fuzio/Header';
import Footer from '../components/fuzio/Footer';
import PageHeader from '../components/fuzio/PageHeader';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import FiturSistemSec from '../components/fuzio/layanan/FiturSistemSec';
import MulaiSistemSec from '../components/fuzio/layanan/MulaiSistemSec';
import PaketSistemSec, { type PaketSistem } from '../components/fuzio/layanan/PaketSistemSec';
import SistemBookingIntro from '../components/fuzio/layanan/SistemBookingIntro';
import TabelFiturSec from '../components/fuzio/layanan/TabelFiturSec';
import { GD_CDN } from '../data/greendeahan';

/**
 * Layanan > Sistem Booking: the court booking website product.
 * Prices come from SistemBookingController; copy from resources/js/data/sistemBooking.ts.
 */
export default function SistemBooking({ paket, diskonPerpanjanganPersen }: { paket: PaketSistem[]; diskonPerpanjanganPersen: number }) {
    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Sistem Booking" imgSrc={`${GD_CDN}/badminton1-img.png`} crumbs={[{ label: 'Layanan' }, { label: 'Sistem Booking' }]} />
            <SistemBookingIntro />
            <FiturSistemSec />
            <PaketSistemSec paket={paket} diskonPerpanjanganPersen={diskonPerpanjanganPersen} />
            <TabelFiturSec />
            <MulaiSistemSec />
            <Footer />
            <ScrollToTop />
        </div>
    );
}
