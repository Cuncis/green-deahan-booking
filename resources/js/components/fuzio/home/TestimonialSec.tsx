import { useRef, type CSSProperties } from 'react';
import FadeIn from '../elements/FadeIn';
import Slider, { type SliderHandle } from '../elements/Slider';
import TextAnimation from '../elements/TextAnimation';
import type { Testimonial } from '../../../data/greendeahan';
import Star from '../../../../fuzio/images/icons/star.svg';
import StarHalf from '../../../../fuzio/images/icons/star-half.svg';

/** Slide speed (ms) when the visitor clicks an arrow. */
const MANUAL_SPEED = 600;

/** Remote (CDN) photos aren't square; crop them to the template's square slots. */
const squareCover: CSSProperties = { aspectRatio: '1 / 1', objectFit: 'cover' };

function StarRating({ rating }: { rating: number }) {
    const fullStars = Math.floor(rating);
    const hasHalf = rating % 1 >= 0.5;

    return (
        <div className="ratings mb-3">
            {Array.from({ length: fullStars }).map((_, index) => (
                <img key={`full-${index}`} src={Star} alt="star" width={18} height={18} />
            ))}
            {hasHalf && <img src={StarHalf} alt="half star" width={18} height={18} />}
        </div>
    );
}

interface TestimonialSecProps {
    title: string;
    testimonials: Testimonial[];
    media: string;
    mediaAlt: string;
}

export default function TestimonialSec({ title, testimonials, media, mediaAlt }: TestimonialSecProps) {
    const sliderRef = useRef<SliderHandle>(null);

    const slides = testimonials.map((testimonial) => (
        <div key={testimonial.id} className="single-testimonial">
            <StarRating rating={testimonial.rating} />
            <p>&ldquo;{testimonial.text}&rdquo;</p>
            <div className="d-flex align-items-center pt-4 mt-4 border-top">
                <div className="avatar">
                    <img className="img-fluid rounded-circle" src={testimonial.avatar} alt={`${testimonial.name} avatar`} width={64} height={64} style={squareCover} />
                </div>
                <div className="avatar-info ms-3">
                    <h6 className="mb-0 body-font">{testimonial.name}</h6>
                    <span className="text-xs">{testimonial.role}</span>
                </div>
            </div>
        </div>
    ));

    return (
        <section className="testimonial-sec sec-padding bg-light">
            <div className="container">
                <div className="row align-items-center">
                    <div className="col-md-6 order-2 order-lg-1">
                        <div className="testimonial-content">
                            <TextAnimation>
                                <h2 className="sec-title mb-5">{title}</h2>
                            </TextAnimation>
                            <div className="swiper testimonial-slider">
                                <FadeIn delay={50}>
                                    <Slider ref={sliderRef} slides={slides} autoplayDelay={2500} speed={2000} />
                                </FadeIn>

                                {/* The template's CSS places .swiper-button-next on the LEFT with a "<-" icon and
                                    .swiper-button-prev on the RIGHT with a "->" icon, so each handler follows the icon it shows. */}
                                <div className="testimonial-nav">
                                    <div onClick={() => sliderRef.current?.next(MANUAL_SPEED)} className="swiper-button-prev" role="button" aria-label="Testimoni berikutnya" />
                                    <div onClick={() => sliderRef.current?.prev(MANUAL_SPEED)} className="swiper-button-next" role="button" aria-label="Testimoni sebelumnya" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="col-md-6 order-1 order-lg-2">
                        {/* animation: none turns off the template's floating "updown" effect */}
                        <div className="testimonial-media ps-lg-5" style={{ animation: 'none' }}>
                            <img className="img-fluid" src={media} alt={mediaAlt} width={600} height={600} loading="lazy" style={squareCover} />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
