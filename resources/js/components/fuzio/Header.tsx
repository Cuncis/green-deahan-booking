import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import WhatsAppIcon from './elements/WhatsAppIcon';
import { GD_LOGO, GD_LOGO_LIGHT, GD_PHONE_DISPLAY, GD_PHONE_TEL, menuData, waLink } from '../../data/greendeahan';

function MenuList({ onNavigate }: { onNavigate: () => void }) {
    const currentPath = window.location.pathname;
    const isActive = (path: string) => (path === '/' ? currentPath === '/' : currentPath === path || currentPath.startsWith(`${path}/`));

    return (
        <ul className="navbar-nav mx-auto align-items-lg-center">
            {menuData.map((item) => (
                <li key={item.path} className={`nav-item${isActive(item.path) ? ' current' : ''}`}>
                    <a href={item.path} className="nav-link" onClick={onNavigate}>
                        {item.label}
                    </a>
                </li>
            ))}
        </ul>
    );
}

interface HeaderProps {
    /**
     * 'home': Fuzio's HeaderOne, a transparent navbar with the white logo over the hero.
     * 'pages': Fuzio's PagesHeader, a light navbar with the coloured logo for inner pages.
     */
    variant?: 'home' | 'pages';
}

/**
 * Port of Fuzio's HeaderOne / PagesHeader: green top bar, navbar that
 * becomes a sticky bar after scrolling past the header, and off-canvas
 * mobile menu.
 */
export default function Header({ variant = 'home' }: HeaderProps) {
    const isHome = variant === 'home';
    const [isSticky, setIsSticky] = useState(false);
    const [isMobile, setIsMobile] = useState(false);
    const headerRef = useRef<HTMLElement>(null);
    const jumlahKeranjang = Number(usePage<{ jumlahKeranjang?: number }>().props.jumlahKeranjang ?? 0);

    useEffect(() => {
        let ticking = false;

        const handleScroll = () => {
            if (ticking) {
                return;
            }

            ticking = true;
            requestAnimationFrame(() => {
                if (headerRef.current) {
                    const shouldBeSticky = window.scrollY > headerRef.current.offsetHeight;
                    setIsSticky((previous) => (previous === shouldBeSticky ? previous : shouldBeSticky));
                }
                ticking = false;
            });
        };

        window.addEventListener('scroll', handleScroll, { passive: true });

        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    return (
        <header ref={headerRef} className={isHome ? 'header header-1' : 'header'}>
            <div className="top-bar bg-primary py-1">
                <div className="container">
                    <div className="row">
                        <div className="col-lg-12">
                            <div className="d-sm-flex justify-content-between align-items-center">
                                <p className="d-none d-sm-block">
                                    <i className="feather-icon icon-phone me-2" aria-hidden="true"></i> <a href={`tel:${GD_PHONE_TEL}`}>{GD_PHONE_DISPLAY}</a>
                                </p>
                                <p className="d-none d-lg-block">Konsultasi Gratis &middot; Garansi Resmi &middot; Melayani Seluruh Indonesia</p>
                                <div className="top-meta d-flex align-items-center justify-content-between">
                                    <p>
                                        <a href={waLink()} target="_blank" rel="noopener noreferrer">
                                            <WhatsAppIcon size={16} className="topbar-wa-icon me-2" />
                                            Chat WhatsApp
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <nav className={`navbar navbar-expand-lg ${isSticky ? 'scroll-on' : ''}`}>
                <div className="container px-lg-0">
                    <a href="/" className="navbar-brand">
                        <img src={isHome && !isSticky ? GD_LOGO_LIGHT : GD_LOGO} width={150} height={60} alt="Green Deahan Sport" />
                    </a>
                    <a href="/keranjang" className="gd-keranjang-link order-lg-last" aria-label={`Keranjang belanja, ${jumlahKeranjang} barang`}>
                        <i className="feather-icon icon-shopping-bag" aria-hidden="true"></i>
                        {jumlahKeranjang > 0 && <span className="gd-keranjang-jumlah">{jumlahKeranjang > 99 ? '99+' : jumlahKeranjang}</span>}
                    </a>
                    <button onClick={() => setIsMobile((previous) => !previous)} className="navbar-toggler offcanvas-nav-btn" type="button" aria-label="Menu">
                        <span className="feather-icon icon-menu"></span>
                    </button>
                    <div className={`offcanvas${isHome ? ' bg-dark' : ''} offcanvas-start offcanvas-nav ${isMobile ? 'show' : ''}`}>
                        <div className="offcanvas-header">
                            <a href="/" className="text-inverse">
                                <img src={GD_LOGO} width={150} height={60} alt="Green Deahan Sport" />
                            </a>
                            <button onClick={() => setIsMobile(false)} type="button" className="btn-close" aria-label="Close"></button>
                        </div>
                        <div className="offcanvas-body pt-0 align-items-center">
                            <MenuList onNavigate={() => setIsMobile(false)} />
                        </div>
                    </div>
                    {isMobile && <div onClick={() => setIsMobile(false)} className="offcanvas-backdrop fade show"></div>}
                </div>
            </nav>
        </header>
    );
}
