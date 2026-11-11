import '../../fuzio/css/fuzio.css';
import Header from '../components/fuzio/Header';
import Footer from '../components/fuzio/Footer';
import PageHeader from '../components/fuzio/PageHeader';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import SinglePost from '../components/fuzio/blog/SinglePost';
import { GD_CDN } from '../data/greendeahan';
import { BLOG_PATH, type ArtikelDetail, type ArtikelKartu } from '../data/blog';

/**
 * Halaman detail artikel, porting dari halaman artikel di Fuzio clone
 * (~/remove-later/fuzio/src/app/(pages)/blog/[slug]/page.tsx). Data dari
 * BlogController::show().
 */
export default function BlogShow({ artikel, terkait }: { artikel: ArtikelDetail; terkait: ArtikelKartu[] }) {
    return (
        <div className="App">
            <Header variant="pages" />
            <PageHeader
                title={artikel.judul}
                titleClassName="long-title"
                imgSrc={`${GD_CDN}/futsal-img2.png`}
                crumbs={[{ label: 'Blog', href: BLOG_PATH }, { label: 'Artikel' }]}
            />
            <main className="single-post-page sec-padding">
                <div className="container">
                    <div className="row">
                        <div className="col-lg-10 offset-lg-1">
                            <SinglePost artikel={artikel} terkait={terkait} />
                        </div>
                    </div>
                </div>
            </main>
            <Footer />
            <ScrollToTop />
        </div>
    );
}
