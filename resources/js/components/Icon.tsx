import type { ReactElement } from 'react';

/**
 * Inline SVG icon set for the /v2 React page, ported 1:1 from the Blade
 * partials in resources/views/icons so the two pages stay visually
 * identical. Kept local to resources/js (not shared with Blade) since Blade
 * components can't be reused inside React.
 */
const paths: Record<string, ReactElement> = {
    layers: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M12 3 3 8l9 5 9-5-9-5z" />
            <path d="M3 12l9 5 9-5" />
            <path d="M3 16l9 5 9-5" />
        </svg>
    ),
    stadium: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <ellipse cx="12" cy="12" rx="10" ry="6" />
            <ellipse cx="12" cy="12" rx="5.5" ry="3" />
            <path d="M2 12h20" />
        </svg>
    ),
    'chart-trend': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M4 19V5M4 19h16" />
            <path d="M6 15l4-4 3 3 5-6" />
            <path d="M15 8h3v3" />
        </svg>
    ),
    'user-group': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 19c0-3 2.5-5 5.5-5s5.5 2 5.5 5" />
            <circle cx="17" cy="9" r="2.3" />
            <path d="M15.5 13.2c2.4.3 4 2 4 4.3" />
        </svg>
    ),
    parking: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <rect x="4" y="3" width="16" height="18" rx="2" />
            <path d="M9 17V7h3.5a3 3 0 0 1 0 6H9" />
        </svg>
    ),
    toilet: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M7 4h10v3a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V4Z" />
            <path d="M6 11c0-1 .5-2 2-2h8c1.5 0 2 1 2 2 0 5-2.5 9-6 9s-6-4-6-9Z" />
        </svg>
    ),
    shower: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M4 8a8 8 0 0 1 14-5" />
            <path d="M6 8h14" />
            <path d="M8 12v2M12 12v2M16 12v2M10 16v2M14 16v2" />
        </svg>
    ),
    'coffee-cup': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M5 8h11v6a5 5 0 0 1-5 5h-1a5 5 0 0 1-5-5V8Z" />
            <path d="M16 9h1.5a2.5 2.5 0 0 1 0 5H16" />
            <path d="M8 4c0 1-1 1-1 2M12 4c0 1-1 1-1 2" />
        </svg>
    ),
    building: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <rect x="5" y="3" width="14" height="18" rx="1" />
            <path d="M9 8h1M14 8h1M9 12h1M14 12h1M9 16h1M14 16h1" />
        </svg>
    ),
    'prayer-room': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M12 3v2M9 8a3 3 0 0 1 6 0c0 1.5-1 2-1 3.5H10c0-1.5-1-2-1-3.5Z" />
            <path d="M5 21v-6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v6" />
            <path d="M5 21h14M11 21v-4h2v4" />
        </svg>
    ),
    dumbbell: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M4 9v6M7 7v10M17 7v10M20 9v6M7 12h10" />
        </svg>
    ),
    'shopping-bag': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M6 8h12l-1 12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 8Z" />
            <path d="M9 8V6a3 3 0 0 1 6 0v2" />
        </svg>
    ),
    'storage-box': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M3 8l9-4 9 4-9 4-9-4Z" />
            <path d="M3 8v9l9 4 9-4V8" />
            <path d="M12 12v9" />
        </svg>
    ),
    plus: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M12 5v14M5 12h14" />
        </svg>
    ),
    'chevron-left': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M15 5l-7 7 7 7" />
        </svg>
    ),
    'chevron-right': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M9 5l7 7-7 7" />
        </svg>
    ),
    'basketball-ball': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 3v18M3 12h18M5.5 5.5c3 3 3 10 0 13M18.5 5.5c-3 3-3 10 0 13" />
        </svg>
    ),
    search: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
        </svg>
    ),
    expand: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <polyline points="15 3 21 3 21 9" />
            <polyline points="9 21 3 21 3 15" />
            <line x1="21" y1="3" x2="14" y2="10" />
            <line x1="3" y1="21" x2="10" y2="14" />
        </svg>
    ),
    'x-close': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M6 6l12 12M18 6 6 18" />
        </svg>
    ),
    'futsal-goal': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <rect x="3" y="7" width="18" height="11" rx="0.5" />
            <path d="M3 11h18M8 7v11M16 7v11" />
        </svg>
    ),
    'soccer-ball': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 8.2 15.3 10.6 14.1 14.5H9.9L8.7 10.6ZM12 8.2V4.5M9.9 14.5 6.6 17M14.1 14.5 17.4 17M15.3 10.6 19 9.3M8.7 10.6 5 9.3" />
        </svg>
    ),
    'padel-racket': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <ellipse cx="12" cy="9" rx="6" ry="7" />
            <path d="M12 16v6" />
        </svg>
    ),
    shuttlecock: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <circle cx="12" cy="18" r="2.5" />
            <path d="M12 15.5L8 6M12 15.5L12 5M12 15.5L16 6" />
        </svg>
    ),
    star: (
        <svg viewBox="0 0 24 24" fill="currentColor" className="h-full w-full">
            <path d="M12 2.5l2.9 6.3 6.8.7-5.1 4.7 1.4 6.8L12 17.6l-6 3.4 1.4-6.8-5.1-4.7 6.8-.7L12 2.5z" />
        </svg>
    ),
    'check-circle': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <circle cx="12" cy="12" r="9" />
            <path d="M8 12.5l2.5 2.5L16 9.5" />
        </svg>
    ),
    'arrow-right': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M5 12h14M13 6l6 6-6 6" />
        </svg>
    ),
    'chevron-down': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M6 9l6 6 6-6" />
        </svg>
    ),
    'whatsapp-logo': (
        <svg viewBox="0 0 24 24" fill="currentColor" className="h-full w-full">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.124.553 4.118 1.522 5.855L0 24l6.335-1.508A11.955 11.955 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.854 0-3.594-.48-5.112-1.32l-.366-.213-3.762.895.952-3.648-.239-.386A10 10 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z" />
        </svg>
    ),
    'build-hammer': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M14.5 6.5l3 3L8 19H5v-3l9.5-9.5z" />
            <path d="M13 8l3-3 3 3-3 3" />
            <path d="M16 5l3-3 3 3-3 3" />
        </svg>
    ),
    shield: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z" />
            <path d="M9 12l2 2 4-4" />
        </svg>
    ),
    wallet: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <rect x="3" y="6" width="18" height="13" rx="1.5" />
            <path d="M3 9.5h18" />
            <circle cx="16.5" cy="13.5" r="1.3" />
        </svg>
    ),
    zap: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z" />
        </svg>
    ),
    'location-pin': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M12 21s7-7.5 7-12a7 7 0 0 0-14 0c0 4.5 7 12 7 12z" />
            <circle cx="12" cy="9" r="2.5" />
        </svg>
    ),
    'phone-call': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <path d="M5 4h3l1.3 4-2 1.7a12.5 12.5 0 0 0 6 6l1.7-2 4 1.3v3a1.5 1.5 0 0 1-1.7 1.5c-3.9-.5-7.6-2.4-10.4-5.2S2.7 8.6 2.2 4.7A1.5 1.5 0 0 1 3.7 3H5z" />
        </svg>
    ),
    'mail-envelope': (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="M3.5 6.5 12 13l8.5-6.5" />
        </svg>
    ),
    clock: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" className="h-full w-full">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3.5 2" />
        </svg>
    ),
    'instagram-logo': (
        <svg viewBox="0 0 24 24" fill="currentColor" className="h-full w-full">
            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
        </svg>
    ),
    'facebook-logo': (
        <svg viewBox="0 0 24 24" fill="currentColor" className="h-full w-full">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
        </svg>
    ),
    'tiktok-logo': (
        <svg viewBox="0 0 24 24" fill="currentColor" className="h-full w-full">
            <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.28 8.28 0 0 0 4.84 1.55V6.79a4.85 4.85 0 0 1-1.07-.1z" />
        </svg>
    ),
    'x-logo': (
        <svg viewBox="0 0 24 24" fill="currentColor" className="h-full w-full">
            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z" />
        </svg>
    ),
    'youtube-logo': (
        <svg viewBox="0 0 24 24" fill="currentColor" className="h-full w-full">
            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
        </svg>
    ),
};

export default function Icon({
    name,
    size = 20,
    className = '',
}: {
    name: keyof typeof paths;
    size?: number;
    className?: string;
}) {
    return (
        <span
            className={`inline-flex items-center justify-center ${className}`}
            style={{ width: size, height: size }}
        >
            {paths[name]}
        </span>
    );
}
