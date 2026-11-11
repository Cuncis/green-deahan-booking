import FadeIn from '../elements/FadeIn';
import Slider from '../elements/Slider';
import TextAnimation from '../elements/TextAnimation';
import { GD_CDN } from '../../../data/greendeahan';
import Br1 from '../../../../fuzio/images/br1.png';
import Br1Hover from '../../../../fuzio/images/br1-hover.png';
import Br2 from '../../../../fuzio/images/br2.png';
import Br2Hover from '../../../../fuzio/images/br2-hover.png';
import Br3 from '../../../../fuzio/images/br3.png';
import Br3Hover from '../../../../fuzio/images/br3-hover.png';
import Br4 from '../../../../fuzio/images/br4.png';
import Br4Hover from '../../../../fuzio/images/br4-hover.png';
import Br5 from '../../../../fuzio/images/br5.png';
import Br5Hover from '../../../../fuzio/images/br5-hover.png';

/** 3 artikel panduan dari blog Green Deahan. Evergreen content, so cards show reading time instead of a date. */
const artikel = [
    {
        judul: 'Cara Hitung ROI Bisnis Lapangan Olahraga: Kapan Balik Modal?',
        href: '/blog/cara-hitung-roi-bisnis-lapangan-olahraga-kapan-balik-modal',
        gambar: `${GD_CDN}/roi-img.jpg`,
        kategori: 'Panduan Bisnis',
        menitBaca: 1,
        ringkasan: 'Hitung ROI sebelum investasi agar Anda tahu kapan modal bisa kembali dan berapa keuntungan yang bisa diharapkan.',
    },
    {
        judul: '7 Cara Merawat Lapangan Futsal agar Awet Sampai 10 Tahun',
        href: '/blog/7-cara-merawat-lapangan-futsal-agar-awet-sampai-10-tahun',
        gambar: `${GD_CDN}/interlock1.jpg`,
        kategori: 'Tips Perawatan',
        menitBaca: 1,
        ringkasan: 'Investasi lapangan futsal bisa bertahan lama jika dirawat dengan benar. 7 tips dari tim GreenDeahan berdasarkan pengalaman 16+ tahun.',
    },
    {
        judul: 'Ukuran Standar Lapangan Futsal Indoor & Outdoor Sesuai FIFA',
        href: '/blog/ukuran-standar-lapangan-futsal-indoor-outdoor-sesuai-fifa',
        gambar: `${GD_CDN}/futsal-court1.webp`,
        kategori: 'Futsal',
        menitBaca: 1,
        ringkasan: 'Lapangan futsal sesuai standar meningkatkan kepercayaan pelanggan dan memungkinkan Anda menggelar turnamen resmi.',
    },
];

/** Same image ratio as the /blog page cards (550x360). */
const blogThumbStyle = { aspectRatio: '550 / 360', objectFit: 'cover' } as const;

/** 5 logos in order, doubled, so each logo reappears only after the other four. */
const brandLogos = [
    { image: Br1, hoverImage: Br1Hover },
    { image: Br2, hoverImage: Br2Hover },
    { image: Br3, hoverImage: Br3Hover },
    { image: Br4, hoverImage: Br4Hover },
    { image: Br5, hoverImage: Br5Hover },
];
const brandSlides = [...brandLogos, ...brandLogos].map((brand, index) => (
    <div key={index} className="single-brand">
        {/* <a> without href: not clickable/focusable, but the template's hover CSS (.single-brand a img) still applies */}
        <a>
            <img src={brand.image} alt="Brand" />
            <img className="hover-img" src={brand.hoverImage} alt="Brand" />
        </a>
    </div>
));

/** Swiper-style breakpoints: min window width => slides per view. */
const brandBreakpoints = { 320: 1, 480: 2, 768: 3, 992: 5 };

export default function BlogSec() {
    return (
        <>
            <div className="brand-sec bg-primary">
                <div className="container">
                    <div className="row">
                        <div className="col-lg-12">
                            <div className="swiper brand-slider">
                                <FadeIn delay={100}>
                                    <Slider slides={brandSlides} autoplayDelay={1500} speed={600} spaceBetween={20} breakpoints={brandBreakpoints} />
                                </FadeIn>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section className="blog-sec sec-padding">
                <div className="container">
                    <div className="d-sm-flex justify-content-between align-items-center mb-5">
                        <TextAnimation>
                            <h2 className="sec-title">Panduan &amp; Tips Lapangan</h2>
                        </TextAnimation>
                        <a href="/blog" className="btn btn-dark text-primary">
                            Baca Semua Artikel
                        </a>
                    </div>
                    <div className="row pt-4">
                        {artikel.map((item) => (
                            <div key={item.href} className="col-lg-4 col-sm-6">
                                <div className="single-entry2 bg-mute rounded-2">
                                    <div className="entry-media">
                                        <a href={item.href}>
                                            <img src={item.gambar} alt={item.judul} width={550} height={360} loading="lazy" style={blogThumbStyle} className="img-fluid" />
                                        </a>
                                    </div>
                                    <div className="entry-content p-4">
                                        <div className="entry-meta pb-3">
                                            <span>
                                                <i className="feather-icon icon-file-text"></i>
                                                <a href="/blog">{item.kategori}</a>{' '}
                                            </span>
                                            <span>
                                                <i className="feather-icon icon-clock"></i>
                                                {item.menitBaca} Menit Baca
                                            </span>
                                        </div>
                                        <h3 className="text-uppercase">
                                            <a href={item.href}>{item.judul}</a>
                                        </h3>
                                        <p>{item.ringkasan}</p>
                                        <a href={item.href} className="btn-link d-block mt-4">
                                            Baca Selengkapnya <i className="feather-icon icon-chevrons-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>
        </>
    );
}
