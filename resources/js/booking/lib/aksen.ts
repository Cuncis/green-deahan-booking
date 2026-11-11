import type { CSSProperties } from 'react';

const HEKS = /^#([0-9a-f]{6})$/i;

/**
 * The tenant's brand colour (Pengaturan > Warna utama) as CSS variables.
 * Text on it is white or dark depending on how light the colour is, so a
 * tenant picking a light yellow still gets readable buttons.
 */
export function variabelAksen(warna: string): CSSProperties {
    const cocok = HEKS.exec(warna);
    const heks = cocok ? cocok[1] : '3A6B4A';
    const [r, g, b] = [0, 2, 4].map((i) => parseInt(heks.slice(i, i + 2), 16) / 255);
    const linear = (c: number) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
    const luminans = 0.2126 * linear(r) + 0.7152 * linear(g) + 0.0722 * linear(b);

    return {
        '--aksen': `#${heks}`,
        '--aksen-teks': luminans > 0.45 ? '#2C1F14' : '#FFFFFF',
        '--aksen-pucat': `#${heks}1A`,
    } as CSSProperties;
}

/** 1250000 -> "Rp1.250.000" */
export const rupiah = (nilai: number): string => `Rp${Math.round(nilai).toLocaleString('id-ID')}`;

export const hanyaAngka = (teks: string): string => teks.replace(/\D/g, '');
