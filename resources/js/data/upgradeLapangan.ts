/**
 * Copy for /upgrade-lapangan (Layanan > Upgrade Lapangan). Unlike /perawatan,
 * upgrades are open to every court, including ones other contractors built.
 * No prices are promised: every job starts with a free check and a written
 * estimate, same as Perawatan.
 */

export interface LayananUpgrade {
    judul: string;
    ikon: string;
    deskripsi: string;
    manfaat: string;
    pesanWa: string;
}

export const layananUpgrade: LayananUpgrade[] = [
    {
        judul: 'Pencahayaan LED',
        ikon: 'icon-sun',
        deskripsi: 'Ganti lampu lama ke LED atau tambah titik lampu supaya seluruh lapangan terang merata.',
        manfaat: 'Jam malam adalah jam paling ramai. Lapangan yang terang lebih nyaman dan lebih laku disewa.',
        pesanWa: 'Halo Green Deahan, saya ingin konsultasi upgrade lampu LED untuk lapangan saya.',
    },
    {
        judul: 'Atap & Kanopi',
        ikon: 'icon-umbrella',
        deskripsi: 'Pasang atap atau kanopi untuk lapangan outdoor, disesuaikan dengan ukuran dan konstruksi lapangan.',
        manfaat: 'Booking tidak batal saat hujan, jadi lapangan bisa disewakan sepanjang tahun.',
        pesanWa: 'Halo Green Deahan, saya ingin konsultasi pemasangan atap atau kanopi lapangan.',
    },
    {
        judul: 'Pagar & Jaring',
        ikon: 'icon-grid',
        deskripsi: 'Pasang atau ganti jaring pengaman, pagar keliling, dan net yang sudah rusak atau kendur.',
        manfaat: 'Bola tidak keluar lapangan, area sekitar lebih aman, dan lapangan terlihat lebih rapi.',
        pesanWa: 'Halo Green Deahan, saya ingin konsultasi pemasangan pagar atau jaring lapangan.',
    },
    {
        judul: 'Ganti Permukaan',
        ikon: 'icon-layers',
        deskripsi: 'Ganti rumput sintetis yang sudah pudar atau rebah, lantai interlock, vinyl, atau karpet yang sudah aus.',
        manfaat: 'Permukaan baru membuat pemain lebih nyaman, lebih aman, dan mau kembali bermain.',
        pesanWa: 'Halo Green Deahan, saya ingin konsultasi penggantian rumput atau lantai lapangan.',
    },
    {
        judul: 'Ubah ke Olahraga Lain',
        ikon: 'icon-repeat',
        deskripsi: 'Ubah lapangan futsal atau badminton yang sepi ke padel atau mini soccer, sesuai ukuran lahan.',
        manfaat: 'Lahan yang sudah ada bisa dipakai untuk olahraga yang sedang banyak dicari.',
        pesanWa: 'Halo Green Deahan, saya ingin konsultasi mengubah lapangan saya ke olahraga lain.',
    },
    {
        judul: 'Perbaikan Drainase',
        ikon: 'icon-droplet',
        deskripsi: 'Perbaiki saluran air untuk lapangan yang sering tergenang setelah hujan.',
        manfaat: 'Lapangan cepat kering dan bisa langsung dipakai lagi, permukaan juga lebih awet.',
        pesanWa: 'Halo Green Deahan, lapangan saya sering tergenang, saya ingin konsultasi perbaikan drainase.',
    },
];

export const alurUpgrade = [
    { judul: 'Kirim Foto Lapangan', teks: 'Chat WhatsApp dengan lokasi, jenis lapangan, dan foto kondisinya. Tim kami respon dalam 1 jam.' },
    { judul: 'Cek Gratis', teks: 'Tim kami cek ukuran, konstruksi, dan kondisi lapangan untuk menentukan upgrade yang paling cocok.' },
    { judul: 'Estimasi Tertulis', teks: 'Anda menerima rincian pekerjaan, bahan, dan biaya sebelum pekerjaan dimulai.' },
    { judul: 'Dikerjakan Tim Kami', teks: 'Pekerjaan dikerjakan tim Green Deahan, lalu dicek bersama Anda sebelum serah terima.' },
];

export const upgradeFaqs = [
    {
        id: 'kontraktor-lain',
        question: 'Apakah lapangan yang dibangun kontraktor lain bisa di-upgrade?',
        answer: 'Bisa. Layanan upgrade terbuka untuk semua lapangan. Karena kami belum tahu bahan dan konstruksinya, tim kami cek lapangan dulu secara gratis sebelum memberi estimasi.',
    },
    {
        id: 'biaya',
        question: 'Berapa biaya upgrade lapangan?',
        answer: 'Tergantung jenis upgrade, ukuran, dan kondisi lapangan. Setelah pengecekan gratis, Anda menerima estimasi biaya tertulis sebelum pekerjaan dimulai.',
    },
    {
        id: 'beda-perawatan',
        question: 'Apa bedanya dengan layanan Perawatan?',
        answer: 'Perawatan menjaga lapangan buatan Green Deahan tetap prima lewat servis berkala dan perbaikan. Upgrade menambah atau mengganti bagian lapangan, seperti lampu, atap, atau permukaan, dan bisa untuk lapangan siapa saja.',
    },
    {
        id: 'tetap-buka',
        question: 'Apakah lapangan harus tutup selama pengerjaan?',
        answer: 'Tergantung pekerjaannya. Sebagian upgrade bisa dijadwalkan di luar jam ramai. Jadwal pengerjaan kami sepakati bersama Anda saat estimasi.',
    },
    {
        id: 'beberapa-sekaligus',
        question: 'Bisa mengerjakan beberapa upgrade sekaligus?',
        answer: 'Bisa. Misalnya ganti permukaan sekaligus upgrade lampu. Semua pekerjaan masuk dalam satu estimasi tertulis.',
    },
    {
        id: 'cara-ajukan',
        question: 'Bagaimana cara memulai?',
        answer: 'Chat kami lewat WhatsApp dengan lokasi, jenis lapangan, dan foto kondisinya. Tim kami respon dalam 1 jam dan menjadwalkan pengecekan.',
    },
];
