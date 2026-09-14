import { useId } from 'react';

export type ConceptVariant = 'single-focus' | 'multi-court' | 'lifestyle-hub' | 'completed-badge';

const stroke = { fill: 'none', strokeWidth: 2.2, strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const };

/**
 * Small hand-built illustrations (gradient fill, animated stroke draw-in via the
 * `.draw-path`/`.reveal.visible` rule in PageStyles, plus a pulsing glow disc) used
 * for the konsep page's featured concept tiles. Deliberately more detailed than the
 * flat single-path icons in Icon.tsx, which stay simple for compact inline UI use.
 */
export default function ConceptArt({ variant, size = 56, className = '' }: { variant: ConceptVariant; size?: number; className?: string }) {
    const gradId = `ca-grad-${useId()}`;

    return (
        <svg viewBox="0 0 64 64" width={size} height={size} className={className} aria-hidden>
            <defs>
                <linearGradient id={gradId} x1="4" y1="4" x2="60" y2="60" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stopColor="#4caf50" />
                    <stop offset="100%" stopColor="#004d00" />
                </linearGradient>
            </defs>

            {variant === 'single-focus' && (
                <g stroke={`url(#${gradId})`} {...stroke}>
                    <circle cx="32" cy="32" r="23" fill={`url(#${gradId})`} stroke="none" opacity={0.12} className="pulse-glow" />
                    <rect x="13" y="19" width="38" height="26" rx="3" pathLength={300} className="draw-path" />
                    <line x1="32" y1="19" x2="32" y2="45" pathLength={300} className="draw-path" style={{ transitionDelay: '0.15s' }} />
                    <circle cx="32" cy="32" r="4.2" fill={`url(#${gradId})`} stroke="none" />
                </g>
            )}

            {variant === 'multi-court' && (
                <g stroke={`url(#${gradId})`} {...stroke}>
                    <circle cx="32" cy="32" r="23" fill={`url(#${gradId})`} stroke="none" opacity={0.1} className="pulse-glow" />
                    <rect x="7" y="10" width="21" height="15" rx="2.5" pathLength={300} className="draw-path" />
                    <rect x="36" y="13" width="21" height="15" rx="2.5" pathLength={300} className="draw-path" style={{ transitionDelay: '0.1s' }} />
                    <rect x="18" y="36" width="21" height="15" rx="2.5" pathLength={300} className="draw-path" style={{ transitionDelay: '0.2s' }} />
                    <path d="M22 25 L26 36 M46 28 L33 38" strokeDasharray="3 4" opacity={0.55} />
                </g>
            )}

            {variant === 'lifestyle-hub' && (
                <g stroke={`url(#${gradId})`} {...stroke}>
                    <circle cx="32" cy="32" r="23" fill={`url(#${gradId})`} stroke="none" opacity={0.1} className="pulse-glow" />
                    <path d="M14 46V26l18-11 18 11v20Z" pathLength={300} className="draw-path" />
                    <path d="M14 46h36" pathLength={300} className="draw-path" style={{ transitionDelay: '0.15s' }} />
                    <rect x="26" y="31" width="12" height="15" rx="1.5" opacity={0.6} />
                    <circle cx="48" cy="19" r="5.5" fill={`url(#${gradId})`} stroke="none" opacity={0.22} className="pulse-glow" />
                    <path d="M45.5 16.5c0 2 2 2 2 4M49.5 16.5c0 2 2 2 2 4" opacity={0.85} />
                </g>
            )}

            {variant === 'completed-badge' && (
                <g stroke={`url(#${gradId})`} {...stroke}>
                    <circle cx="32" cy="32" r="23" fill={`url(#${gradId})`} stroke="none" opacity={0.12} className="pulse-glow" />
                    <path d="M32 7l20 7.5v13C52 40 44 48 32 53 20 48 12 40 12 27.5v-13Z" pathLength={300} className="draw-path" />
                    <path d="M22.5 32.5l6.5 6.5 13-14" pathLength={300} className="draw-path" style={{ transitionDelay: '0.2s' }} />
                </g>
            )}
        </svg>
    );
}
