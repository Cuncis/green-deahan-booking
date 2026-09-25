import Icon from './Icon';

const WA_NUMBER = '6281357570064';

const layanan: Record<string, string> = {
    'Lapangan Futsal': 'lapangan futsal',
    'Mini Soccer': 'mini soccer',
    'Lapangan Padel': 'lapangan padel',
    'Lapangan Badminton': 'lapangan badminton',
    'Maintenance & Servis': 'maintenance lapangan',
};

const socials: { icon: 'instagram-logo' | 'facebook-logo' | 'tiktok-logo' | 'x-logo' | 'youtube-logo'; href: string; label: string; hover: string }[] = [
    { icon: 'instagram-logo', href: 'https://www.instagram.com/green_deahan1927', label: 'Instagram', hover: 'hover:bg-pink-600' },
    { icon: 'facebook-logo', href: 'https://www.facebook.com/greendeahan/', label: 'Facebook', hover: 'hover:bg-[#1877f2]' },
    { icon: 'tiktok-logo', href: 'https://www.tiktok.com/@green_deahan', label: 'TikTok', hover: 'hover:bg-stone-600' },
    { icon: 'x-logo', href: 'https://x.com/greendeahan1927', label: 'X / Twitter', hover: 'hover:bg-stone-600' },
    { icon: 'youtube-logo', href: 'https://www.youtube.com/@green_deahan', label: 'YouTube', hover: 'hover:bg-red-600' },
];

export default function Footer() {
    return (
        <footer id="kontak" className="bg-stone-900 px-6 pb-8 pt-14 text-stone-400">
            <div className="mx-auto mb-10 grid max-w-6xl gap-10 sm:grid-cols-2 md:grid-cols-4">
                <div className="md:col-span-2">
                    <div className="mb-4 flex items-center gap-2.5">
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-[#f5f5f5] p-0.5">
                            <img
                                src="https://cdn.libradigital.id/site-assets/GD-logo-1.png"
                                alt="Green Deahan Sport"
                                className="h-full w-full object-contain"
                            />
                        </div>
                        <div>
                            <div className="text-base font-black leading-none text-white">Green Deahan Sport</div>
                            <div className="mt-0.5 text-[10px] font-semibold uppercase leading-none tracking-widest text-stone-500">Sejak 2010</div>
                        </div>
                    </div>
                    <p className="mb-5 max-w-xs text-sm leading-relaxed text-stone-500">
                        Jasa pembuatan lapangan futsal, mini soccer, padel, dan badminton profesional sejak 2010. Melayani seluruh Indonesia.
                    </p>
                    <div className="space-y-2 text-sm">
                        <p className="flex items-center gap-2">
                            <Icon name="phone-call" size={14} className="text-stone-500" />
                            <a href={`tel:+${WA_NUMBER}`} className="transition-colors hover:text-white">+62 813-5757-0064</a>
                        </p>
                        <p className="flex items-center gap-2">
                            <Icon name="mail-envelope" size={14} className="text-stone-500" />
                            <a href="mailto:rumput1927@gmail.com" className="transition-colors hover:text-white">rumput1927@gmail.com</a>
                        </p>
                        <p className="flex items-center gap-2">
                            <Icon name="clock" size={14} className="text-stone-500" />
                            Senin-Sabtu, 06.00-23.00 WIB
                        </p>
                    </div>

                    <div className="mt-4 flex items-center gap-3">
                        {socials.map((social) => (
                            <a
                                key={social.label}
                                href={social.href}
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label={social.label}
                                className={`flex h-8 w-8 items-center justify-center rounded-lg bg-stone-800 text-stone-300 transition-colors ${social.hover}`}
                            >
                                <Icon name={social.icon} size={15} />
                            </a>
                        ))}
                    </div>
                </div>

                <div>
                    <h4 className="mb-4 text-sm font-bold uppercase tracking-widest text-white">Layanan</h4>
                    <ul className="space-y-2 text-sm">
                        {Object.entries(layanan).map(([label, topik]) => (
                            <li key={label}>
                                <a
                                    href={`https://wa.me/${WA_NUMBER}?text=${encodeURIComponent(`Halo, saya ingin konsultasi ${topik}`)}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="transition-colors hover:text-white"
                                >{label}</a>
                            </li>
                        ))}
                    </ul>
                </div>

                <div>
                    <h4 className="mb-4 text-sm font-bold uppercase tracking-widest text-white">Halaman</h4>
                    <ul className="space-y-2 text-sm">
                        <li><a href="/" className="transition-colors hover:text-white">Beranda</a></li>
                        <li><a href="/konsep" className="transition-colors hover:text-white">Konsep Sport Center</a></li>
                        <li><a href="/galeri" className="transition-colors hover:text-white">Galeri Proyek</a></li>
                        <li><a href="/blog" className="transition-colors hover:text-white">Blog & Tips</a></li>
                        <li><a href="/harga" className="transition-colors hover:text-white">Website Booking</a></li>
                        <li><a href="/kontak" className="transition-colors hover:text-white">Kontak Kami</a></li>
                    </ul>
                </div>
            </div>

            <div className="mx-auto max-w-6xl border-t border-stone-800 pt-6 text-center text-xs text-stone-500">
                &copy; {new Date().getFullYear()} <strong className="font-semibold text-stone-300">Green Deahan Sport</strong>. Seluruh hak cipta dilindungi.
                <br />
                Jasa Pembuatan Lapangan Futsal, Mini Soccer, Padel &amp; Badminton Se-Indonesia
            </div>
        </footer>
    );
}
