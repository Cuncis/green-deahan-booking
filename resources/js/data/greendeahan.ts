/**
 * Green Deahan Sport brand & contact data for the React homepage.
 * Ported from the Fuzio clone (~/remove-later/fuzio/src/data/greendeahan.ts).
 */

export const GD_CDN = 'https://cdn.libradigital.id/site-assets';

export const GD_LOGO = `${GD_CDN}/full-logo-02.png`;
/** White-text version for dark backgrounds (header before scrolling). */
export const GD_LOGO_LIGHT = `${GD_CDN}/logo-green-bg.png`;

export const GD_PHONE_DISPLAY = '+62 813-5757-0064';
export const GD_PHONE_TEL = '+6281357570064';
export const GD_EMAIL = 'rumput1927@gmail.com';
export const GD_HOURS = 'Senin-Sabtu, 06.00-23.00 WIB';

const GD_WA_NUMBER = '6281357570064';

/** WhatsApp link, optionally with a pre-filled message. */
export const waLink = (message?: string): string =>
    message
        ? `https://wa.me/${GD_WA_NUMBER}?text=${encodeURIComponent(message)}`
        : `https://wa.me/${GD_WA_NUMBER}`;

export interface Testimonial {
    id: number;
    rating: number;
    text: string;
    name: string;
    role: string;
    avatar: string;
}

/** Testimoni klien (section "Apa Kata Klien Kami?"). */
export const gdTestimonials: Testimonial[] = [
    {
        id: 1,
        rating: 5,
        text: 'Awalnya ragu karena project dari nol, tapi GreenDeahan handle semuanya dengan profesional. Lapangan futsal saya sudah balik modal dalam 8 bulan.',
        name: 'Budi Santoso',
        role: 'Owner Futsal, Jakarta Selatan',
        avatar: `${GD_CDN}/foto1.webp`,
    },
    {
        id: 2,
        rating: 5,
        text: 'Tim yang sangat responsif! Konsultasi gratisnya detail banget, bantu pilih material sesuai budget. Lapangan mini soccer saya jadi yang paling ramai di daerah.',
        name: 'Ahmad Wijaya',
        role: 'Pengusaha, Surabaya',
        avatar: `${GD_CDN}/foto2.jpg`,
    },
    {
        id: 3,
        rating: 5,
        text: 'Proyek lapangan padel pertama di kota kami dikerjakan GreenDeahan. Dari desain sampai konstruksi standar internasional. Garansi dan after sales-nya juga top!',
        name: 'Ricky Pratama',
        role: 'Investor, Medan',
        avatar: `${GD_CDN}/foto3.jpg`,
    },
];

export interface MenuItem {
    label: string;
    path: string;
}

/** Same pages as the Blade site nav (resources/views/components/site-nav.blade.php). */
export const menuData: MenuItem[] = [
    { label: 'Beranda', path: '/' },
    { label: 'Konsep', path: '/konsep' },
    { label: 'Galeri', path: '/galeri' },
    { label: 'Blog', path: '/blog' },
    { label: 'Harga', path: '/harga' },
    { label: 'Kontak', path: '/kontak' },
];

export interface SocialLink {
    label: string;
    handle: string;
    href: string;
    /** SVG path (24x24 viewBox), same icons as resources/views/icons/*-logo.blade.php */
    iconPath: string;
}

/** Same accounts as the Blade footer (resources/views/components/site-footer.blade.php). */
export const socialLinks: SocialLink[] = [
    {
        label: 'Instagram',
        handle: '@green_deahan1927',
        href: 'https://www.instagram.com/green_deahan1927',
        iconPath: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z',
    },
    {
        label: 'Facebook',
        handle: 'greendeahan',
        href: 'https://www.facebook.com/greendeahan/',
        iconPath: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
    },
    {
        label: 'TikTok',
        handle: '@green_deahan',
        href: 'https://www.tiktok.com/@green_deahan',
        iconPath: 'M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.28 8.28 0 0 0 4.84 1.55V6.79a4.85 4.85 0 0 1-1.07-.1z',
    },
    {
        label: 'X / Twitter',
        handle: '@greendeahan1927',
        href: 'https://x.com/greendeahan1927',
        iconPath: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.746l7.73-8.835L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z',
    },
    {
        label: 'YouTube',
        handle: '@green_deahan',
        href: 'https://www.youtube.com/@green_deahan',
        iconPath: 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
    },
];
