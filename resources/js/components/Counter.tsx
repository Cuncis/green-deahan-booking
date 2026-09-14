import { useEffect, useRef, useState } from 'react';

/** Counts up to `target` once its container scrolls into view. */
export default function Counter({
    target,
    suffix = '',
    decimals = 0,
    className = 'mb-1 text-4xl font-bold',
}: {
    target: number;
    suffix?: string;
    decimals?: number;
    className?: string;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const [value, setValue] = useState(0);

    useEffect(() => {
        const el = ref.current;
        if (!el) return;
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (!entry.isIntersecting) return;
                const factor = 10 ** decimals;
                const targetScaled = Math.round(target * factor);
                const step = Math.ceil(targetScaled / 50);
                const timer = setInterval(() => {
                    setValue((current) => {
                        const next = Math.min(current + step, targetScaled);
                        if (next >= targetScaled) clearInterval(timer);
                        return next;
                    });
                }, 28);
                observer.unobserve(el);
            },
            { threshold: 0.5 },
        );
        observer.observe(el);
        return () => observer.disconnect();
    }, [target, decimals]);

    const shown = decimals > 0 ? (value / 10 ** decimals).toFixed(decimals) : value;

    return (
        <div ref={ref} className={className}>
            {shown}
            {value >= target * 10 ** decimals ? suffix : ''}
        </div>
    );
}
