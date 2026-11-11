import type { CSSProperties, ReactNode } from 'react';
import useInView from './useInView';

type FadeVariant = 'fadeIn' | 'fadeInUp';

interface FadeInProps {
    children: ReactNode;
    variant?: FadeVariant;
    /** ms */
    delay?: number;
    /** ms */
    duration?: number;
    distance?: number;
    className?: string;
}

/**
 * Port of Fuzio's FadeInAdvanced (framer-motion) using a CSS transition:
 * fades in (and slides up 80px for fadeInUp) once 30% of it is visible.
 */
export default function FadeIn({ children, variant = 'fadeInUp', delay = 0, duration = 1500, distance = 80, className = '' }: FadeInProps) {
    const [ref, inView] = useInView<HTMLDivElement>(0.3);

    const hiddenTransform = variant === 'fadeInUp' ? `translateY(${distance}px)` : 'none';
    const style: CSSProperties = {
        opacity: inView ? 1 : 0,
        transform: inView ? 'none' : hiddenTransform,
        transition: `opacity ${duration}ms cubic-bezier(0, 0, 0.58, 1) ${delay}ms, transform ${duration}ms cubic-bezier(0, 0, 0.58, 1) ${delay}ms`,
    };

    return (
        <div ref={ref} className={className} style={style}>
            {children}
        </div>
    );
}
