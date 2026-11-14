import type { ReactNode } from 'react';

/** Line icon in the same style as resources/views/icons/*.blade.php (24x24, 1.6 stroke, currentColor). */
function LineIcon({ size = 30, children }: { size?: number; children: ReactNode }) {
    return (
        <svg viewBox="0 0 24 24" width={size} height={size} fill="none" stroke="currentColor" strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            {children}
        </svg>
    );
}

/** Shield with a check, same as resources/views/icons/shield.blade.php. */
export function ShieldCheckIcon() {
    return (
        <LineIcon size={34}>
            <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z" />
            <path d="M9 12l2 2 4-4" />
        </LineIcon>
    );
}

/** Itemized receipt (outline from Lucide "receipt", ISC license), for a detailed price breakdown (RAB). */
export function ReceiptIcon() {
    return (
        <LineIcon>
            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z" />
            <path d="M8 8h8" />
            <path d="M8 12h8" />
            <path d="M8 16h5" />
        </LineIcon>
    );
}

/** Checklist, same as resources/views/icons/checklist.blade.php. */
export function ChecklistIcon() {
    return (
        <LineIcon>
            <rect x="3.5" y="4" width="17" height="16" rx="1.5" />
            <path d="M7.5 9.5l1.5 1.5 3-3" />
            <line x1="13.5" y1="10" x2="17" y2="10" />
            <path d="M7.5 15.5l1.5 1.5 3-3" />
            <line x1="13.5" y1="16" x2="17" y2="16" />
        </LineIcon>
    );
}

/** Stadium / field, same as resources/views/icons/stadium.blade.php. */
export function StadiumIcon() {
    return (
        <LineIcon size={34}>
            <ellipse cx="12" cy="12" rx="10" ry="6" />
            <ellipse cx="12" cy="12" rx="5.5" ry="3" />
            <path d="M2 12h20" />
        </LineIcon>
    );
}

/** Circle with a check (Lucide "circle-check", ISC license): payment received. */
export function CheckCircleIcon({ size = 48 }: { size?: number }) {
    return (
        <LineIcon size={size}>
            <circle cx="12" cy="12" r="10" />
            <path d="m9 12 2 2 4-4" />
        </LineIcon>
    );
}

/** Clock (Lucide "clock", ISC license): waiting for payment confirmation. */
export function ClockIcon({ size = 48 }: { size?: number }) {
    return (
        <LineIcon size={size}>
            <circle cx="12" cy="12" r="10" />
            <path d="M12 6v6l4 2" />
        </LineIcon>
    );
}

/** Triangle with an exclamation mark (Lucide "triangle-alert", ISC license). */
export function AlertIcon({ size = 48 }: { size?: number }) {
    return (
        <LineIcon size={size}>
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
            <path d="M12 9v4" />
            <path d="M12 17h.01" />
        </LineIcon>
    );
}
