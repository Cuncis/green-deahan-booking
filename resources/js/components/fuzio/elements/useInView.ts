import { useEffect, useRef, useState, type RefObject } from 'react';

/**
 * True once at least `amount` (0..1) of the element is inside the viewport.
 * Lightweight stand-in for framer-motion's useInView / whileInView.
 */
export default function useInView<T extends Element>(amount = 0.3, once = true): [RefObject<T | null>, boolean] {
    const ref = useRef<T | null>(null);
    const [inView, setInView] = useState(false);

    useEffect(() => {
        const element = ref.current;
        if (!element) {
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setInView(true);
                    if (once) {
                        observer.disconnect();
                    }
                } else if (!once) {
                    setInView(false);
                }
            },
            { threshold: amount },
        );

        observer.observe(element);

        return () => observer.disconnect();
    }, [amount, once]);

    return [ref, inView];
}
