import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';
import { cn } from '../../lib/utils';

/** Same colors as the Blade <x-badge> and <x-paket-badge> components. */
const badgeVariants = cva('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold', {
    variants: {
        variant: {
            confirmed: 'bg-green-pale text-green',
            pending: 'bg-amber-pale text-amber',
            cancelled: 'bg-danger-pale text-danger',
            info: 'bg-cream-deep text-ink-mid',
            basic: 'bg-cream-deep uppercase text-ink-mid',
            pro: 'bg-gold uppercase text-white',
            premium: 'bg-plum uppercase text-white',
        },
    },
    defaultVariants: {
        variant: 'info',
    },
});

export interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement>, VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
    return <span className={cn(badgeVariants({ variant }), className)} {...props} />;
}

/** Booking status -> badge color, as on the Blade dashboard. */
export const varianStatusBooking = (status: string): BadgeProps['variant'] =>
    (({
        menunggu: 'pending',
        dikonfirmasi: 'confirmed',
        selesai: 'confirmed',
        dibatalkan: 'cancelled',
    })[status] as BadgeProps['variant']) ?? 'info';

/** Tenant plan -> badge color. */
export const varianPaket = (paket: string): BadgeProps['variant'] => (paket === 'premium' ? 'premium' : paket === 'pro' ? 'pro' : 'basic');

export { Badge, badgeVariants };
