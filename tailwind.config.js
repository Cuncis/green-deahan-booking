import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                cream: { DEFAULT: '#F5F0E8', dark: '#EDE5D5', deep: '#E0D5C0' },
                sand: '#C9B99A',
                brown: { DEFAULT: '#7C5C3A', light: '#A07850' },
                green: { DEFAULT: '#3A6B4A', mid: '#4E8B60', light: '#6AAF7C', pale: '#D6EAD9' },
                gold: { DEFAULT: '#C99A3A', pale: '#F5E9D0' },
                plum: { DEFAULT: '#6B4A6B', pale: '#E9DEE9' },
                ink: { DEFAULT: '#2A2018', mid: '#5C4A30', soft: '#8A7260' },
                danger: { DEFAULT: '#C0392B', pale: '#F7DEDC' },
                amber: { DEFAULT: '#B8860B', pale: '#FBF1D8' },
                // Brand hijau resmi greendeahan.com (situs korporat lama,
                // beda dari palet 'green' di atas yang dipakai khusus untuk
                // demo dashboard booking). Dipakai untuk homepage utama
                // platform di resources/views/pages/home.blade.php.
                brand: {
                    50: '#f3faf3',
                    100: '#e8f5e9',
                    200: '#c8e6c9',
                    300: '#94c99a',
                    400: '#4caf50',
                    DEFAULT: '#006400',
                    dark: '#004d00',
                    light: '#e8f5e9',
                },
            },
            fontFamily: {
                display: ['Lora', ...defaultTheme.fontFamily.serif],
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                // Font asli homepage greendeahan.com (Inter untuk body, DM
                // Mono untuk heading), lihat pages/home.blade.php.
                marketing: ['Inter', ...defaultTheme.fontFamily.sans],
                'marketing-display': ['Manrope', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                card: '16px',
            },
        },
    },

    // TIDAK ADA require('daisyui') DI SINI
    plugins: [forms],
};
