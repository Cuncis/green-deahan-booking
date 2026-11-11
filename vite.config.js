import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            // resources/js/inertia.tsx powers only the Inertia+React homepage
            // (with its own Fuzio CSS); the rest of the app stays on app.js
            // (Alpine) + app.css untouched.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/inertia.tsx', 'resources/js/admin/statistik-pengunjung.tsx'],
            refresh: true,
        }),
        react(),
    ],
});
