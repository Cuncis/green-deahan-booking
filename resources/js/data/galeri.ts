/** Gallery data from GaleriController::index() (galeri_items table). */
export interface GaleriKategori {
    /** 'semua' or a galeri_items.kategori value (futsal, minisoccer, padel, badminton, proses) */
    id: string;
    label: string;
}

export interface GaleriItem {
    id: number;
    kategori: string;
    judul: string;
    kota: string;
    material: string;
    deskripsi: string;
    foto: string;
    /** tampilan_besar: shown as a big tile */
    besar: boolean;
}
