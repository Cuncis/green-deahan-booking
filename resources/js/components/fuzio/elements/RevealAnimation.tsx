import type { ReactNode } from 'react';
import useInView from './useInView';

interface RevealAnimationProps {
    children: ReactNode;
    className?: string;
    /** ms */
    duration?: number;
}

/**
 * Port of Fuzio's RevealAnimation "revealLeft" (react-awesome-reveal):
 * a left-to-right curtain wipe (see .gd-reveal-left in gd-react.css).
 */
export default function RevealAnimation({ children, className = '', duration = 1500 }: RevealAnimationProps) {
    const [ref, inView] = useInView<HTMLDivElement>(0.3);

    return (
        <div ref={ref} className={className}>
            <div className={inView ? 'gd-reveal-left' : 'gd-reveal-hidden'} style={{ animationDuration: `${duration}ms` }}>
                {children}
            </div>
        </div>
    );
}
