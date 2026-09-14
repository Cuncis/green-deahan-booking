import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

/**
 * Entry point for the /v2 Inertia + React page only. Every other route in
 * this app is server-rendered Blade + Livewire + Alpine (resources/js/app.js)
 * and never loads this bundle.
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
