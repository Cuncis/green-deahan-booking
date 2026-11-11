import type { ReactNode } from 'react';
import { cn } from '../../lib/utils';
import { Label } from '../ui/label';

interface FieldProps {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    wajib?: boolean;
    className?: string;
    children: ReactNode;
}

/** Label above the control, optional hint, and the server validation error below it. */
export default function Field({ id, label, error, hint, wajib, className, children }: FieldProps) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label htmlFor={id}>
                {label}
                {wajib && <span className="text-destructive"> *</span>}
            </Label>
            {children}
            {hint && !error && <p className="text-xs text-muted-foreground">{hint}</p>}
            {error && (
                <p className="text-sm text-destructive" role="alert">
                    {error}
                </p>
            )}
        </div>
    );
}
