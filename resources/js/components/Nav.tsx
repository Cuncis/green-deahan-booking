import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Icon from './Icon';

const WA_NUMBER = '6281357570064';
const WA_LINK = `https://wa.me/${WA_NUMBER}`;

/** `component` is the matching Inertia page component for active-state detection; Beranda is excluded on purpose (never shows as active). */
const navLinks: { label: string; href: string; component: string | null }[] = [
    { label: 'Beranda', href: '/', component: null },
    { label: 'Konsep', href: '/konsep', component: 'V2Konsep' },
    { label: 'Galeri', href: '/galeri', component: 'V2Galeri' },
    { label: 'Blog', href: '/blog', component: 'V2Blog' },
    { label: 'Kontak', href: '/kontak', component: 'V2Kontak' },
];

export default function Nav() {
    const [scrolled, setScrolled] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const currentComponent = usePage().component;

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
                className={`fixed inset-x-0 top-0 z-50 border-b border-stone-200 bg-[#f5f5f5]/95 backdrop-blur transition-shadow duration-300 ${scrolled ? 'shadow-md' : ''}`}
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
                        {navLinks.map((link) => {
                            const isActive = link.component !== null && link.component === currentComponent;
                            return (
                                <a
                                    key={link.href}
                                    href={link.href}
                                    className={`underline-offset-8 decoration-2 transition-colors hover:text-brand hover:underline ${isActive ? 'text-brand underline' : ''}`}
                                >
                                    {link.label}
                                </a>
                            );
                        })}
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
                    <div className="flex flex-col gap-4 border-t border-stone-200 bg-[#f5f5f5] px-6 py-4 text-sm font-semibold text-stone-700 md:hidden">
                        {navLinks.map((link) => {
                            const isActive = link.component !== null && link.component === currentComponent;
                            return (
                                <a
                                    key={link.href}
                                    href={link.href}
                                    className={`underline-offset-8 decoration-2 transition-colors hover:text-brand hover:underline ${isActive ? 'text-brand underline' : ''}`}
                                    onClick={() => setMobileOpen(false)}
                                >
                                    {link.label}
                                </a>
                            );
                        })}
                        <a href={WA_LINK} target="_blank" rel="noopener noreferrer" className="rounded-lg bg-brand py-2.5 text-center text-white">
                            Konsultasi Gratis
                        </a>
                    </div>
                )}
            </nav>
        </>
    );
}
