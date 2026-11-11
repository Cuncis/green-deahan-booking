import '../../fuzio/css/fuzio.css';
import Header from '../components/fuzio/Header';
import Footer from '../components/fuzio/Footer';
import PageHeader from '../components/fuzio/PageHeader';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import BlogGrid from '../components/fuzio/blog/BlogGrid';
import { GD_CDN } from '../data/greendeahan';
import type { ArtikelKartu } from '../data/blog';

/**
 * Halaman Blog, porting dari template Fuzio "/blog"
 * (~/remove-later/fuzio/src/app/(pages)/blog/page.tsx). Artikel dari
 * database (BlogController::index), dikelola di /superadmin/artikel.
 */
export default function Blog({ artikel }: { artikel: ArtikelKartu[] }) {
    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader title="Tips & Panduan" imgSrc={`${GD_CDN}/futsal-img2.png`} crumbs={[{ label: 'Blog' }]} />
            <BlogGrid artikel={artikel} />
            <Footer />
            <ScrollToTop />
        </div>
    );
}
