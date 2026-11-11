import ParallaxSection from './elements/ParallaxSection';

interface PageHeaderProps {
    title: string;
    /** Banner background image */
    imgSrc: string;
}

/** Port of Fuzio's PageHeader: parallax banner with the page title and a breadcrumb. */
export default function PageHeader({ title, imgSrc }: PageHeaderProps) {
    return (
        <ParallaxSection overlayOpacity={0.5} imgSrc={imgSrc}>
            {/* backgroundImage none: the parallax image replaces the theme's CSS banner background */}
            <section className="promo-sec default-banner default-banner_bg position-relative jarallax" style={{ backgroundImage: 'none' }}>
                <div className="container">
                    <div className="row">
                        <div className="col-md-12">
                            <div className="promo-txt">
                                <h1 className="text-uppercase text-info mt-3 mb-5 text-center">{title}</h1>
                                <nav aria-label="breadcrumb">
                                    <ol className="breadcrumb">
                                        <li className="breadcrumb-item">
                                            <a href="/">Beranda</a>
                                        </li>
                                        <li className="breadcrumb-item active" aria-current="page">
                                            {title}
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </ParallaxSection>
    );
}
