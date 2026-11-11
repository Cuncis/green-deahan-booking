import { Toaster as Sonner } from 'sonner';

/** Toast host (shadcn/ui "sonner"), styled with the admin palette. */
function Toaster() {
    return (
        <Sonner
            position="top-center"
            closeButton
            toastOptions={{
                classNames: {
                    toast: 'rounded-xl border border-border bg-card text-foreground shadow-lg font-sans',
                    description: 'text-ink-mid',
                    success: '[&_[data-icon]]:text-green',
                    error: '[&_[data-icon]]:text-danger',
                },
            }}
        />
    );
}

export { Toaster };
