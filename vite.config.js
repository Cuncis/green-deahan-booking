import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            // resources/js/inertia.tsx: public React pages (Fuzio CSS).
            // resources/js/admin/app.tsx: React admin app (tenant admin +
            // superadmin, Tailwind + shadcn/ui). app.js: Blade/Alpine pages.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/inertia.tsx', 'resources/js/admin/app.tsx'],
            refresh: true,
        }),
        react(),
    ],
});
