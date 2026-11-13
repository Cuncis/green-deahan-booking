/**
 * Copy for /sistem-booking (Produk & Layanan > Sistem Booking). The feature
 * table and add-ons match the /harga page (resources/views/pages/pricing.blade.php);
 * package prices come from the server (Tenant::HARGA_PAKET).
 */

/** true = included, false = not included, text = included with a limit. */
export type NilaiFitur = boolean | string;

export interface GrupFitur {
    judul: string;
    baris: { fitur: string; basic: NilaiFitur; pro: NilaiFitur; premium: NilaiFitur }[];
}

export const tabelFitur: GrupFitur[] = [
    {
        judul: 'Fitur Inti Booking',
        baris: [
            { fitur: 'Jadwal lapangan real-time', basic: true, pro: true, premium: true },
            { fitur: 'Booking mandiri 24 jam', basic: true, pro: true, premium: true },
            { fitur: 'Anti double-booking otomatis', basic: true, pro: true, premium: true },
            { fitur: 'Konfirmasi otomatis (WA/email)', basic: true, pro: true, premium: true },
            { fitur: 'Tampilan mobile-friendly', basic: true, pro: true, premium: true },
            { fitur: 'Info lapangan (foto, peta, harga)', basic: true, pro: true, premium: true },
        ],
    },
    {
        judul: 'Pembayaran & Pengelolaan',
        baris: [
            { fitur: 'Pembayaran online (QRIS, VA, e-wallet)', basic: true, pro: true, premium: true },
            { fitur: 'Pilihan DP atau bayar penuh', basic: false, pro: true, premium: true },
            { fitur: 'Dashboard pemilik', basic: 'Dasar', pro: true, premium: 'Lengkap' },
            { fitur: 'Laporan pendapatan & jam ramai', basic: false, pro: true, premium: true },
            { fitur: 'Notifikasi tiap ada booking', basic: true, pro: true, premium: true },
            { fitur: 'Kelola banyak lapangan', basic: '1 lapangan', pro: '3 lapangan', premium: 'Unlimited' },
        ],
    },
    {
        judul: 'Fitur Premium',
        baris: [
            { fitur: 'Membership & pelanggan langganan', basic: false, pro: false, premium: true },
            { fitur: 'Kode promo & diskon', basic: false, pro: true, premium: true },
            { fitur: 'Booking berulang (jadwal rutin)', basic: false, pro: true, premium: true },
            { fitur: 'Rating & ulasan pelanggan', basic: false, pro: true, premium: true },
            { fitur: 'Reminder terjadwal sebelum main', basic: false, pro: false, premium: true },
            { fitur: 'Domain & branding sendiri', basic: false, pro: true, premium: true },
            { fitur: 'Multi-cabang', basic: false, pro: false, premium: true },
        ],
    },
    {
        judul: 'Layanan & Support',
        baris: [
            { fitur: 'Setup awal oleh tim kami', basic: true, pro: true, premium: true },
            { fitur: 'Support 1 tahun', basic: 'Email', pro: 'WA + Email', premium: 'Prioritas' },
            { fitur: 'Pelatihan penggunaan', basic: false, pro: 'Online', premium: 'Online + Video' },
            { fitur: 'Update fitur gratis', basic: false, pro: true, premium: true },
        ],
    },
];

export const addonSistemBooking = [
    { nama: 'Domain custom (.com) per tahun', harga: 'Rp250rb' },
    { nama: 'Desain logo & branding', harga: 'Rp500rb' },
    { nama: 'Integrasi WhatsApp Business', harga: 'Rp350rb' },
    { nama: 'Foto profesional lapangan', harga: 'Rp500rb' },
    { nama: 'Setup Google Maps & SEO dasar', harga: 'Rp400rb' },
];

export const fiturUnggulan = [
    { judul: 'Booking 24 Jam', ikon: 'icon-calendar', teks: 'Pelanggan lihat jadwal real-time dan booking sendiri kapan saja, tanpa perlu chat admin.' },
    { judul: 'Anti Double-Booking', ikon: 'icon-shield', teks: 'Jam yang sedang dipilih atau sudah dibayar otomatis terkunci, jadi tidak ada jadwal bentrok.' },
    { judul: 'Bayar Online', ikon: 'icon-credit-card', teks: 'QRIS, virtual account, dan e-wallet. Booking terkonfirmasi otomatis begitu pembayaran masuk.' },
    { judul: 'Laporan Pendapatan', ikon: 'icon-bar-chart-2', teks: 'Pantau pendapatan dan jam paling ramai dari dashboard pemilik (paket Pro dan Premium).' },
];

export const langkahMulai = [
    { judul: 'Pilih Paket & Daftar', teks: 'Isi data bisnis dan pilih paket Basic, Pro, atau Premium.' },
    { judul: 'Bayar Online', teks: 'Bayar biaya tahunan lewat halaman pembayaran online.' },
    { judul: 'Akun Aktif', teks: 'Setelah pembayaran diterima, akun aktif dan link pembuatan akun pemilik dikirim ke email Anda.' },
    { judul: 'Siap Terima Booking', teks: 'Tim kami bantu setup awal, lalu lapangan Anda siap menerima booking online.' },
];

export const sistemBookingFaqs = [
    {
        id: 'beda-paket',
        question: 'Apa bedanya paket Basic, Pro, dan Premium?',
        answer: 'Basic untuk 1 lapangan dengan fitur booking dan pembayaran online. Pro untuk sampai 3 lapangan, ditambah DP, kode promo, booking berulang, laporan pendapatan, dan domain sendiri. Premium tanpa batas lapangan, multi-cabang, membership, reminder, dan support prioritas.',
    },
    {
        id: 'pembayaran-pelanggan',
        question: 'Pelanggan saya bisa bayar pakai apa?',
        answer: 'QRIS, virtual account bank, dan e-wallet. Di paket Pro dan Premium, pelanggan juga bisa memilih bayar DP atau lunas.',
    },
    {
        id: 'domain',
        question: 'Apakah bisa memakai domain sendiri?',
        answer: 'Bisa di paket Pro dan Premium. Paket Basic memakai alamat dari domain kami. Domain custom (.com) tersedia sebagai tambahan Rp250rb per tahun.',
    },
    {
        id: 'perpanjangan',
        question: 'Berapa biaya tahun berikutnya?',
        answer: 'Harga yang tertera adalah biaya per tahun. Perpanjangan tahun berikutnya lebih ringan, hemat 30% dari harga tahun pertama.',
    },
    {
        id: 'setup',
        question: 'Apakah saya harus mengatur semuanya sendiri?',
        answer: 'Tidak. Semua paket sudah termasuk setup awal oleh tim kami dan support selama 1 tahun. Paket Pro dan Premium juga mendapat pelatihan penggunaan.',
    },
    {
        id: 'double-booking',
        question: 'Bagaimana kalau dua orang booking jam yang sama?',
        answer: 'Sistem otomatis mengunci jam yang sedang dipilih seseorang. Pelanggan lain langsung melihat jam itu tidak tersedia, jadi tidak ada jadwal bentrok.',
    },
];
