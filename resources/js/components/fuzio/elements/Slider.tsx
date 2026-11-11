import { forwardRef, useCallback, useEffect, useImperativeHandle, useLayoutEffect, useRef, useState, type ReactNode } from 'react';

export interface SliderHandle {
    next: (speed?: number) => void;
    prev: (speed?: number) => void;
}

interface SliderProps {
    slides: ReactNode[];
    /** ms the slide transition takes during autoplay */
    speed?: number;
    /** ms to wait between autoplay transitions */
    autoplayDelay?: number;
    spaceBetween?: number;
    /** min window width => slides per view, like Swiper breakpoints */
    breakpoints?: Record<number, number>;
}

const slidesPerViewFor = (breakpoints: Record<number, number> | undefined): number => {
    if (!breakpoints) {
        return 1;
    }

    const width = window.innerWidth;
    const matched = Object.keys(breakpoints)
        .map(Number)
        .sort((a, b) => a - b)
        .filter((minWidth) => width >= minWidth)
        .pop();

    return matched === undefined ? 1 : breakpoints[matched];
};

/**
 * Small looping autoplay carousel that renders Swiper's markup
 * (.swiper / .swiper-wrapper / .swiper-slide) so the template CSS applies,
 * without the swiper package. Clones `perView` slides on each side so the
 * loop is seamless.
 */
const Slider = forwardRef<SliderHandle, SliderProps>(function Slider({ slides, speed = 300, autoplayDelay = 3000, spaceBetween = 0, breakpoints }, handle) {
    const containerRef = useRef<HTMLDivElement>(null);
    const [perView, setPerView] = useState(1);
    const [containerWidth, setContainerWidth] = useState(0);
    const [index, setIndex] = useState(1);
    const [duration, setDuration] = useState(0);
    const autoplayTimer = useRef<number | undefined>(undefined);

    const total = slides.length;
    const clones = Math.min(perView, total);
    const loopSlides = [...slides.slice(total - clones), ...slides, ...slides.slice(0, clones)];
    const slideWidth = perView > 0 ? (containerWidth - spaceBetween * (perView - 1)) / perView : containerWidth;

    useLayoutEffect(() => {
        const container = containerRef.current;
        if (!container) {
            return;
        }

        const measure = () => {
            const view = slidesPerViewFor(breakpoints);
            setPerView(view);
            setContainerWidth(container.clientWidth);
            setDuration(0);
            setIndex(Math.min(view, total));
        };

        measure();
        const observer = new ResizeObserver(measure);
        observer.observe(container);

        return () => observer.disconnect();
    }, [breakpoints, total]);

    const go = useCallback(
        (step: number, transitionSpeed: number) => {
            setDuration(transitionSpeed);
            setIndex((current) => Math.min(Math.max(current + step, 0), total + clones * 2 - perView));
        },
        [total, clones, perView],
    );

    useImperativeHandle(handle, () => ({
        next: (customSpeed?: number) => go(1, customSpeed ?? speed),
        prev: (customSpeed?: number) => go(-1, customSpeed ?? speed),
    }), [go, speed]);

    // Autoplay: wait `autoplayDelay` after each slide settles, then advance.
    useEffect(() => {
        window.clearTimeout(autoplayTimer.current);
        autoplayTimer.current = window.setTimeout(() => go(1, speed), autoplayDelay + duration);

        return () => window.clearTimeout(autoplayTimer.current);
    }, [index, go, speed, autoplayDelay, duration]);

    const handleTransitionEnd = () => {
        // Jump from a clone back to the matching real slide without animating.
        if (index >= total + clones) {
            setDuration(0);
            setIndex(index - total);
        } else if (index < clones) {
            setDuration(0);
            setIndex(index + total);
        }
    };

    return (
        <div ref={containerRef} className="swiper swiper-initialized swiper-horizontal">
            <div
                className="swiper-wrapper"
                onTransitionEnd={(event) => event.target === event.currentTarget && handleTransitionEnd()}
                style={{
                    transform: `translate3d(${-index * (slideWidth + spaceBetween)}px, 0, 0)`,
                    transitionDuration: `${duration}ms`,
                }}
            >
                {loopSlides.map((slide, slideIndex) => (
                    <div
                        key={slideIndex}
                        className={`swiper-slide${slideIndex === index ? ' swiper-slide-active' : ''}`}
                        style={{ width: `${slideWidth}px`, marginRight: `${spaceBetween}px` }}
                        aria-hidden={slideIndex < index || slideIndex >= index + perView}
                    >
                        {slide}
                    </div>
                ))}
            </div>
        </div>
    );
});

export default Slider;
