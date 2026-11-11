import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

/**
 * Entry point for the public corporate-site React pages (resources/js/pages).
 * The admin area (resources/js/admin) and the tenant booking page
 * (resources/js/booking) have their own entries; remaining Blade pages use
 * Alpine from resources/js/app.js.
 */
createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });
        const page = pages[`./pages/${name}.tsx`] as { default: unknown };

        if (!page) {
            throw new Error(`Inertia page not found: ./pages/${name}.tsx`);
        }

        return page.default as never;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
