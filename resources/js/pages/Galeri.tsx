import '../../fuzio/css/fuzio.css';
import Header from '../components/fuzio/Header';
import Footer from '../components/fuzio/Footer';
import PageHeader from '../components/fuzio/PageHeader';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import GaleriSec from '../components/fuzio/galeri/GaleriSec';
import { GD_CDN } from '../data/greendeahan';
import type { GaleriItem, GaleriKategori } from '../data/galeri';

/**
 * Halaman Galeri, porting dari Fuzio clone "/galeri"
 * (~/remove-later/fuzio/src/app/(pages)/galeri/page.tsx). Foto dari
 * database (GaleriController::index), dikelola di /superadmin/galeri.
 * Desain Blade lama ada di /v2/galeri.
 */
export default function Galeri({ kategori, items }: { kategori: GaleriKategori[]; items: GaleriItem[] }) {
    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Galeri Proyek" imgSrc={`${GD_CDN}/minisoccer6-img-1.png`} crumbs={[{ label: 'Galeri' }]} />
            <GaleriSec kategori={kategori} items={items} />
            <Footer />
            <ScrollToTop />
        </div>
    );
}
