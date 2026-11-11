import ParallaxSection from './elements/ParallaxSection';

interface Crumb {
    label: string;
    href?: string;
}

interface PageHeaderProps {
    title: string;
    /** Banner background image */
    imgSrc: string;
    /** Extra class on the title, e.g. `long-title` for article titles */
    titleClassName?: string;
    /** Breadcrumb after "Beranda"; defaults to just the title */
    crumbs?: Crumb[];
}

/** Port of Fuzio's PageHeader: parallax banner with the page title and a breadcrumb. */
export default function PageHeader({ title, imgSrc, titleClassName = '', crumbs }: PageHeaderProps) {
    const trail: Crumb[] = crumbs ?? [{ label: title }];

    return (
        <ParallaxSection overlayOpacity={0.5} imgSrc={imgSrc}>
            {/* backgroundImage none: the parallax image replaces the theme's CSS banner background */}
            <section className="promo-sec default-banner default-banner_bg position-relative jarallax" style={{ backgroundImage: 'none' }}>
                <div className="container">
                    <div className="row">
                        <div className="col-md-12">
                            <div className="promo-txt">
                                <h1 className={`text-uppercase text-info mt-3 mb-5 text-center ${titleClassName}`.trim()}>{title}</h1>
                                <nav aria-label="breadcrumb">
                                    <ol className="breadcrumb">
                                        <li className="breadcrumb-item">
                                            <a href="/">Beranda</a>
                                        </li>
                                        {trail.map((crumb, index) =>
                                            crumb.href && index < trail.length - 1 ? (
                                                <li key={crumb.label} className="breadcrumb-item">
                                                    <a href={crumb.href}>{crumb.label}</a>
                                                </li>
                                            ) : (
                                                <li key={crumb.label} className="breadcrumb-item active" aria-current="page">
                                                    {crumb.label}
                                                </li>
                                            ),
                                        )}
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
