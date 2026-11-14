import { router } from '@inertiajs/react';
import { useState } from 'react';
import GambarProduk from './GambarProduk';
import { rupiah, type ProdukPublik } from './types';

/**
 * Fuzio's .product-entry card, as in the Toko reference
 * (~/remove-later/fuzio/src/features/toko/TokoSection.tsx): badge, view and
 * add-to-cart buttons over the photo, price per unit with an optional
 * struck-through price. No star ratings (there are no real reviews yet).
 */
export default function ProdukCard({ produk }: { produk: ProdukPublik }) {
    const [memproses, setMemproses] = useState(false);
    const tautan = `/toko/${produk.slug}`;

    const tambah = () =>
        router.post('/keranjang', { product_id: produk.id, qty: 1 }, { preserveScroll: true, onStart: () => setMemproses(true), onFinish: () => setMemproses(false) });

    return (
        <div className="product-entry shadow-sm">
            <div className="product-thumb position-relative">
                {produk.badge && <div className="badge text-uppercase">{produk.badge}</div>}
                <a href={tautan} tabIndex={-1} aria-hidden="true">
                    <GambarProduk src={produk.imageUrl} alt={produk.name} label={produk.categoryLabel} />
                </a>
                <div className="product-cta">
                    <a href={tautan} title="Lihat detail" aria-label={`Lihat detail ${produk.name}`}>
                        <i className="feather-icon icon-eye" aria-hidden="true"></i>
                    </a>
                    <button type="button" title="Tambah ke keranjang" aria-label={`Tambah ${produk.name} ke keranjang`} onClick={tambah} disabled={memproses}>
                        <i className="feather-icon icon-shopping-bag" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div className="product-txt">
                <span className="kategori">{produk.categoryLabel}</span>
                <h3>
                    <a href={tautan}>{produk.name}</a>
                </h3>
                <div className="price">
                    {rupiah(produk.price)} <small>/{produk.unit}</small>
                    {produk.comparePrice && <del>{rupiah(produk.comparePrice)}</del>}
                </div>
                <button type="button" className="btn btn-primary gd-tambah-kartu d-lg-none" onClick={tambah} disabled={memproses}>
                    {memproses ? 'Menambahkan...' : 'Tambah ke Keranjang'}
                </button>
            </div>
        </div>
    );
}
