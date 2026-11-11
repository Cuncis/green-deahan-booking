export interface Bisnis {
    nama: string;
    logoUrl: string | null;
    warnaUtama: string;
    whatsapp: string | null;
}

export interface Fitur {
    multiCabang: boolean;
    kodePromo: boolean;
    membership: boolean;
    reminder: boolean;
    tipePembayaran: TipePembayaran[];
}

export type TipePembayaran = 'dp' | 'lunas';

export interface Cabang {
    id: number;
    nama: string;
}

export interface Lapangan {
    id: number;
    nama: string;
    jenis: string;
    hargaPerJam: number;
    fotoUrl: string | null;
    cabangId: number | null;
    alamat: string | null;
    jamBuka: string | null;
    jamTutup: string | null;
}

export interface PilihanTanggal {
    tanggal: string;
    hari: string;
    angka: number;
    label: string;
}

/** GET /api/booking/lapangan/{id}/slot */
export interface Slot {
    id: number;
    jam_mulai: string;
    jam_selesai: string;
    harga: number;
    status: 'tersedia' | 'terisi' | 'tutup' | 'lewat';
    tersedia: boolean;
}

/** POST /api/booking/slot/{id}/hold */
export interface Hold {
    slot_id: number;
    tanggal: string;
    jam_mulai: string;
    jam_selesai: string;
    harga: number;
    hold_token: string;
    hold_sampai: string;
}

/** POST /api/booking/harga */
export interface Ringkasan {
    harga_normal: number;
    member: { tier: string; persen: number } | null;
    diskon_member: number;
    status_promo: 'kosong' | 'valid' | 'tidak_valid' | 'habis';
    kode_promo: string | null;
    diskon_promo: number;
    total_setelah_diskon: number;
    tipe_pembayaran: TipePembayaran;
    total_bayar: number;
}

/** POST /api/booking/cek-membership */
export interface InfoMember {
    tier: string;
    persen_diskon: number;
    harga_member: number;
}

/** POST /api/booking (201) */
export interface HasilBooking {
    data: { kode_booking: string; total_bayar: number };
    pembayaran?: { redirect_url?: string };
    pembayaran_error?: string;
}

export type StatusBooking = 'menunggu' | 'dikonfirmasi' | 'selesai' | 'dibatalkan';
