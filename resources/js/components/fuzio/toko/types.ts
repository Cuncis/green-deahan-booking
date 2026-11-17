/** Product data sent to customer pages (Product::dataPublik()). No cost price or supplier link. */
export interface ProdukPublik {
    id: number;
    name: string;
    slug: string;
    category: string;
    categoryLabel: string;
    /** Selling unit: pcs, m², set, sak, ... */
    unit: string;
    badge: string | null;
    description: string | null;
    imageUrl: string | null;
    /** All photos, main photo first (empty when the product has none). */
    images: string[];
    price: number;
    /** Struck-through "was" price, only when higher than price. */
    comparePrice: number | null;
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
    /** null while the shipping cost is still to be chosen (Biteship), set when it is a flat rate. */
    ongkir: number | null;
    ongkirDinamis: boolean;
    total: number;
}

export interface TokoSharedProps {
    jumlahKeranjang?: number;
    flash?: { success?: string | null; error?: string | null };
    [key: string]: unknown;
}

/** 95000 -> "Rp95.000" */
export const rupiah = (nilai: number): string => `Rp${Math.round(nilai).toLocaleString('id-ID')}`;
