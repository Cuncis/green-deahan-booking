/**
 * Copy for /perawatan (Produk & Layanan > Perawatan). Maintenance is offered
 * only for courts Green Deahan built. Warranty facts match the Kontak FAQ;
 * care tips come from the "Tips Perawatan" blog articles.
 */

export interface LayananPerawatan {
    judul: string;
    ikon: string;
    deskripsi: string;
    pesanWa: string;
}

export const layananPerawatan: LayananPerawatan[] = [
    {
        judul: 'Perawatan Berkala',
        ikon: 'icon-calendar',
        deskripsi: 'Servis terjadwal: pembersihan, sikat rumput, tambah infill karet atau pasir, kencangkan net dan pagar, serta cek lampu.',
        pesanWa: 'Halo Green Deahan, saya ingin menjadwalkan perawatan berkala untuk lapangan saya.',
    },
    {
        judul: 'Perbaikan Kerusakan',
        ikon: 'icon-settings',
        deskripsi: 'Rumput sobek, tile interlock lepas, net atau gawang rusak, lampu LED mati, kaca atau rangka padel bermasalah.',
        pesanWa: 'Halo Green Deahan, ada kerusakan di lapangan saya yang perlu diperbaiki.',
    },
    {
        judul: 'Renovasi Lapangan',
        ikon: 'icon-refresh-cw',
        deskripsi: 'Ganti rumput atau lantai yang sudah aus, cat ulang garis, upgrade pencahayaan, atau ubah lapangan ke olahraga lain.',
        pesanWa: 'Halo Green Deahan, saya ingin konsultasi renovasi lapangan.',
    },
    {
        judul: 'Cek & Konsultasi Gratis',
        ikon: 'icon-search',
        deskripsi: 'Tim kami cek kondisi lapangan tanpa biaya, lalu kirim estimasi biaya tertulis sebelum pekerjaan dimulai.',
        pesanWa: 'Halo Green Deahan, saya ingin pengecekan gratis untuk lapangan saya.',
    },
];

export const alurPerawatan = [
    { judul: 'Hubungi Kami', teks: 'Chat WhatsApp dengan nama lapangan, lokasi, dan foto kondisi atau kerusakannya. Tim kami respon dalam 1 jam.' },
    { judul: 'Cek Gratis', teks: 'Tim kami cek kondisi lapangan untuk memastikan apa yang perlu dikerjakan.' },
    { judul: 'Estimasi Tertulis', teks: 'Anda menerima rincian pekerjaan dan biaya sebelum mulai. Kerusakan yang masuk garansi tanpa biaya.' },
    { judul: 'Dikerjakan Tim Kami', teks: 'Dikerjakan tim Green Deahan yang paham konstruksi dan bahan lapangan Anda.' },
];

export const tipsPerawatan = [
    { judul: 'Bersihkan secara rutin', teks: 'Sapu atau vacuum lantai setiap hari sebelum dan sesudah jam operasional.' },
    { judul: 'Wajibkan sepatu yang sesuai', teks: 'Sepatu non-futsal mempercepat aus permukaan lapangan.' },
    { judul: 'Cek infill rumput sintetis setiap 6 bulan', teks: 'Infill yang berkurang membuat rumput cepat rebah dan rusak.' },
    { judul: 'Periksa sambungan tile interlock', teks: 'Tile yang rusak bisa diganti sebagian tanpa membongkar seluruh lantai.' },
    { judul: 'Cat ulang garis lapangan', teks: 'Garis yang jelas menjaga tampilan dan kenyamanan pemain.' },
    { judul: 'Rawat pencahayaan', teks: 'Lampu yang redup atau mati mengurangi kenyamanan main di malam hari.' },
];

export const artikelPerawatan = [
    { judul: '7 Cara Merawat Lapangan Futsal agar Awet Sampai 10 Tahun', href: '/blog/7-cara-merawat-lapangan-futsal-agar-awet-sampai-10-tahun' },
    { judul: 'Cara Merawat Rumput Sintetis agar Tidak Cepat Rusak dan Pudar', href: '/blog/cara-merawat-rumput-sintetis-agar-tidak-cepat-rusak-dan-pudar' },
];

export const perawatanFaqs = [
    {
        id: 'kontraktor-lain',
        question: 'Apakah melayani lapangan yang dibangun kontraktor lain?',
        answer: 'Saat ini layanan perawatan kami khusus untuk lapangan yang dibangun oleh Green Deahan, supaya kami tahu persis bahan dan konstruksinya. Ingin membangun lapangan baru? Lihat halaman Bangun Lapangan.',
    },
    {
        id: 'garansi',
        question: 'Kerusakan apa yang ditanggung garansi?',
        answer: 'Setiap lapangan mendapat garansi resmi tertulis untuk pekerjaan dan bahannya. Kalau ada masalah yang masuk garansi, kami perbaiki tanpa biaya tambahan. Rincian cakupan dan masa garansi ada di dokumen garansi lapangan Anda.',
    },
    {
        id: 'biaya',
        question: 'Berapa biaya perawatan atau renovasi?',
        answer: 'Tergantung jenis lapangan, kondisinya, dan pekerjaan yang dibutuhkan. Tim kami cek lapangan secara gratis, lalu mengirim estimasi biaya tertulis sebelum pekerjaan dimulai.',
    },
    {
        id: 'seberapa-sering',
        question: 'Seberapa sering lapangan perlu dirawat?',
        answer: 'Pembersihan sebaiknya setiap hari. Untuk rumput sintetis, cek infill setiap 6 bulan. Jadwal servis berkala yang pas untuk lapangan Anda bisa kami sarankan saat pengecekan.',
    },
    {
        id: 'cara-ajukan',
        question: 'Bagaimana cara mengajukan perbaikan?',
        answer: 'Chat kami lewat WhatsApp dengan nama lapangan, lokasi, dan foto kondisi atau kerusakannya. Tim kami respon dalam 1 jam dan menjadwalkan pengecekan.',
    },
    {
        id: 'renovasi-olahraga-lain',
        question: 'Apakah lapangan bisa diubah ke olahraga lain?',
        answer: 'Bisa dikonsultasikan. Tim kami cek ukuran dan konstruksi lapangan Anda, lalu menjelaskan pilihan yang memungkinkan beserta estimasi biayanya.',
    },
];
