/** Article card data from BlogController::kartu() (artikel table). */
export interface ArtikelKartu {
    id: number;
    judul: string;
    slug: string;
    kategori: string;
    ringkasan: string;
    fotoUrl: string | null;
    menitBaca: number;
}

/** Full article from BlogController::show(). `konten` is HTML written in /superadmin/artikel. */
export interface ArtikelDetail extends ArtikelKartu {
    konten: string;
    tanggal: string;
}

export const BLOG_PATH = '/blog';

export const articlePath = (slug: string): string => `${BLOG_PATH}/${slug}`;
