/**
 * Kontak page content, ported from the Fuzio clone
 * (~/remove-later/fuzio/src/data/contactData.ts).
 */
import { GD_ADDRESS, GD_EMAIL, GD_PHONE_DISPLAY, waLink } from './greendeahan';

/** "Jenis Lapangan yang Diminati" */
export const contactFormSubjects = [
    { id: 1, label: 'Lapangan Futsal', value: 'Lapangan Futsal' },
    { id: 2, label: 'Mini Soccer', value: 'Mini Soccer' },
    { id: 3, label: 'Lapangan Padel', value: 'Lapangan Padel' },
    { id: 4, label: 'Lapangan Badminton', value: 'Lapangan Badminton' },
    { id: 5, label: 'Lainnya / Lebih dari 1 jenis', value: 'Lainnya / Lebih dari 1 jenis' },
];

/** "Estimasi Budget" */
export const contactFormBudgets = [
    { id: 1, label: 'Belum tahu, minta estimasi', value: 'Belum tahu, minta estimasi' },
    { id: 2, label: 'Di bawah Rp 200 juta', value: 'Di bawah Rp 200 juta' },
    { id: 3, label: 'Rp 200-500 juta', value: 'Rp 200-500 juta' },
    { id: 4, label: 'Rp 500 juta-1 miliar', value: 'Rp 500 juta-1 miliar' },
    { id: 5, label: 'Di atas Rp 1 miliar', value: 'Di atas Rp 1 miliar' },
];

export const contactFormText = {
    title: 'Hubungi Green Deahan, Kami Siap Membantu!',
    description:
        'Punya pertanyaan soal lapangan? Ingin estimasi biaya? Atau siap mulai proyek? Isi formulir ini dan pesan Anda langsung terkirim ke WhatsApp tim kami. Tim kami online, respon dalam 1 jam. Konsultasi, survey lokasi, dan RAB estimasi 100% gratis.',
};

export const contactFormSuccess = {
    title: 'Pesan Terkirim!',
    message: 'Jendela WhatsApp akan segera terbuka. Tim kami siap menghubungi Anda.',
};

/**
 * Same WhatsApp message format as the Blade contact form (/v2/kontak).
 * Emoji are allowed here only because they are inside the WhatsApp message
 * text, not in the page UI (references/icon-rules.md).
 */
export const contactWaMessage = (detail: { name: string; lapangan: string; city: string; budget: string; message: string }): string =>
    waLink(
        'Halo kak, saya ingin tanya-tanya soal pembuatan lapangan 🙏\n\n' +
            '━━━━━━━━━━━━━━━━━━━━\n' +
            '👤 *INFORMASI PEMESAN*\n' +
            `Nama Saya  : ${detail.name}\n` +
            '━━━━━━━━━━━━━━━━━━━━\n' +
            '🏟️ *DETAIL LAPANGAN*\n' +
            `Jenis Lapangan : ${detail.lapangan}\n` +
            `Kota / Lokasi  : ${detail.city}\n` +
            `Estimasi Budget: ${detail.budget || 'Belum tahu, minta estimasi'}\n` +
            '━━━━━━━━━━━━━━━━━━━━\n' +
            '💬 *KETERANGAN TAMBAHAN*\n' +
            `${detail.message || 'Tidak ada keterangan tambahan'}\n` +
            '━━━━━━━━━━━━━━━━━━━━\n' +
            'Mohon info lebih lanjutnya ya kak, terima kasih! 😊',
    );

/** Green Deahan on Google Maps (https://maps.app.goo.gl/Quq9TPPDzsaqynhE7). */
export const googleMapsUrl =
    'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3958.0300834353247!2d112.62486637588017!3d-7.2374080710837845!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7fe2e5c1388a3%3A0x4dcdfe1422d72eb0!2sGreen%20Deahan!5e0!3m2!1sen!2sid!4v1794306464432!5m2!1sen!2sid';

interface ContactLink {
    label: string;
    /** omit to show plain, non-clickable text (the underlined link below it does the action) */
    href?: string;
    /** underline the link so it reads as clickable (e.g. "Chat WhatsApp") */
    underline?: boolean;
}

interface ContactCard {
    id: number;
    icon: string;
    title: string;
    items?: ContactLink[];
    content?: string;
}

export const contactCards: ContactCard[] = [
    {
        id: 1,
        icon: 'icon-phone',
        title: 'Telepon / WhatsApp',
        items: [
            { label: GD_PHONE_DISPLAY },
            { label: 'Chat WhatsApp', href: waLink('Halo GreenDeahan, saya ingin konsultasi lapangan'), underline: true },
        ],
    },
    {
        id: 2,
        icon: 'icon-mail',
        title: 'Email',
        items: [
            { label: GD_EMAIL },
            { label: 'Kirim Email', href: `mailto:${GD_EMAIL}?subject=${encodeURIComponent('Konsultasi Pembuatan Lapangan')}`, underline: true },
        ],
    },
    {
        id: 3,
        icon: 'icon-map-pin',
        title: 'Kantor Pusat',
        content: `${GD_ADDRESS}. Melayani seluruh Indonesia.`,
    },
];

/** Kontak FAQ, same questions as the "Pertanyaan Cepat" box on the Blade contact page (/v2/kontak). */
export const kontakFaqs = [
    {
        id: 'gratis',
        question: 'Apakah konsultasi benar-benar gratis?',
        answer: 'Ya, 100% gratis tanpa syarat. Kami tidak memungut biaya apapun untuk konsultasi, survey lokasi, maupun pembuatan RAB estimasi.',
    },
    {
        id: 'lama-proses',
        question: 'Berapa lama proses dari konsultasi sampai mulai?',
        answer: 'Setelah konsultasi awal, biasanya 3 sampai 7 hari untuk survey dan desain, lalu 3 sampai 5 hari untuk penawaran harga. Setelah deal, konstruksi bisa dimulai dalam 1 sampai 2 minggu.',
    },
    {
        id: 'portofolio',
        question: 'Apakah bisa minta contoh portofolio dulu?',
        answer: 'Tentu! Kunjungi halaman Galeri kami atau minta tim kami kirim foto dan video proyek yang sesuai kebutuhan Anda via WhatsApp.',
    },
    {
        id: 'biaya',
        question: 'Berapa biaya membuat satu lapangan?',
        answer: 'Biayanya tergantung jenis lapangan, ukuran lahan, dan bahan yang dipilih. Setelah tim kami melihat lokasi, kami buatkan rincian biaya (RAB) secara gratis. Jadi Anda tahu semua biayanya sebelum mulai, tanpa biaya tersembunyi.',
    },
    {
        id: 'luar-kota',
        question: 'Apakah bisa membangun di luar Surabaya?',
        answer: 'Bisa. Kami melayani seluruh Indonesia dan sudah membangun lapangan di lebih dari 15 kota. Tim kami yang datang ke lokasi Anda.',
    },
    {
        id: 'pembayaran',
        question: 'Bagaimana cara pembayarannya?',
        answer: 'Anda cukup membayar uang muka (DP) di awal. Sisanya dibayar setelah lapangan selesai 100% dan sudah Anda cek sendiri.',
    },
    {
        id: 'garansi',
        question: 'Apakah ada garansi kalau ada yang rusak?',
        answer: 'Ada. Setiap lapangan mendapat garansi resmi tertulis untuk pekerjaan dan bahannya. Kalau ada masalah yang masuk garansi, kami perbaiki tanpa biaya tambahan.',
    },
    {
        id: 'pilih-lapangan',
        question: 'Saya belum tahu lapangan apa yang cocok. Bisa dibantu?',
        answer: 'Bisa. Ceritakan saja ukuran lahan, lokasi, dan budget Anda. Tim kami datang mengukur lahan, lalu menyarankan jenis lapangan yang paling pas, misalnya futsal, mini soccer, padel, atau badminton.',
    },
];
