import '../../fuzio/css/fuzio.css';
import Header from '../components/fuzio/Header';
import Footer from '../components/fuzio/Footer';
import PageHeader from '../components/fuzio/PageHeader';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import ContactCardSec from '../components/fuzio/kontak/ContactCardSec';
import ContactFormSec from '../components/fuzio/kontak/ContactFormSec';
import KontakFaqSec from '../components/fuzio/kontak/KontakFaqSec';
import { GD_CDN } from '../data/greendeahan';

/**
 * Halaman Kontak, porting dari template Fuzio "/contact"
 * (~/remove-later/fuzio/src/app/(pages)/contact/page.tsx). Halaman Kontak
 * Blade lama ada di /v2/kontak.
 */
export default function Kontak() {
    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Kontak Kami" imgSrc={`${GD_CDN}/futsal1-outdoor.png`} />
            <ContactCardSec />
            <ContactFormSec />
            <KontakFaqSec />
            <Footer />
            <ScrollToTop />
        </div>
    );
}
