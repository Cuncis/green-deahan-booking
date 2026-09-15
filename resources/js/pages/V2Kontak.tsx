import { Head } from '@inertiajs/react';
import { useRef, useState } from 'react';
import Icon from '../components/Icon';
import Nav from '../components/Nav';
import Footer from '../components/Footer';
import Reveal from '../components/Reveal';
import PageStyles from '../components/PageStyles';
import BackToTop from '../components/BackToTop';

const WA_NUMBER = '6281357570064';
const WA_LINK = `https://wa.me/${WA_NUMBER}`;

const interestOptions: { value: string; label: string }[] = [
    { value: 'konsultasi', label: 'Konsultasi Gratis' },
    { value: 'survey', label: 'Survey Lokasi' },
    { value: 'rab', label: 'Estimasi RAB' },
    { value: 'renovasi', label: 'Renovasi Lapangan' },
    { value: 'perawatan', label: 'Perawatan Berkala' },
];

const interestLabels: Record<string, string> = Object.fromEntries(interestOptions.map((o) => [o.value, o.label]));

const quickContacts = [
    {
        icon: 'whatsapp-logo',
        color: 'bg-[#25d366]',
        hoverText: 'group-hover:text-[#25d366]',
        hoverBorder: 'hover:border-[#25d366]',
        label: 'WhatsApp',
        value: '+62 813-5757-0064',
        cta: 'Chat Sekarang →',
        ctaColor: 'bg-[#25d366] group-hover:bg-[#1fbc5a]',
        href: `${WA_LINK}?text=${encodeURIComponent('Halo GreenDeahan, saya ingin konsultasi lapangan')}`,
        external: true,
    },
    {
        icon: 'phone-call',
        color: 'bg-brand',
        hoverText: 'group-hover:text-brand',
        hoverBorder: 'hover:border-brand',
        label: 'Telepon',
        value: '+62 813-5757-0064',
        cta: 'Hubungi →',
        ctaColor: 'bg-brand group-hover:bg-brand-dark',
        href: `tel:+${WA_NUMBER}`,
        external: false,
    },
    {
        icon: 'mail-envelope',
        color: 'bg-red-500',
        hoverText: 'group-hover:text-red-500',
        hoverBorder: 'hover:border-red-400',
        label: 'Email',
        value: 'rumput1927@gmail.com',
        cta: 'Kirim Email →',
        ctaColor: 'bg-red-500 group-hover:bg-red-600',
        href: 'mailto:rumput1927@gmail.com',
        external: false,
    },
] as const;

const infoRows = [
    { icon: 'phone-call', label: 'Telepon / WhatsApp', value: '+62 813-5757-0064', href: `tel:+${WA_NUMBER}` },
    { icon: 'mail-envelope', label: 'Email', value: 'rumput1927@gmail.com', href: 'mailto:rumput1927@gmail.com' },
    { icon: 'clock', label: 'Jam Kerja', value: 'Senin sampai Sabtu, 06.00-23.00 WIB', href: null },
    { icon: 'location-pin', label: 'Area Layanan', value: 'Seluruh Indonesia', href: null },
] as const;

const socials = [
    { icon: 'instagram-logo', href: 'https://www.instagram.com/green_deahan1927', label: 'Instagram', hover: 'hover:bg-pink-600' },
    { icon: 'facebook-logo', href: 'https://www.facebook.com/greendeahan/', label: 'Facebook', hover: 'hover:bg-[#1877f2]' },
    { icon: 'tiktok-logo', href: 'https://www.tiktok.com/@green_deahan', label: 'TikTok', hover: 'hover:bg-stone-800' },
    { icon: 'x-logo', href: 'https://x.com/greendeahan1927', label: 'X / Twitter', hover: 'hover:bg-stone-800' },
    { icon: 'youtube-logo', href: 'https://www.youtube.com/@green_deahan', label: 'YouTube', hover: 'hover:bg-red-600' },
] as const;

const faqs = [
    { q: 'Apakah konsultasi benar-benar gratis?', a: 'Ya, 100% gratis tanpa syarat. Kami tidak memungut biaya apapun untuk konsultasi, survey lokasi, maupun pembuatan RAB estimasi.' },
    { q: 'Berapa lama proses dari konsultasi sampai mulai?', a: 'Setelah konsultasi awal, biasanya 3 sampai 7 hari untuk survey dan desain, lalu 3 sampai 5 hari untuk penawaran harga. Setelah deal, konstruksi bisa dimulai dalam 1 sampai 2 minggu.' },
    { q: 'Apakah bisa minta contoh portofolio dulu?', a: 'Tentu! Kunjungi halaman Galeri kami atau minta tim kami kirim foto dan video proyek yang sesuai kebutuhan Anda via WhatsApp.' },
];

/** FAQ row that expands/collapses to its real content height, matching the pattern used elsewhere on /v2 pages. */
function FaqRow({ q, a, isOpen, onToggle }: { q: string; a: string; isOpen: boolean; onToggle: () => void }) {
    const innerRef = useRef<HTMLDivElement>(null);
    const maxHeight = isOpen ? (innerRef.current?.scrollHeight ?? 300) : 0;

    return (
        <div className="overflow-hidden rounded-xl border border-stone-100">
            <button type="button" onClick={onToggle} className="flex w-full items-center justify-between px-4 py-3 text-left text-xs font-bold text-stone-800">
                {q}
                <span className="text-base font-black leading-none text-brand">{isOpen ? '−' : '+'}</span>
            </button>
            <div style={{ maxHeight }} className="overflow-hidden px-4 text-xs leading-relaxed text-stone-500 transition-[max-height] duration-300 ease-in-out">
                <div ref={innerRef} className="pb-3">
                    {a}
                </div>
            </div>
        </div>
    );
}

export default function V2Kontak() {
    const [name, setName] = useState('');
    const [lapanganType, setLapanganType] = useState('');
    const [city, setCity] = useState('');
    const [budget, setBudget] = useState('');
    const [message, setMessage] = useState('');
    const [interests, setInterests] = useState<string[]>([]);
    const [loading, setLoading] = useState(false);
    const [submitted, setSubmitted] = useState(false);
    const [error, setError] = useState('');
    const [openFaq, setOpenFaq] = useState<number | null>(null);

    function toggleInterest(value: string) {
        setInterests((prev) => (prev.includes(value) ? prev.filter((v) => v !== value) : [...prev, value]));
    }

    function submit() {
        setError('');
        if (!name.trim()) return setError('Nama lengkap wajib diisi.');
        if (!lapanganType) return setError('Pilih jenis lapangan yang diminati.');
        if (!city.trim()) return setError('Kota / lokasi proyek wajib diisi.');

        setLoading(true);

        const interestList = interests.length ? interests.map((v) => interestLabels[v]).join(', ') : 'Belum ditentukan';

        const waText = encodeURIComponent(
            'Halo kak, saya ingin tanya-tanya soal pembuatan lapangan \u{1F64F}\n\n' +
                '━━━━━━━━━━━━━━━━━━━━\n' +
                '\u{1F464} *INFORMASI PEMESAN*\n' +
                `Nama Saya  : ${name}\n` +
                '━━━━━━━━━━━━━━━━━━━━\n' +
                '\u{1F3DF}️ *DETAIL LAPANGAN*\n' +
                `Jenis Lapangan : ${lapanganType}\n` +
                `Kota / Lokasi  : ${city}\n` +
                `Estimasi Budget: ${budget || 'Belum tahu, minta estimasi'}\n` +
                '━━━━━━━━━━━━━━━━━━━━\n' +
                '✅ *SAYA BUTUH BANTUAN*\n' +
                `${interestList}\n` +
                '━━━━━━━━━━━━━━━━━━━━\n' +
                '\u{1F4AC} *KETERANGAN TAMBAHAN*\n' +
                `${message || 'Tidak ada keterangan tambahan'}\n` +
                '━━━━━━━━━━━━━━━━━━━━\n' +
                'Mohon info lebih lanjutnya ya kak, terima kasih! \u{1F60A}',
        );

        setTimeout(() => {
            setLoading(false);
            setSubmitted(true);
            setTimeout(() => {
                window.open(`${WA_LINK}?text=${waText}`, '_blank', 'noopener,noreferrer');
            }, 500);
        }, 350);
    }

    return (
        <>
            <Head title="Kontak Kami, Green Deahan Sport" />

            <PageStyles />

            <div className="bg-white text-stone-600 antialiased">
                <Nav />

                {/* HERO */}
                <section
                    className="relative bg-cover bg-center py-28 pt-36 text-center text-white"
                    style={{
                        backgroundImage: 'linear-gradient(rgba(0,0,0,.55), rgba(0,0,0,.55)), url(https://cdn.libradigital.id/site-assets/hero-banner1.jpg)',
                    }}
                >
                    <Reveal className="mx-auto max-w-2xl px-6">
                        <span className="mb-4 inline-flex items-center rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold tracking-wide backdrop-blur">
                            <span className="mr-1.5 inline-block h-2 w-2 animate-pulse rounded-full bg-green-400 align-middle" />
                            Tim Kami Online, Respon dalam 1 Jam
                        </span>
                        <h1 className="text-4xl font-bold leading-tight sm:text-5xl">Hubungi Green Deahan, Kami Siap Membantu!</h1>
                        <p className="mx-auto mt-3 max-w-xl text-base leading-relaxed text-white/80">
                            Punya pertanyaan soal lapangan? Ingin estimasi biaya? Atau siap mulai proyek? Pilih cara yang paling nyaman untuk Anda.
                        </p>
                    </Reveal>
                </section>

                {/* QUICK CONTACT CARDS */}
                <section className="mx-auto -mt-10 mb-6 max-w-6xl px-6">
                    <div className="grid gap-4 sm:grid-cols-3">
                        {quickContacts.map((card) => (
                            <Reveal key={card.label}>
                                <a
                                    href={card.href}
                                    target={card.external ? '_blank' : undefined}
                                    rel={card.external ? 'noopener noreferrer' : undefined}
                                    className={`group flex flex-col items-center rounded-2xl border-2 border-stone-200 bg-white p-5 text-center shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg ${card.hoverBorder}`}
                                >
                                    <div className={`mb-4 flex h-14 w-14 items-center justify-center rounded-2xl ${card.color}`}>
                                        <Icon name={card.icon} size={24} className="text-white" />
                                    </div>
                                    <div className={`mb-1 text-sm font-bold text-stone-900 transition-colors ${card.hoverText}`}>{card.label}</div>
                                    <div className="mb-1 text-sm font-bold text-brand">{card.value}</div>
                                    <div className={`mt-3 w-full rounded-full px-4 py-1.5 text-center text-xs font-bold text-white transition-colors ${card.ctaColor}`}>
                                        {card.cta}
                                    </div>
                                </a>
                            </Reveal>
                        ))}
                    </div>
                </section>

                {/* FORM + INFO */}
                <section className="mx-auto mb-16 max-w-6xl px-6 py-10">
                    <div className="grid grid-cols-1 gap-10 lg:grid-cols-5">
                        {/* Form */}
                        <Reveal className="lg:col-span-3">
                            <span className="mb-3 inline-flex items-center gap-1.5 rounded-full bg-brand-100 px-3 py-1.5 text-xs font-bold tracking-wide text-brand">
                                <Icon name="checklist" size={13} />
                                Formulir Kontak
                            </span>
                            <h2 className="mb-1 text-2xl font-bold text-stone-900">Bagaimana kami bisa bantu?</h2>
                            <p className="mb-6 text-sm text-stone-500">Isi form ini, tim kami akan follow up lewat WhatsApp.</p>

                            {submitted ? (
                                <div className="rounded-2xl border border-stone-200 bg-brand-50 py-10 text-center">
                                    <div className="mb-4 flex justify-center">
                                        <Icon name="check-circle" size={48} className="text-brand" />
                                    </div>
                                    <h3 className="mb-2 text-xl font-bold text-stone-900">Pesan Terkirim!</h3>
                                    <p className="mb-6 text-sm text-stone-500">Jendela WhatsApp akan segera terbuka. Tim kami siap menghubungi Anda.</p>
                                    <button
                                        type="button"
                                        onClick={() => setSubmitted(false)}
                                        className="rounded-full bg-brand px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-dark"
                                    >
                                        Kirim Pesan Lagi
                                    </button>
                                </div>
                            ) : (
                                <form
                                    className="space-y-4"
                                    onSubmit={(e) => {
                                        e.preventDefault();
                                        submit();
                                    }}
                                >
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600" htmlFor="f-name">
                                                Nama Lengkap *
                                            </label>
                                            <input
                                                id="f-name"
                                                type="text"
                                                value={name}
                                                onChange={(e) => setName(e.target.value)}
                                                placeholder="Masukkan nama Anda"
                                                className="w-full rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm placeholder-stone-400 transition-colors focus:border-brand focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,100,0,.1)]"
                                            />
                                        </div>
                                        <div>
                                            <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600" htmlFor="f-type">
                                                Jenis Lapangan yang Diminati *
                                            </label>
                                            <select
                                                id="f-type"
                                                value={lapanganType}
                                                onChange={(e) => setLapanganType(e.target.value)}
                                                className="w-full rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm text-stone-700 transition-colors focus:border-brand focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,100,0,.1)]"
                                            >
                                                <option value="">-- Pilih jenis lapangan --</option>
                                                <option value="Lapangan Futsal">Lapangan Futsal</option>
                                                <option value="Mini Soccer">Mini Soccer</option>
                                                <option value="Lapangan Padel">Lapangan Padel</option>
                                                <option value="Lapangan Badminton">Lapangan Badminton</option>
                                                <option value="Lebih dari 1 jenis">Lainnya / Lebih dari 1 jenis</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600" htmlFor="f-city">
                                                Kota / Lokasi Proyek *
                                            </label>
                                            <input
                                                id="f-city"
                                                type="text"
                                                value={city}
                                                onChange={(e) => setCity(e.target.value)}
                                                placeholder="Contoh: Jakarta Selatan"
                                                className="w-full rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm placeholder-stone-400 transition-colors focus:border-brand focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,100,0,.1)]"
                                            />
                                        </div>
                                        <div>
                                            <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600" htmlFor="f-budget">
                                                Estimasi Budget
                                            </label>
                                            <select
                                                id="f-budget"
                                                value={budget}
                                                onChange={(e) => setBudget(e.target.value)}
                                                className="w-full rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm text-stone-700 transition-colors focus:border-brand focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,100,0,.1)]"
                                            >
                                                <option value="">-- Pilih range budget --</option>
                                                <option value="Di bawah Rp 200 juta">Di bawah Rp 200 juta</option>
                                                <option value="Rp 200-500 juta">Rp 200-500 juta</option>
                                                <option value="Rp 500 juta-1 miliar">Rp 500 juta-1 miliar</option>
                                                <option value="Di atas Rp 1 miliar">Di atas Rp 1 miliar</option>
                                                <option value="Belum tahu, minta estimasi">Belum tahu, minta estimasi</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600" htmlFor="f-msg">
                                            Pesan / Detail Kebutuhan
                                        </label>
                                        <textarea
                                            id="f-msg"
                                            value={message}
                                            onChange={(e) => setMessage(e.target.value)}
                                            rows={4}
                                            placeholder="Ceritakan kebutuhan Anda: ukuran lahan, jumlah lapangan, fasilitas yang diinginkan, target waktu selesai, dll."
                                            className="w-full resize-none rounded-xl border-2 border-stone-200 bg-[#f7f5f2] px-4 py-3 text-sm placeholder-stone-400 transition-colors focus:border-brand focus:outline-none focus:shadow-[0_0_0_3px_rgba(0,100,0,.1)]"
                                        />
                                    </div>

                                    <div>
                                        <label className="mb-2 block text-xs font-bold uppercase tracking-wide text-stone-600">
                                            Saya tertarik untuk (boleh pilih lebih dari satu)
                                        </label>
                                        <div className="flex flex-wrap gap-2">
                                            {interestOptions.map((opt) => (
                                                <label
                                                    key={opt.value}
                                                    className="flex cursor-pointer items-center gap-2 rounded-lg border border-stone-200 bg-[#f7f5f2] px-3 py-2 text-xs font-semibold text-stone-700 transition-colors hover:border-brand"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={interests.includes(opt.value)}
                                                        onChange={() => toggleInterest(opt.value)}
                                                        className="accent-[#006400]"
                                                    />
                                                    {opt.label}
                                                </label>
                                            ))}
                                        </div>
                                    </div>

                                    {error && <small className="block text-xs font-semibold text-danger">{error}</small>}

                                    <button
                                        type="submit"
                                        disabled={loading}
                                        className="flex w-full items-center justify-center gap-2 rounded-xl bg-brand py-4 text-sm font-bold text-white shadow-lg shadow-brand-dark/20 transition-colors hover:bg-brand-dark disabled:opacity-70"
                                    >
                                        {!loading && <Icon name="send" size={16} className="text-white" />}
                                        {loading ? 'Mengirim...' : 'Kirim Pesan Sekarang'}
                                    </button>
                                </form>
                            )}
                        </Reveal>

                        {/* Info sidebar */}
                        <Reveal className="space-y-5 lg:col-span-2">
                            <div className="rounded-3xl bg-brand-50 p-6 sm:p-8">
                                <h5 className="text-base font-bold text-stone-900">Get in Touch</h5>
                                <div className="mt-4 space-y-4">
                                    {infoRows.map((row) => (
                                        <div key={row.label} className="flex gap-3">
                                            <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-white shadow-sm">
                                                <Icon name={row.icon} size={19} className="text-brand" />
                                            </div>
                                            <div>
                                                <p className="text-xs text-stone-500">{row.label}</p>
                                                {row.href ? (
                                                    <a href={row.href} className="text-sm font-semibold text-stone-900 hover:text-brand">
                                                        {row.value}
                                                    </a>
                                                ) : (
                                                    <p className="text-sm font-semibold text-stone-900">{row.value}</p>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center justify-center gap-3 rounded-3xl border border-stone-100 bg-white p-5">
                                <span className="text-sm font-semibold text-stone-900">Ikuti Kami:</span>
                                {socials.map((s) => (
                                    <a
                                        key={s.label}
                                        href={s.href}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label={s.label}
                                        className={`flex h-9 w-9 items-center justify-center rounded-full bg-stone-100 text-stone-500 transition-colors hover:text-white ${s.hover}`}
                                    >
                                        <Icon name={s.icon} size={15} />
                                    </a>
                                ))}
                            </div>

                            <div className="rounded-3xl border border-stone-100 bg-white p-6">
                                <h3 className="mb-4 flex items-center gap-2 border-b border-stone-100 pb-3 text-base font-bold text-stone-900">
                                    <Icon name="help-circle" size={18} className="text-brand" />
                                    Pertanyaan Cepat
                                </h3>
                                <div className="space-y-2">
                                    {faqs.map((faq, i) => (
                                        <FaqRow key={faq.q} q={faq.q} a={faq.a} isOpen={openFaq === i} onToggle={() => setOpenFaq(openFaq === i ? null : i)} />
                                    ))}
                                </div>
                            </div>
                        </Reveal>
                    </div>
                </section>

                {/* COVERAGE STRIP (in place of Folio's map embed - we don't have one physical office address to plot) */}
                <section className="bg-stone-900 px-6 py-10 text-center text-white">
                    <Reveal>
                        <p className="text-sm font-bold uppercase tracking-widest text-brand-300">Area Layanan</p>
                        <h2 className="mt-1 text-xl font-bold sm:text-2xl">Melayani Seluruh Indonesia</h2>
                        <p className="mx-auto mt-2 max-w-xl text-sm text-stone-400">
                            Jabodetabek, Jawa Tengah &amp; Timur, Sumatera, Kalimantan, Sulawesi, Bali, NTB, dan wilayah lainnya.
                        </p>
                    </Reveal>
                </section>

                <Footer />
                <BackToTop />
            </div>
        </>
    );
}
