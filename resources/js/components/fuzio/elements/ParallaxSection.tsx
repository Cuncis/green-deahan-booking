import { useEffect, useRef, type ReactNode } from 'react';

interface ParallaxSectionProps {
    children: ReactNode;
    imgSrc: string;
    className?: string;
    /** 0..1, like jarallax: lower = background moves slower than the page */
    speed?: number;
    overlayOpacity?: number;
    absoluteContent?: ReactNode;
}

/**
 * Replacement for Fuzio's JarallaxSection (jarallax "scroll" type):
 * a cover background image that scrolls slower than the page, with a
 * black overlay and the content on top.
 */
export default function ParallaxSection({ children, imgSrc, className = '', speed = 0.5, overlayOpacity = 0.8, absoluteContent }: ParallaxSectionProps) {
    const sectionRef = useRef<HTMLDivElement>(null);
    const imageRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const section = sectionRef.current;
        const image = imageRef.current;
        if (!section || !image) {
            return;
        }

        let frame = 0;
        const update = () => {
            frame = 0;
            const rect = section.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const range = (viewportHeight + rect.height) * (1 - speed);
            const progress = Math.min(Math.max((viewportHeight - rect.top) / (viewportHeight + rect.height), 0), 1);

            image.style.height = `${rect.height + range}px`;
            image.style.transform = `translate3d(0, ${progress * range - range}px, 0)`;
        };
        const schedule = () => {
            if (!frame) {
                frame = requestAnimationFrame(update);
            }
        };

        update();
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);

        return () => {
            cancelAnimationFrame(frame);
            window.removeEventListener('scroll', schedule);
            window.removeEventListener('resize', schedule);
        };
    }, [speed]);

    return (
        <div ref={sectionRef} className={`jarallax ${className}`}>
            <div style={{ position: 'absolute', inset: 0, overflow: 'hidden', zIndex: -100, pointerEvents: 'none' }}>
                <div
                    ref={imageRef}
                    style={{
                        position: 'absolute',
                        top: 0,
                        left: 0,
                        width: '100%',
                        backgroundImage: `url("${imgSrc}")`,
                        backgroundSize: 'cover',
                        backgroundPosition: '50% 50%',
                        backgroundRepeat: 'no-repeat',
                        willChange: 'transform',
                    }}
                />
            </div>
            <div
                className="jarallax-overlay"
                style={{ position: 'absolute', top: 0, left: 0, width: '100%', height: '100%', backgroundColor: 'rgba(0, 0, 0, 1)', opacity: overlayOpacity, zIndex: 0 }}
            />
            {absoluteContent}
            <div className="jarallax-content" style={{ position: 'relative', zIndex: 1 }}>
                {children}
            </div>
        </div>
    );
}
