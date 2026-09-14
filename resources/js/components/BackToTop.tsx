import { useEffect, useState } from 'react';

/** Floating button that fades in after scrolling and smooth-scrolls back to top. */
export default function BackToTop() {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const onScroll = () => setVisible(window.scrollY > 480);
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <button
            type="button"
            onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
            aria-label="Kembali ke atas"
            className={`fixed bottom-7 left-7 z-[998] flex h-11 w-11 items-center justify-center rounded-full bg-stone-900 text-white shadow-lg transition-all duration-300 hover:-translate-y-1 hover:bg-brand ${
                visible ? 'translate-y-0 opacity-100' : 'pointer-events-none translate-y-3 opacity-0'
            }`}
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" className="h-5 w-5">
                <path d="M12 19V5M5 12l7-7 7 7" />
            </svg>
        </button>
    );
}
