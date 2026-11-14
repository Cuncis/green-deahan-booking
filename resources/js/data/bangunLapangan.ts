import type { JenisLapangan } from './jenisLapangan';
import { jenisLapangan } from './jenisLapangan';

export interface KartuBangun {
    /** Same icon and name as the homepage card (jenisLapangan). */
    dasar: JenisLapangan;
    nama: string;
    poin: string[];
    durasi: string;
    pesanWa: string;
}

/**
 * Ringkasan 4 jenis lapangan di /bangun-lapangan. Sengaja tanpa harga:
 * harga pasti disusun dalam RAB setelah survey lokasi.
 */
const isi: Record<string, Omit<KartuBangun, 'dasar'>> = {
    Futsal: {
        nama: 'Futsal',
        poin: ['Lantai interlock atau rumput sintetis', 'LED standar pertandingan, pagar, gawang, net', 'Ruang ganti dan tribun (opsional)'],
        durasi: 'Pengerjaan 14 sampai 21 hari',
        pesanWa: 'Halo Green Deahan, saya ingin tanya pembuatan lapangan futsal.',
    },
    'Mini Soccer': {
        nama: 'Mini soccer',
        poin: ['Rumput sintetis grade A atau B', 'Drainase anti genangan', 'Tribun dan pagar keliling'],
        durasi: 'Pengerjaan 21 sampai 30 hari',
        pesanWa: 'Halo Green Deahan, saya ingin tanya pembuatan lapangan mini soccer.',
    },
    Padel: {
        nama: 'Padel',
        poin: ['Rangka baja galvanis, kaca tempered', 'Rumput khusus padel', 'Indoor atau outdoor'],
        durasi: 'Pengerjaan 14 sampai 21 hari',
        pesanWa: 'Halo Green Deahan, saya ingin tanya pembuatan lapangan padel.',
    },
    Badminton: {
        nama: 'Badminton',
        poin: ['Lantai vinyl, interlock, atau kayu', 'Non-slip, ramah lutut', 'LED anti silau, tiang dan net standar'],
        durasi: 'Pengerjaan 21 sampai 35 hari (multi court)',
        pesanWa: 'Halo Green Deahan, saya ingin tanya pembuatan lapangan badminton.',
    },
};

export const kartuBangun: KartuBangun[] = jenisLapangan.map((dasar) => ({ dasar, ...isi[dasar.nama] }));

export const langkahBangun = [
    { judul: 'Konsultasi', teks: 'Ceritakan lokasi dan budget lewat WhatsApp.' },
    { judul: 'Survey dan desain', teks: 'Tim datang, ukur lahan, buat desain.' },
    { judul: 'Penawaran', teks: 'RAB detail, tanpa biaya tersembunyi.' },
    { judul: 'Konstruksi', teks: 'Laporan foto harian sampai selesai.' },
    { judul: 'Serah terima', teks: 'Pelunasan setelah 100% selesai, garansi aktif.' },
];

export const bandingLantai = {
    kolom: ['Interlock', 'Rumput sintetis'],
    baris: [
        { ikon: 'harga', hal: 'Harga', interlock: 'Lebih murah', rumput: 'Lebih mahal' },
        { ikon: 'lokasi', hal: 'Cocok untuk', interlock: 'Indoor', rumput: 'Indoor dan outdoor' },
        { ikon: 'perbaikan', hal: 'Perbaikan', interlock: 'Ganti per keping', rumput: 'Tambal per bagian' },
        { ikon: 'nyaman', hal: 'Kenyamanan', interlock: 'Keras, cepat', rumput: 'Lebih empuk' },
        { ikon: 'rawat', hal: 'Perawatan', interlock: 'Sangat mudah', rumput: 'Perlu sikat dan infill rutin' },
    ],
};
