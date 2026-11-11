/** 1250000 -> "Rp1.250.000" */
export const rupiah = (nilai: number): string => `Rp${Math.round(nilai).toLocaleString('id-ID')}`;

/** 1250 -> "1.250" */
export const angka = (nilai: number): string => nilai.toLocaleString('id-ID');
