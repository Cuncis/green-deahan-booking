/** Product data sent to customer pages (Product::dataPublik()). No cost price or supplier link. */
export interface ProdukPublik {
    id: number;
    name: string;
    slug: string;
    category: string;
    categoryLabel: string;
    description: string | null;
    imageUrl: string | null;
    price: number;
}

export interface BarisKeranjang extends ProdukPublik {
    qty: number;
    subtotal: number;
}

/** KeranjangService::ringkasan() */
export interface Keranjang {
    items: BarisKeranjang[];
    jumlahItem: number;
    subtotal: number;
    ongkir: number;
    total: number;
}

export interface TokoSharedProps {
    jumlahKeranjang?: number;
    flash?: { success?: string | null; error?: string | null };
    [key: string]: unknown;
}

/** 95000 -> "Rp95.000" */
export const rupiah = (nilai: number): string => `Rp${Math.round(nilai).toLocaleString('id-ID')}`;
