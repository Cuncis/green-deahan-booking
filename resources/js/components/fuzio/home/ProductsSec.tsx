import FadeIn from '../elements/FadeIn';
import TextAnimation from '../elements/TextAnimation';
import { GD_CDN } from '../../../data/greendeahan';
import Star from '../../../../fuzio/images/icons/star.svg';
import StarHalf from '../../../../fuzio/images/icons/star-half.svg';

interface Proyek {
    id: number;
    name: string;
    city: string;
    image: string;
    badge: string;
    /** out of 5, supports .5 */
    rating: number;
    fadeDelay: number;
}

/** Portofolio proyek Green Deahan (4 dari section "Hasil Nyata dari Proyek Kami"). */
const proyek: Proyek[] = [
    { id: 1, name: 'Futsal Arena', city: 'Jakarta Selatan', image: `${GD_CDN}/futsal-img4-1.png`, badge: 'Futsal', rating: 5, fadeDelay: 100 },
    { id: 2, name: 'Mini Soccer Outdoor', city: 'Surabaya', image: `${GD_CDN}/minisoccer-img2.png`, badge: 'Mini Soccer', rating: 5, fadeDelay: 200 },
    { id: 3, name: 'Padel Premium', city: 'Medan', image: `${GD_CDN}/padel1-img.png`, badge: 'Padel', rating: 5, fadeDelay: 300 },
    { id: 4, name: 'Badminton 4 Court', city: 'Bandung', image: `${GD_CDN}/badminton1-img.png`, badge: 'Badminton', rating: 5, fadeDelay: 400 },
];

function StarRating({ rating }: { rating: number }) {
    const fullStars = Math.floor(rating);
    const hasHalf = rating % 1 >= 0.5;

    return (
        <div className="ratings">
            {Array.from({ length: fullStars }).map((_, index) => (
                <img key={`full-${index}`} src={Star} alt="star" width={16} height={16} />
            ))}
            {hasHalf && <img src={StarHalf} alt="half star" width={16} height={16} />}
        </div>
    );
}

export default function ProductsSec() {
    return (
        <section className="products-sec sec-padding">
            <TextAnimation>
                <h2 className="sec-title text-center mb-5">Hasil Nyata dari Proyek Kami</h2>
            </TextAnimation>
            <div className="container">
                <div className="row">
                    {proyek.map((product) => (
                        <div key={product.id} className="col-lg-3 col-sm-6">
                            <div className="product-entry shadow-sm position-relative">
                                <FadeIn delay={product.fadeDelay}>
                                    <div className="product-thumb position-relative">
                                        <div className="badge text-uppercase">{product.badge}</div>
                                        <img
                                            className="img-fluid"
                                            src={product.image}
                                            alt={`${product.name} ${product.city}`}
                                            width={299}
                                            height={338}
                                            loading="lazy"
                                            style={{ aspectRatio: '299 / 338', objectFit: 'cover' }}
                                        />
                                    </div>
                                    <div className="product-txt">
                                        <h3 className="text-nowrap">
                                            <a href="/galeri" className="stretched-link">
                                                {product.name}
                                            </a>
                                        </h3>
                                        <div className="d-flex justify-content-between">
                                            <div className="price project-location">
                                                <i className="feather-icon icon-map-pin" aria-hidden="true" />
                                                {product.city}
                                            </div>
                                            <StarRating rating={product.rating} />
                                        </div>
                                    </div>
                                </FadeIn>
                            </div>
                        </div>
                    ))}

                    <div className="col-12 text-center mt-5">
                        <a href="/galeri" className="btn btn-dark text-primary">
                            Lihat 50+ Proyek Lainnya <i className="feather-icon icon-plus" />
                        </a>
                    </div>
                </div>
            </div>
        </section>
    );
}
