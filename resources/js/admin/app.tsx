import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

/**
 * React admin app: tenant admin (/admin) and superadmin (/superadmin).
 * Root view resources/views/admin.blade.php (HandleInertiaRequests::rootView()).
 * Pages live in resources/js/admin/pages and wrap themselves in AdminLayout.
 */
createInertiaApp({
    title: (judul) => (judul ? `${judul} · Admin Green Deahan` : 'Admin Green Deahan'),
    // Each page is its own chunk, loaded on demand (e.g. the chart library
    // only downloads on the superadmin dashboard).
    resolve: async (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx');
        const muat = pages[`./pages/${name}.tsx`];

        if (!muat) {
            throw new Error(`Halaman admin tidak ditemukan: ./pages/${name}.tsx`);
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
