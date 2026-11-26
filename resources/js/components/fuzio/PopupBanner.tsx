import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import '../../../fuzio/css/gd-popup.css';

interface BannerPromo {
    id: number;
    versi: number;
    judul: string;
    fotoUrl: string;
    tautan: string | null;
    labelTombol: string;
    tampilkanTombol: boolean;
    target: 'semua' | 'beranda' | 'toko';
    frekuensi: 'sekali' | 'sesi' | 'selalu';
    tundaDetik: number;
}

/** Halaman transaksi, pendaftaran, dan legal tidak pernah diganggu pop-up. */
const HALAMAN_BEBAS = ['/checkout', '/keranjang', '/pesanan', '/daftar', '/kebijakan-privasi', '/syarat-ketentuan', '/pengiriman-pengembalian', '/kebijakan-cookie'];

/** Untuk frekuensi "selalu": tampil sekali per pemuatan halaman, bukan di tiap perpindahan halaman. */
let sudahTampilDiMuatan = false;

function cocokHalaman(target: BannerPromo['target'], path: string): boolean {
    if (HALAMAN_BEBAS.some((awal) => path === awal || path.startsWith(`${awal}/`))) return false;
    if (target === 'beranda') return path === '/';
    if (target === 'toko') return path === '/toko' || path.startsWith('/toko/');

    return true;
}

function penyimpanan(frekuensi: BannerPromo['frekuensi']): Storage | null {
    try {
        return frekuensi === 'sekali' ? window.localStorage : frekuensi === 'sesi' ? window.sessionStorage : null;
    } catch {
        return null;
    }
}

const kunci = (banner: BannerPromo) => `gd-banner-${banner.id}-${banner.versi}`;

function sudahDilihat(banner: BannerPromo): boolean {
    if (banner.frekuensi === 'selalu') return sudahTampilDiMuatan;

    try {
        return penyimpanan(banner.frekuensi)?.getItem(kunci(banner)) === '1';
    } catch {
        return false;
    }
}

function tandaiDilihat(banner: BannerPromo): void {
    sudahTampilDiMuatan = true;

    try {
        penyimpanan(banner.frekuensi)?.setItem(kunci(banner), '1');
    } catch {
        // Penyimpanan diblokir (mode privat): banner tetap tampil, hanya tidak diingat.
    }
}

/**
 * Pop-up promo yang diatur dari superadmin (Banner Promo). Tampil sekali sesuai
 * frekuensinya, ditunda beberapa detik, bisa ditutup dengan tombol, klik latar,
 * atau Esc. Banner yang diedit punya `versi` baru sehingga tampil lagi.
 */
export default function PopupBanner() {
    const { props, url } = usePage<{ bannerPromo?: BannerPromo | null }>();
    const bannerPromo = props.bannerPromo ?? null;
    const halaman = url.split('?')[0].split('#')[0] || '/';
    const [terbuka, setTerbuka] = useState(false);
    const dialog = useRef<HTMLDivElement>(null);
    const bannerId = bannerPromo ? kunci(bannerPromo) : null;

    useEffect(() => {
        if (!bannerPromo || !cocokHalaman(bannerPromo.target, halaman) || sudahDilihat(bannerPromo)) {
            return;
        }

        const timer = window.setTimeout(() => {
            tandaiDilihat(bannerPromo);
            setTerbuka(true);
        }, bannerPromo.tundaDetik * 1000);

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [bannerId, halaman]);

    useEffect(() => {
        if (!terbuka) return;

        const tutup = (event: KeyboardEvent) => event.key === 'Escape' && setTerbuka(false);
        const overflowAwal = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', tutup);
        dialog.current?.focus({ preventScroll: true });

        return () => {
            document.body.style.overflow = overflowAwal;
            document.removeEventListener('keydown', tutup);
        };
    }, [terbuka]);

    // A page change (the cart, checkout) closes a banner that is still open.
    useEffect(() => {
        if (bannerPromo && !cocokHalaman(bannerPromo.target, halaman)) setTerbuka(false);
    }, [bannerPromo, halaman]);

    if (!bannerPromo || !terbuka) return null;

    const luar = bannerPromo.tautan !== null && /^https?:\/\//i.test(bannerPromo.tautan) && !bannerPromo.tautan.startsWith(window.location.origin);
    const tautanProps = luar ? { target: '_blank', rel: 'noopener noreferrer' } : {};
    const gambar = <img src={bannerPromo.fotoUrl} alt={bannerPromo.judul} className="gd-popup-gambar" />;

    return (
        <div className="gd-popup-latar" onClick={() => setTerbuka(false)}>
            <div ref={dialog} tabIndex={-1} className="gd-popup" role="dialog" aria-modal="true" aria-label={bannerPromo.judul} onClick={(event) => event.stopPropagation()}>
                <button type="button" className="gd-popup-tutup" aria-label="Tutup promo" onClick={() => setTerbuka(false)}>
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
                {bannerPromo.tautan ? (
                    <a href={bannerPromo.tautan} onClick={() => setTerbuka(false)} {...tautanProps}>
                        {gambar}
                    </a>
                ) : (
                    gambar
                )}
                {bannerPromo.tautan && bannerPromo.tampilkanTombol && (
                    <div className="gd-popup-aksi">
                        <a href={bannerPromo.tautan} className="btn btn-primary btn-cta" onClick={() => setTerbuka(false)} {...tautanProps}>
                            {bannerPromo.labelTombol}
                        </a>
                    </div>
                )}
            </div>
        </div>
    );
}
