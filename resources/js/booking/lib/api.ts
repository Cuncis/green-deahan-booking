/** Error from the booking API, with Laravel validation errors when status is 422. */
export class GalatApi extends Error {
    constructor(
        public readonly status: number,
        message: string,
        public readonly galatKolom: Record<string, string[]> = {},
    ) {
        super(message);
    }
}

const PESAN_BAWAAN: Record<number, string> = {
    404: 'Data tidak ditemukan, coba muat ulang halaman.',
    429: 'Terlalu banyak percobaan, tunggu sebentar lalu coba lagi.',
    500: 'Terjadi kesalahan di server, coba lagi sebentar lagi.',
};

/**
 * JSON call to /api/booking/*. These routes are stateless (no session or
 * CSRF), the tenant comes from the domain.
 */
export async function api<T>(url: string, opsi: { method?: string; body?: unknown; signal?: AbortSignal } = {}): Promise<T> {
    let respons: Response;

    try {
        respons = await fetch(url, {
            method: opsi.method ?? 'GET',
            headers: { Accept: 'application/json', ...(opsi.body !== undefined ? { 'Content-Type': 'application/json' } : {}) },
            body: opsi.body !== undefined ? JSON.stringify(opsi.body) : undefined,
            signal: opsi.signal,
        });
    } catch (galat) {
        if (galat instanceof DOMException && galat.name === 'AbortError') {
            throw galat;
        }
        throw new GalatApi(0, 'Koneksi terputus, periksa internet kamu lalu coba lagi.');
    }

    const isi = await respons.json().catch(() => ({}));

    if (!respons.ok) {
        throw new GalatApi(respons.status, isi.message || PESAN_BAWAAN[respons.status] || 'Terjadi kesalahan, coba lagi.', isi.errors ?? {});
    }

    return isi as T;
}

export const dibatalkan = (galat: unknown): boolean => galat instanceof DOMException && galat.name === 'AbortError';
