import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

/**
 * Customer booking page on a tenant's domain.
 * Root view resources/views/booking.blade.php (HandleInertiaRequests::rootView()).
 * Kept apart from the admin bundle so customers on phones only download this page.
 */
createInertiaApp({
    title: (judul) => judul || 'Booking Lapangan',
    resolve: async (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx');
        const muat = pages[`./pages/${name}.tsx`];

        if (!muat) {
            throw new Error(`Halaman booking tidak ditemukan: ./pages/${name}.tsx`);
        }

        return ((await muat()) as { default: unknown }).default as never;
    },
    setup({ el, App, props }) {
        createRoot(el!).render(<App {...props} />);
    },
    progress: {
        color: '#3A6B4A',
        delay: 150,
    },
});
