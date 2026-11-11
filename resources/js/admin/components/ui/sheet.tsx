import * as SheetPrimitive from '@radix-ui/react-dialog';
import { cva, type VariantProps } from 'class-variance-authority';
import { X } from 'lucide-react';
import * as React from 'react';
import { cn } from '../../lib/utils';

const Sheet = SheetPrimitive.Root;
const SheetTrigger = SheetPrimitive.Trigger;
const SheetClose = SheetPrimitive.Close;
const SheetPortal = SheetPrimitive.Portal;

const SheetOverlay = React.forwardRef<React.ElementRef<typeof SheetPrimitive.Overlay>, React.ComponentPropsWithoutRef<typeof SheetPrimitive.Overlay>>(
    ({ className, ...props }, ref) => (
        <SheetPrimitive.Overlay
            ref={ref}
            className={cn(
                'fixed inset-0 z-50 bg-ink/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
                className,
            )}
            {...props}
        />
    ),
);
SheetOverlay.displayName = SheetPrimitive.Overlay.displayName;

const sheetVariants = cva(
    'fixed z-50 flex flex-col shadow-lg transition ease-in-out data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:duration-200 data-[state=open]:duration-300',
    {
        variants: {
            side: {
                left: 'inset-y-0 left-0 h-full w-[min(18rem,85vw)] data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left',
                right: 'inset-y-0 right-0 h-full w-[min(18rem,85vw)] data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right',
            },
        },
        defaultVariants: { side: 'left' },
    },
);

interface SheetContentProps extends React.ComponentPropsWithoutRef<typeof SheetPrimitive.Content>, VariantProps<typeof sheetVariants> {
    tutupClassName?: string;
}

const SheetContent = React.forwardRef<React.ElementRef<typeof SheetPrimitive.Content>, SheetContentProps>(
    ({ side = 'left', className, tutupClassName, children, ...props }, ref) => (
        <SheetPortal>
            <SheetOverlay />
            <SheetPrimitive.Content ref={ref} className={cn(sheetVariants({ side }), className)} {...props}>
                {children}
                <SheetPrimitive.Close
                    className={cn(
                        'absolute right-2 top-3 flex size-11 items-center justify-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                        tutupClassName,
                    )}
                >
                    <X className="size-5" />
                    <span className="sr-only">Tutup menu</span>
                </SheetPrimitive.Close>
            </SheetPrimitive.Content>
        </SheetPortal>
    ),
);
SheetContent.displayName = SheetPrimitive.Content.displayName;

const SheetTitle = React.forwardRef<React.ElementRef<typeof SheetPrimitive.Title>, React.ComponentPropsWithoutRef<typeof SheetPrimitive.Title>>(({ className, ...props }, ref) => (
    <SheetPrimitive.Title ref={ref} className={cn('font-display text-base font-semibold', className)} {...props} />
));
SheetTitle.displayName = SheetPrimitive.Title.displayName;

const SheetDescription = React.forwardRef<React.ElementRef<typeof SheetPrimitive.Description>, React.ComponentPropsWithoutRef<typeof SheetPrimitive.Description>>(
    ({ className, ...props }, ref) => <SheetPrimitive.Description ref={ref} className={cn('text-xs', className)} {...props} />,
);
SheetDescription.displayName = SheetPrimitive.Description.displayName;

export { Sheet, SheetClose, SheetContent, SheetDescription, SheetPortal, SheetTitle, SheetTrigger };
