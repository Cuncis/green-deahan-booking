import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            // resources/js/inertia.tsx powers only the /v2 Inertia+React page;
            // the rest of the app stays on app.js (Alpine) + app.css untouched.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/inertia.tsx'],
            refresh: true,
        }),
        react(),
    ],
});
