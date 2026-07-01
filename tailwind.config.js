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
            },
            fontFamily: {
                display: ['Lora', ...defaultTheme.fontFamily.serif],
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                card: '16px',
            },
        },
    },

    // TIDAK ADA require('daisyui') DI SINI
    plugins: [forms],
};
