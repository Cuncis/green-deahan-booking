import '../../fuzio/css/fuzio.css';
import Header from '../components/fuzio/Header';
import Footer from '../components/fuzio/Footer';
import ScrollToTop from '../components/fuzio/elements/ScrollToTop';
import FeatureSec from '../components/fuzio/home/FeatureSec';
import SecPadding from '../components/fuzio/home/SecPadding';
import CtaSec from '../components/fuzio/home/CtaSec';
import ProductsSec from '../components/fuzio/home/ProductsSec';
import TestimonialSec from '../components/fuzio/home/TestimonialSec';
import BlogSec from '../components/fuzio/home/BlogSec';
import { GD_CDN, gdTestimonials } from '../data/greendeahan';

/**
 * Homepage situs korporat, porting 1:1 dari template Fuzio "Home (Gulf)"
 * (~/remove-later/fuzio/src/app/page.tsx). Homepage Blade lama ada di /v2.
 */
export default function Home() {
    return (
        <div className="App">
            <Header />
            <FeatureSec />
            <SecPadding />
            <CtaSec />
            <ProductsSec />
            <TestimonialSec title="Apa Kata Klien Kami?" testimonials={gdTestimonials} media={`${GD_CDN}/hero-img2.png`} mediaAlt="Tim Green Deahan sedang bekerja" />
            <BlogSec />
            <Footer />
            <ScrollToTop />
        </div>
    );
}
