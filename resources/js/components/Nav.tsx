import { useEffect, useState } from 'react';
import Icon from './Icon';

const WA_NUMBER = '6281357570064';
const WA_LINK = `https://wa.me/${WA_NUMBER}`;

const navLinks = [
    { label: 'Beranda', href: '/' },
    { label: 'Konsep', href: '/konsep' },
    { label: 'Galeri', href: '/galeri' },
    { label: 'Blog', href: '/blog' },
    { label: 'Kontak', href: '/kontak' },
];

export default function Nav() {
    const [scrolled, setScrolled] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 40);
        window.addEventListener('scroll', onScroll);
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <>
            <a
                href={WA_LINK}
                target="_blank"
                rel="noopener noreferrer"
                className="fixed bottom-7 right-7 z-[999] flex items-center gap-2 rounded-full bg-[#25d366] px-5 py-3.5 text-sm font-bold text-white shadow-[0_6px_24px_rgba(37,211,102,0.45)] transition-all hover:-translate-y-1 hover:shadow-[0_10px_30px_rgba(37,211,102,0.5)]"
            >
                <Icon name="whatsapp-logo" size={20} className="text-white" />
                Chat WhatsApp
            </a>

            <nav
                className={`fixed inset-x-0 top-0 z-50 border-b border-stone-200 bg-[#f7f5f2]/95 backdrop-blur transition-shadow duration-300 ${scrolled ? 'shadow-md' : ''}`}
            >
                <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                    <a href="/" className="flex items-center">
                        <img
                            src="https://cdn.libradigital.id/site-assets/full-logo-02.png"
                            alt="Green Deahan Sport"
                            className="h-10 w-auto object-contain"
                        />
                    </a>

                    <div className="hidden items-center gap-7 text-sm font-semibold text-stone-500 md:flex">
                        {navLinks.map((link) => (
                            <a key={link.href} href={link.href} className="transition-colors hover:text-brand">
                                {link.label}
                            </a>
                        ))}
                    </div>

                    <a
                        href={WA_LINK}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="hidden items-center gap-2 rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-dark md:flex"
                    >
                        <Icon name="whatsapp-logo" size={15} className="text-white" />
                        Konsultasi Gratis
                    </a>

                    <button
                        type="button"
                        className="flex flex-col gap-1.5 p-1 md:hidden"
                        aria-expanded={mobileOpen}
                        aria-label="Toggle menu"
                        onClick={() => setMobileOpen((open) => !open)}
                    >
                        <span className="block h-0.5 w-6 bg-stone-700"></span>
                        <span className="block h-0.5 w-6 bg-stone-700"></span>
                        <span className="block h-0.5 w-6 bg-stone-700"></span>
                    </button>
                </div>

                {mobileOpen && (
                    <div className="flex flex-col gap-4 border-t border-stone-200 bg-[#f7f5f2] px-6 py-4 text-sm font-semibold text-stone-700 md:hidden">
                        {navLinks.map((link) => (
                            <a key={link.href} href={link.href} className="hover:text-brand" onClick={() => setMobileOpen(false)}>
                                {link.label}
                            </a>
                        ))}
                        <a href={WA_LINK} target="_blank" rel="noopener noreferrer" className="rounded-lg bg-brand py-2.5 text-center text-white">
                            Konsultasi Gratis
                        </a>
                    </div>
                )}
            </nav>
        </>
    );
}
