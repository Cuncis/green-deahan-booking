import { router } from '@inertiajs/react';
import { useCallback, useEffect, useLayoutEffect, useRef, useState, type ReactNode } from 'react';
import GambarProduk from './GambarProduk';
import { rupiah } from './types';

interface BarisRingkas {
    id: number;
    name: string;
    slug: string;
    imageUrl: string | null;
    unit: string;
    qty: number;
    price: number;
    subtotal: number;
}

interface Ringkasan {
    items: BarisRingkas[];
    jumlahItem: number;
    subtotal: number;
}

function Ikon({ children, ukuran = 18 }: { children: ReactNode; ukuran?: number }) {
    return (
        <svg viewBox="0 0 24 24" width={ukuran} height={ukuran} fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            {children}
        </svg>
    );
}

const HANYA_KERANJANG = ['qtyKeranjang', 'jumlahKeranjang', 'flash'];

/**
 * Cart icon in the header. A click opens a small popup with the items in the
 * cart, the subtotal, and links to the full cart and checkout. On /keranjang
 * and /checkout the popup would only repeat the page, so the icon stays a link.
 */
export default function MiniKeranjang({ jumlah }: { jumlah: number }) {
    const [buka, setBuka] = useState(false);
    const [data, setData] = useState<Ringkasan | null>(null);
    const [memuat, setMemuat] = useState(false);
    const [gagal, setGagal] = useState(false);
    const [posisi, setPosisi] = useState<{ top: number; right: number }>({ top: 0, right: 12 });
    const pembungkus = useRef<HTMLDivElement>(null);
    const tombol = useRef<HTMLButtonElement>(null);
    const diHalamanKeranjang = ['/keranjang', '/checkout'].includes(window.location.pathname);
    const label = `Keranjang belanja, ${jumlah} barang`;

    const muat = useCallback(() => {
        setMemuat(true);
        setGagal(false);
        fetch('/keranjang/ringkas', { headers: { Accept: 'application/json' } })
            .then((respons) => (respons.ok ? (respons.json() as Promise<Ringkasan>) : Promise.reject(new Error('gagal'))))
            .then((hasil) => setData(hasil))
            .catch(() => setGagal(true))
            .finally(() => setMemuat(false));
    }, []);

    // Load when opened, and again whenever the cart changes while it is open (cards, remove button).
    useEffect(() => {
        if (buka) muat();
    }, [buka, jumlah, muat]);

    // The popup is fixed to the viewport so it fits narrow screens; keep it under the icon while open.
    useLayoutEffect(() => {
        if (!buka) return;
        const letakkan = () => {
            const kotak = tombol.current?.getBoundingClientRect();
            if (!kotak) return;
            // Hang from the icon, but never past the left edge of a narrow screen (panel width matches the CSS).
            const lebar = Math.min(380, window.innerWidth - 24);
            const kanan = Math.min(window.innerWidth - kotak.right, window.innerWidth - lebar - 12);
            setPosisi({ top: Math.round(kotak.bottom + 10), right: Math.max(12, Math.round(kanan)) });
        };
        letakkan();
        window.addEventListener('scroll', letakkan, { passive: true });
        window.addEventListener('resize', letakkan);

        return () => {
            window.removeEventListener('scroll', letakkan);
            window.removeEventListener('resize', letakkan);
        };
    }, [buka]);

    useEffect(() => {
        if (!buka) return;
        const klikLuar = (event: MouseEvent) => {
            if (pembungkus.current && !pembungkus.current.contains(event.target as Node)) setBuka(false);
        };
        const tekanTombol = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setBuka(false);
                tombol.current?.focus();
            }
        };
        document.addEventListener('mousedown', klikLuar);
        document.addEventListener('keydown', tekanTombol);

        return () => {
            document.removeEventListener('mousedown', klikLuar);
            document.removeEventListener('keydown', tekanTombol);
        };
    }, [buka]);

    const hapus = (id: number) => router.delete(`/keranjang/${id}`, { preserveScroll: true, preserveState: true, only: HANYA_KERANJANG });

    const lencana = jumlah > 0 && <span className="gd-keranjang-jumlah">{jumlah > 99 ? '99+' : jumlah}</span>;

    if (diHalamanKeranjang) {
        return (
            <div className="gd-mini-keranjang order-lg-last">
                <a href="/keranjang" className="gd-keranjang-link" aria-label={label}>
                    <i className="feather-icon icon-shopping-bag" aria-hidden="true"></i>
                    {lencana}
                </a>
            </div>
        );
    }

    const kosong = data !== null && data.items.length === 0;

    return (
        <div className="gd-mini-keranjang order-lg-last" ref={pembungkus}>
            <button
                type="button"
                ref={tombol}
                className="gd-keranjang-link"
                aria-label={label}
                aria-haspopup="dialog"
                aria-expanded={buka}
                aria-controls="gd-mini-keranjang-panel"
                onClick={() => setBuka((nilai) => !nilai)}
            >
                <i className="feather-icon icon-shopping-bag" aria-hidden="true"></i>
                {lencana}
            </button>

            {buka && (
                <div id="gd-mini-keranjang-panel" className="gd-mini-panel" role="dialog" aria-label="Ringkasan keranjang" style={{ top: posisi.top, right: posisi.right }}>
                    <div className="gd-mini-kepala">
                        <strong>Keranjang{data && data.jumlahItem > 0 ? ` (${data.jumlahItem})` : ''}</strong>
                        <button type="button" className="gd-mini-tutup" aria-label="Tutup ringkasan keranjang" onClick={() => setBuka(false)}>
                            <Ikon>
                                <path d="M18 6 6 18M6 6l12 12" />
                            </Ikon>
                        </button>
                    </div>

                    {data === null && memuat && <p className="gd-mini-info">Memuat...</p>}
                    {gagal && (
                        <p className="gd-mini-info" role="alert">
                            Ringkasan gagal dimuat.{' '}
                            <button type="button" className="gd-tautan-ulang" onClick={muat}>
                                Coba lagi
                            </button>
                        </p>
                    )}

                    {kosong && (
                        <div className="gd-mini-kosong">
                            <Ikon ukuran={32}>
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                <path d="M3 6h18" />
                                <path d="M16 10a4 4 0 0 1-8 0" />
                            </Ikon>
                            <p>Keranjang masih kosong.</p>
                            <a href="/toko" className="btn btn-primary">
                                Lihat Toko
                            </a>
                        </div>
                    )}

                    {data !== null && data.items.length > 0 && (
                        <>
                            <ul className="gd-mini-daftar">
                                {data.items.map((item) => (
                                    <li key={item.id}>
                                        <a href={`/toko/${item.slug}`} className="gd-mini-foto" tabIndex={-1} aria-hidden="true">
                                            <GambarProduk src={item.imageUrl} alt={item.name} />
                                        </a>
                                        <div className="gd-mini-teks">
                                            <a href={`/toko/${item.slug}`}>{item.name}</a>
                                            <span>
                                                {item.qty} x {rupiah(item.price)} /{item.unit}
                                            </span>
                                        </div>
                                        <div className="gd-mini-kanan">
                                            <strong>{rupiah(item.subtotal)}</strong>
                                            <button type="button" aria-label={`Hapus ${item.name} dari keranjang`} onClick={() => hapus(item.id)}>
                                                <Ikon ukuran={16}>
                                                    <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                                </Ikon>
                                            </button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                            <div className="gd-mini-total">
                                <span>Subtotal</span>
                                <strong>{rupiah(data.subtotal)}</strong>
                            </div>
                            <p className="gd-mini-catatan">Ongkir dihitung di checkout sesuai alamat dan kurir.</p>
                            <div className="gd-mini-aksi">
                                <a href="/keranjang" className="btn btn-outline-primary">
                                    Lihat Keranjang
                                </a>
                                <a href="/checkout" className="btn btn-primary">
                                    Checkout
                                </a>
                            </div>
                        </>
                    )}
                </div>
            )}
        </div>
    );
}
