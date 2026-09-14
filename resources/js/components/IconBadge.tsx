import type { ReactNode } from 'react';

type Tone = 'light' | 'dark' | 'solid' | 'white';

const toneClass: Record<Tone, string> = {
    light: 'bg-gradient-to-br from-brand-50 to-brand-100 text-brand ring-1 ring-brand-200/70',
    dark: 'bg-white/5 text-brand-300 ring-1 ring-white/10',
    solid: 'bg-gradient-to-br from-brand-400 to-brand-dark text-white shadow-lg shadow-brand-dark/25',
    white: 'bg-white text-brand shadow-sm ring-1 ring-stone-100',
};

/** Gradient badge with a soft pulsing glow behind the icon, used for every non-featured icon spot on /v2 pages. */
export default function IconBadge({
    children,
    tone = 'light',
    size = 56,
    floaty = false,
    className = '',
}: {
    children: ReactNode;
    tone?: Tone;
    size?: number;
    floaty?: boolean;
    className?: string;
}) {
    return (
        <span
            className={`relative inline-flex flex-shrink-0 items-center justify-center rounded-2xl transition-transform duration-300 ${toneClass[tone]} ${floaty ? 'float-anim' : ''} ${className}`}
            style={{ width: size, height: size }}
        >
            <span aria-hidden className="pointer-events-none absolute inset-0 rounded-2xl bg-brand/25 blur-lg pulse-glow" />
            <span className="relative">{children}</span>
        </span>
    );
}
