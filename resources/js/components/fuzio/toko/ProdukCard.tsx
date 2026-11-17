import { router, usePage } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import GambarProduk from './GambarProduk';
import { rupiah, type ProdukPublik } from './types';

interface PropsKeranjang {
    qtyKeranjang: Record<string, number>;
    maksQtyProduk: number;
    [kunci: string]: unknown;
}

/** Only these props change when the cart does, so the list page is not reloaded. */
const HANYA_KERANJANG = ['qtyKeranjang', 'jumlahKeranjang', 'flash'];

function Ikon({ children }: { children: ReactNode }) {
    return (
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            {children}
        </svg>
    );
}

/**
 * Product card for the /toko grid: badge, photo, name, price per unit with an
 * optional struck-through price. Below the price sits "Tambah ke Keranjang";
 * once the product is in the cart it becomes a minus / number / plus counter
 * (minus at 1 takes it out of the cart). No star ratings (no real reviews yet).
 */
export default function ProdukCard({ produk }: { produk: ProdukPublik }) {
    const { qtyKeranjang, maksQtyProduk } = usePage<PropsKeranjang>().props;
    const qty = qtyKeranjang?.[produk.id] ?? 0;
    const [memproses, setMemproses] = useState(false);
    const [teks, setTeks] = useState(String(qty));
    const tautan = `/toko/${produk.slug}`;

    // Keep the number field in step with the cart after every server response.
    useEffect(() => setTeks(String(qty)), [qty]);

    const opsi = { preserveScroll: true, preserveState: true, only: HANYA_KERANJANG, onStart: () => setMemproses(true), onFinish: () => setMemproses(false) };

    const tambah = () => router.post('/keranjang', { product_id: produk.id, qty: 1 }, opsi);

    const ubah = (jumlah: number) => {
        if (memproses) return;
        if (jumlah <= 0) {
            router.delete(`/keranjang/${produk.id}`, opsi);
        } else if (jumlah !== qty) {
            router.patch(`/keranjang/${produk.id}`, { qty: Math.min(jumlah, maksQtyProduk) }, opsi);
        }
    };

    // Typing: 0 or empty removes the item, a number above the limit is clamped, anything else is a plain number.
    const simpanKetikan = () => {
        const angka = Math.floor(Number(teks));
        if (teks.trim() === '' || !Number.isFinite(angka)) {
            setTeks(String(qty));
            return;
        }
        if (angka > maksQtyProduk) setTeks(String(maksQtyProduk));
        ubah(Math.min(angka, maksQtyProduk));
    };

    return (
        <div className="product-entry shadow-sm">
            <div className="product-thumb position-relative">
                {produk.badge && <div className="badge text-uppercase">{produk.badge}</div>}
                <a href={tautan} tabIndex={-1} aria-hidden="true">
                    <GambarProduk src={produk.imageUrl} alt={produk.name} label={produk.categoryLabel} />
                </a>
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
                {qty === 0 ? (
                    <button type="button" className="btn btn-primary gd-tambah-kartu" onClick={tambah} disabled={memproses} aria-label={`Tambah ${produk.name} ke keranjang`}>
                        <Ikon>
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                            <path d="M3 6h18" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </Ikon>
                        {memproses ? (
                            'Menambahkan...'
                        ) : (
                            <>
                                <span className="d-none d-sm-inline">Tambah ke Keranjang</span>
                                <span className="d-sm-none">Tambah</span>
                            </>
                        )}
                    </button>
                ) : (
                    <div className="gd-qty-kartu" role="group" aria-label={`Jumlah ${produk.name} di keranjang`}>
                        <button
                            type="button"
                            onClick={() => ubah(qty - 1)}
                            disabled={memproses}
                            aria-label={qty === 1 ? `Hapus ${produk.name} dari keranjang` : `Kurangi jumlah ${produk.name}`}
                        >
                            <Ikon>
                                <path d="M5 12h14" />
                            </Ikon>
                        </button>
                        <input
                            type="number"
                            inputMode="numeric"
                            min={0}
                            max={maksQtyProduk}
                            value={teks}
                            disabled={memproses}
                            aria-label={`Jumlah ${produk.name}`}
                            onChange={(event) => setTeks(event.target.value)}
                            onFocus={(event) => event.currentTarget.select()}
                            onBlur={simpanKetikan}
                            onKeyDown={(event) => event.key === 'Enter' && event.currentTarget.blur()}
                        />
                        <button type="button" onClick={() => ubah(qty + 1)} disabled={memproses || qty >= maksQtyProduk} aria-label={`Tambah jumlah ${produk.name}`}>
                            <Ikon>
                                <path d="M12 5v14M5 12h14" />
                            </Ikon>
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
