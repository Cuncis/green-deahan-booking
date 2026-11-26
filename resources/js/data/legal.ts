/**
 * Isi halaman legal situs korporat (Kebijakan Privasi, Syarat & Ketentuan,
 * Pengiriman & Pengembalian, Kebijakan Cookie). Ditampilkan oleh pages/Legal.tsx,
 * rutenya di routes/web.php. Teks ditulis berdasarkan cara kerja situs ini;
 * tinjau dengan konsultan hukum sebelum diandalkan, dan perbarui TANGGAL_LEGAL
 * setiap kali isinya berubah.
 */
import { GD_ADDRESS, GD_EMAIL, GD_PHONE_DISPLAY } from './greendeahan';

export const TANGGAL_LEGAL = '25 November 2026';

export type IsiBagian = string | { daftar: string[] };

export interface BagianLegal {
    judul: string;
    isi: IsiBagian[];
}

export interface DokumenLegal {
    slug: string;
    judul: string;
    /** Label pendek untuk footer dan navigasi antar dokumen. */
    label: string;
    pembuka: string;
    bagian: BagianLegal[];
}

const KONTAK: BagianLegal = {
    judul: 'Hubungi Kami',
    isi: [
        'Pertanyaan tentang halaman ini dapat dikirim ke:',
        { daftar: [`Email: ${GD_EMAIL}`, `WhatsApp: ${GD_PHONE_DISPLAY}`, `Alamat: ${GD_ADDRESS}`] },
    ],
};

export const dokumenLegal: DokumenLegal[] = [
    {
        slug: 'kebijakan-privasi',
        judul: 'Kebijakan Privasi',
        label: 'Kebijakan Privasi',
        pembuka:
            'Green Deahan Sport ("kami") menghargai privasi Anda. Halaman ini menjelaskan data pribadi apa yang kami kumpulkan saat Anda memakai situs ini, untuk apa data itu dipakai, dengan siapa dibagikan, dan hak Anda sesuai Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.',
        bagian: [
            {
                judul: 'Data yang Kami Kumpulkan',
                isi: [
                    'Kami hanya mengumpulkan data yang Anda berikan sendiri atau yang diperlukan agar layanan berjalan:',
                    {
                        daftar: [
                            'Belanja di toko online: nama penerima, nomor HP/WhatsApp, email, alamat pengiriman (provinsi sampai kelurahan dan kode pos), isi pesanan, kode promo, dan status pembayaran.',
                            'Pendaftaran Sistem Booking: nama usaha, nama pemilik, email, nomor HP, dan data cabang atau lapangan yang Anda isi.',
                            'Booking lapangan di tenant Sistem Booking: nama, nomor HP, dan jadwal booking yang diisi pelanggan tenant.',
                            'Saat menghubungi kami lewat WhatsApp, email, atau formulir: nama, kontak, dan isi pesan Anda.',
                            'Statistik kunjungan: halaman yang dibuka, jenis perangkat, situs perujuk, serta perkiraan negara dan wilayah. Kami tidak menyimpan alamat IP; kunjungan dihitung memakai kode acak yang berganti setiap hari sehingga tidak dapat dipakai untuk mengenali Anda.',
                        ],
                    },
                    'Kami tidak menyimpan data kartu, rekening, atau PIN pembayaran Anda. Pembayaran diproses langsung oleh penyedia pembayaran.',
                ],
            },
            {
                judul: 'Penggunaan Data',
                isi: [
                    {
                        daftar: [
                            'Memproses pesanan: membuat tagihan, menghitung ongkos kirim, meneruskan pesanan ke supplier, dan mengirim nomor resi.',
                            'Mengirim kabar pesanan atau booking lewat WhatsApp dan email.',
                            'Menjalankan Sistem Booking untuk tenant, termasuk tagihan langganan dan pengingat.',
                            'Menjawab pertanyaan, penawaran, dan komplain.',
                            'Memperbaiki situs berdasarkan statistik kunjungan yang tidak mengenali identitas.',
                            'Memenuhi kewajiban hukum, misalnya pencatatan transaksi.',
                        ],
                    },
                    'Kami tidak menjual data pribadi Anda dan tidak memakainya untuk iklan pihak lain.',
                ],
            },
            {
                judul: 'Pihak yang Menerima Data',
                isi: [
                    'Data dibagikan hanya sebatas yang dibutuhkan untuk menyelesaikan layanan:',
                    {
                        daftar: [
                            'Mayar, penyedia pembayaran: nama, email, nomor HP, dan jumlah tagihan.',
                            'Supplier atau mitra toko yang mengirim barang: nama penerima, nomor HP, dan alamat pengiriman.',
                            'Penyedia tarif ongkir (RajaOngkir dan Biteship): kode pos asal dan tujuan, berat, serta nilai barang. Nama dan alamat lengkap tidak dikirim.',
                            'Penyedia layanan WhatsApp, email, dan server tempat situs ini berjalan.',
                            'Instansi berwenang, bila diwajibkan oleh hukum.',
                        ],
                    },
                    'Untuk Sistem Booking, data pelanggan sebuah lapangan dikelola oleh pemilik lapangan (tenant) tersebut. Kami menyimpan dan memproses data itu atas nama tenant.',
                ],
            },
            {
                judul: 'Penyimpanan dan Keamanan',
                isi: [
                    'Data disimpan di server yang dilindungi kata sandi dan koneksi terenkripsi (HTTPS). Hanya tim yang berwenang yang dapat membuka data pesanan.',
                    'Data pesanan dan transaksi disimpan selama dibutuhkan untuk layanan, garansi, dan pembukuan sesuai ketentuan perpajakan. Data yang tidak lagi diperlukan dihapus atau dianonimkan.',
                ],
            },
            {
                judul: 'Hak Anda',
                isi: [
                    'Anda dapat meminta untuk:',
                    {
                        daftar: [
                            'melihat data pribadi Anda yang kami simpan;',
                            'memperbaiki data yang salah atau tidak lengkap;',
                            'menghapus data Anda, kecuali yang wajib kami simpan menurut hukum (misalnya catatan transaksi);',
                            'menarik persetujuan untuk pesan promosi.',
                        ],
                    },
                    'Kirim permintaan lewat kontak di bawah. Kami akan memeriksa identitas Anda lalu menjawab paling lambat 3 x 24 jam sesuai UU PDP.',
                ],
            },
            {
                judul: 'Perubahan Kebijakan',
                isi: ['Kebijakan ini dapat diperbarui sewaktu-waktu. Tanggal pembaruan terakhir tercantum di bagian atas halaman.'],
            },
            KONTAK,
        ],
    },
    {
        slug: 'syarat-ketentuan',
        judul: 'Syarat & Ketentuan',
        label: 'Syarat & Ketentuan',
        pembuka:
            'Dengan memakai situs greendeahan.com, berbelanja di toko online, atau mendaftar Sistem Booking, Anda menyetujui syarat dan ketentuan berikut. Mohon dibaca sebelum melakukan transaksi.',
        bagian: [
            {
                judul: 'Tentang Kami',
                isi: [`Green Deahan Sport adalah penyedia jasa pembuatan dan perawatan lapangan olahraga, toko perlengkapan olahraga online, dan Sistem Booking lapangan, beralamat di ${GD_ADDRESS}.`],
            },
            {
                judul: 'Jasa Pembuatan dan Perawatan Lapangan',
                isi: [
                    'Harga, contoh proyek, dan estimasi di situs ini bersifat informasi awal. Lingkup kerja, harga final, jadwal, dan garansi pekerjaan konstruksi ditetapkan dalam penawaran tertulis atau kontrak terpisah yang disepakati kedua pihak.',
                ],
            },
            {
                judul: 'Toko Online',
                isi: [
                    {
                        daftar: [
                            'Harga ditampilkan dalam Rupiah dan dapat berubah sewaktu-waktu. Harga yang berlaku adalah harga saat pesanan dibuat.',
                            'Sebagian besar produk dikirim langsung oleh supplier mitra. Stok dapat berubah setelah pesanan dibuat. Bila barang ternyata habis, kami akan menghubungi Anda dan membatalkan pesanan dengan pengembalian dana penuh.',
                            'Pesanan wajib dibayar sebelum batas waktu yang tertera di halaman pembayaran. Pesanan yang tidak dibayar sampai batas waktu dibatalkan otomatis.',
                            'Ongkos kirim di checkout adalah perkiraan dari tarif kurir. Ketentuan pengiriman dan komplain ada di halaman Pengiriman & Pengembalian.',
                            'Kode promo berlaku sesuai syarat masing-masing (masa berlaku, minimal belanja, kuota), hanya memotong harga barang, tidak dapat diuangkan, dan dapat dihentikan bila terjadi penyalahgunaan.',
                            'Pastikan nama, nomor HP, dan alamat yang Anda isi benar. Kami tidak bertanggung jawab atas keterlambatan atau kegagalan kirim akibat data yang salah.',
                        ],
                    },
                ],
            },
            {
                judul: 'Pembayaran',
                isi: [
                    'Pembayaran diproses oleh Mayar melalui QRIS, virtual account bank, atau e-wallet. Pesanan dianggap lunas setelah kami menerima konfirmasi dari Mayar. Biaya yang dikenakan bank atau dompet digital Anda di luar tanggung jawab kami.',
                ],
            },
            {
                judul: 'Sistem Booking',
                isi: [
                    {
                        daftar: [
                            'Sistem Booking adalah layanan berlangganan untuk pemilik lapangan (tenant). Fitur, harga, dan masa aktif mengikuti paket yang dipilih saat mendaftar atau memperpanjang.',
                            'Tenant bertanggung jawab atas jadwal, harga sewa, kebijakan pembatalan, dan pelayanan kepada pelanggannya sendiri, serta atas data pelanggan yang diinput ke sistem.',
                            'Langganan yang tidak diperpanjang setelah masa aktif habis dapat dinonaktifkan. Data tenant tetap kami simpan untuk sementara agar dapat diaktifkan kembali.',
                            'Akun tidak boleh dipakai untuk kegiatan yang melanggar hukum atau mengganggu sistem.',
                        ],
                    },
                ],
            },
            {
                judul: 'Hak Kekayaan Intelektual',
                isi: ['Logo, foto proyek, tulisan, dan desain situs ini milik Green Deahan Sport atau dipakai dengan izin. Dilarang menyalin untuk kepentingan komersial tanpa izin tertulis dari kami.'],
            },
            {
                judul: 'Batasan Tanggung Jawab',
                isi: [
                    'Kami berupaya agar informasi di situs ini akurat dan layanan berjalan lancar, tetapi tidak menjamin situs selalu bebas gangguan. Tanggung jawab kami atas sebuah transaksi terbatas pada nilai transaksi tersebut.',
                ],
            },
            {
                judul: 'Hukum yang Berlaku',
                isi: [
                    'Syarat dan ketentuan ini tunduk pada hukum Republik Indonesia. Perselisihan diselesaikan lebih dulu secara musyawarah, dan bila tidak tercapai kesepakatan, melalui jalur hukum yang berlaku di Indonesia.',
                ],
            },
            {
                judul: 'Perubahan',
                isi: ['Kami dapat memperbarui syarat dan ketentuan ini. Versi yang berlaku adalah versi yang tampil di halaman ini saat Anda bertransaksi.'],
            },
            KONTAK,
        ],
    },
    {
        slug: 'pengiriman-pengembalian',
        judul: 'Pengiriman & Pengembalian',
        label: 'Pengiriman & Pengembalian',
        pembuka: 'Ketentuan pengiriman, komplain, dan pengembalian dana untuk pesanan di toko online Green Deahan Sport.',
        bagian: [
            {
                judul: 'Pengiriman',
                isi: [
                    {
                        daftar: [
                            'Pesanan diproses setelah pembayaran terkonfirmasi, pada hari kerja.',
                            'Barang dikirim oleh supplier mitra dari kota terdekat yang memiliki stok, memakai kurir reguler (misalnya JNE, J&T, SiCepat, AnterAja).',
                            'Ongkos kirim di checkout adalah perkiraan berdasarkan kode pos dan berat barang. Kami tidak menagih kekurangan bila ongkos sebenarnya lebih tinggi.',
                            'Estimasi waktu kirim berasal dari kurir dan dapat berubah karena cuaca, hari libur, atau kendala di lapangan.',
                            'Nomor resi dikirim lewat WhatsApp dan dapat dilihat di halaman status pesanan.',
                        ],
                    },
                ],
            },
            {
                judul: 'Komplain Barang',
                isi: [
                    'Bila barang yang diterima rusak, salah, atau kurang, hubungi kami paling lambat 2 x 24 jam setelah paket diterima dengan menyertakan:',
                    {
                        daftar: ['nomor pesanan;', 'video unboxing tanpa jeda sejak paket masih tersegel;', 'foto barang dan label pengiriman.'],
                    },
                    'Komplain tanpa video unboxing atau lewat dari batas waktu tidak dapat kami proses.',
                ],
            },
            {
                judul: 'Penukaran dan Pengembalian Barang',
                isi: [
                    {
                        daftar: [
                            'Penukaran diberikan bila barang yang dikirim tidak sesuai pesanan (salah ukuran, warna, atau model) atau rusak saat diterima.',
                            'Penukaran karena berubah pikiran atau salah memilih ukuran tidak dijamin dan bergantung pada persetujuan supplier. Ongkos kirim penukaran seperti ini ditanggung pembeli.',
                            'Barang yang dikembalikan harus belum dipakai, lengkap dengan kemasan dan label aslinya.',
                            'Instruksi dan alamat pengembalian akan kami berikan setelah komplain disetujui. Mohon jangan mengirim barang sebelum ada konfirmasi dari kami.',
                        ],
                    },
                ],
            },
            {
                judul: 'Pembatalan dan Pengembalian Dana',
                isi: [
                    {
                        daftar: [
                            'Pesanan yang belum dibayar dapat dibiarkan; pesanan otomatis batal setelah batas waktu pembayaran.',
                            'Pesanan yang sudah dibayar dapat dibatalkan selama belum diteruskan ke supplier. Hubungi kami secepatnya.',
                            'Bila pesanan dibatalkan oleh kami (misalnya stok habis), atau komplain disetujui dan barang tidak dapat ditukar, dana dikembalikan penuh termasuk ongkos kirim.',
                            'Pengembalian dana dilakukan lewat transfer ke rekening atas nama pembeli, paling lambat 7 hari kerja setelah disetujui.',
                        ],
                    },
                ],
            },
            KONTAK,
        ],
    },
    {
        slug: 'kebijakan-cookie',
        judul: 'Kebijakan Cookie',
        label: 'Kebijakan Cookie',
        pembuka: 'Cookie adalah file kecil yang disimpan browser Anda. Halaman ini menjelaskan cookie dan penyimpanan browser yang dipakai situs ini.',
        bagian: [
            {
                judul: 'Cookie yang Kami Pakai',
                isi: [
                    'Situs ini hanya memakai cookie yang diperlukan agar situs berfungsi:',
                    {
                        daftar: [
                            'Cookie sesi: menyimpan isi keranjang belanja dan status login selama Anda berkunjung.',
                            'Cookie keamanan (XSRF-TOKEN): melindungi formulir dari permintaan palsu.',
                        ],
                    },
                    'Selain cookie, situs ini menyimpan penanda di penyimpanan browser (localStorage atau sessionStorage) agar banner promo tidak muncul berulang kali setelah Anda menutupnya.',
                ],
            },
            {
                judul: 'Statistik dan Iklan',
                isi: [
                    'Statistik kunjungan kami dihitung di server tanpa cookie dan tanpa menyimpan alamat IP. Saat ini kami tidak memasang cookie iklan atau pelacak pihak ketiga. Bila nanti dipasang (misalnya untuk Google Ads atau Meta), halaman ini akan diperbarui terlebih dahulu.',
                ],
            },
            {
                judul: 'Mengatur Cookie',
                isi: [
                    'Anda dapat menghapus atau memblokir cookie lewat pengaturan browser. Bila cookie sesi diblokir, keranjang belanja dan login tidak dapat berfungsi.',
                ],
            },
            KONTAK,
        ],
    },
];

/** Tautan legal untuk footer dan teks persetujuan checkout. */
export const tautanLegal = dokumenLegal.map((dokumen) => ({ path: `/${dokumen.slug}`, label: dokumen.label }));
