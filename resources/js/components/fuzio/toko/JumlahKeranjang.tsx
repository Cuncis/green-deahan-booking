import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Quantity control for one cart line: -/+ buttons, or type a number (saved
 * when the field loses focus or Enter is pressed). Only the product id and
 * quantity go to the server.
 */
export default function JumlahKeranjang({ id, nama, qty, maks }: { id: number; nama: string; qty: number; maks: number }) {
    const [nilai, setNilai] = useState(String(qty));

    useEffect(() => setNilai(String(qty)), [qty]);

    const simpan = (baru: number) => {
        const bersih = Math.min(maks, Math.max(1, Number.isFinite(baru) ? Math.floor(baru) : qty));
        setNilai(String(bersih));
        if (bersih !== qty) {
            router.patch(`/keranjang/${id}`, { qty: bersih }, { preserveScroll: true, only: ['keranjang', 'jumlahKeranjang'] });
        }
    };

    return (
        <div className="cart-quanty gd-qty" role="group" aria-label={`Jumlah ${nama}`}>
            <button type="button" className="qty-minus" onClick={() => simpan(qty - 1)} disabled={qty <= 1} aria-label="Kurangi jumlah">
                <i className="feather-icon icon-minus" aria-hidden="true"></i>
            </button>
            <input
                className="qty-count"
                type="number"
                inputMode="numeric"
                min={1}
                max={maks}
                value={nilai}
                aria-label="Jumlah"
                onChange={(event) => setNilai(event.target.value)}
                onFocus={(event) => event.currentTarget.select()}
                onBlur={() => simpan(Number(nilai))}
                onKeyDown={(event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        simpan(Number(nilai));
                    }
                }}
            />
            <button type="button" className="qty-plus" onClick={() => simpan(qty + 1)} disabled={qty >= maks} aria-label="Tambah jumlah">
                <i className="feather-icon icon-plus" aria-hidden="true"></i>
            </button>
        </div>
    );
}
