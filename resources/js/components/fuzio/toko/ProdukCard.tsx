import { router } from '@inertiajs/react';
import { useState } from 'react';
import GambarProduk from './GambarProduk';
import { rupiah, type ProdukPublik } from './types';

/** Fuzio .product-entry card with an "add to cart" button. */
export default function ProdukCard({ produk }: { produk: ProdukPublik }) {
    const [memproses, setMemproses] = useState(false);
    const tautan = `/toko/${produk.slug}`;

    const tambah = () =>
        router.post(
            '/keranjang',
            { product_id: produk.id, qty: 1 },
            { preserveScroll: true, onStart: () => setMemproses(true), onFinish: () => setMemproses(false) },
        );

    return (
        <div className="product-entry">
            <div className="product-thumb position-relative">
                <a href={tautan} tabIndex={-1} aria-hidden="true">
                    <GambarProduk src={produk.imageUrl} alt={produk.name} label={produk.categoryLabel} />
                </a>
            </div>
            <div className="product-txt">
                <span className="kategori">{produk.categoryLabel}</span>
                <h3>
                    <a href={tautan}>{produk.name}</a>
                </h3>
                <div className="price">{rupiah(produk.price)}</div>
                <button type="button" className="btn btn-primary" onClick={tambah} disabled={memproses}>
                    {memproses ? 'Menambahkan...' : 'Tambah ke Keranjang'}
                </button>
            </div>
        </div>
    );
}
