import { cn } from '../../lib/utils';
import { angka, rupiah } from '../../lib/format';

export interface DataOngkir {
    rajaongkir: {
        aktif: boolean;
        batas: number;
        sisaAman: number;
        terpakai: number;
        tersisa: number;
        alasanHabis: 'batas' | 'ditolak' | null;
        terakhir: { waktu: string; aksi: string; berhasil: boolean; pesan: string | null } | null;
    };
    biteship: { aktif: boolean; hariIni: number; bulanIni: number; tarifPerPermintaan: number; biayaBulanIni: number };
}

/** Status line for the RajaOngkir daily quota. */
export function statusRajaOngkir(data: DataOngkir['rajaongkir']): { teks: string; kelas: string } {
    if (!data.aktif) return { teks: 'Belum ada API key', kelas: 'bg-muted text-muted-foreground' };
    if (data.alasanHabis === 'ditolak') return { teks: 'Ditolak RajaOngkir, memakai Biteship', kelas: 'bg-danger-pale text-destructive' };
    if (data.alasanHabis === 'batas') return { teks: 'Batas harian habis, memakai Biteship', kelas: 'bg-gold-pale text-ink' };

    return { teks: 'Aktif', kelas: 'bg-green-pale text-green' };
}

/** RajaOngkir usage today (used / daily limit with a bar) and the Biteship fallback usage with its estimated cost. */
export default function PemakaianOngkir({ data }: { data: DataOngkir }) {
    const { rajaongkir: ro, biteship } = data;
    const persen = ro.batas > 0 ? Math.min(100, Math.round((ro.terpakai / ro.batas) * 100)) : 0;
    const status = statusRajaOngkir(ro);

    return (
        <div className="grid gap-px bg-border md:grid-cols-2">
            <div className="bg-card p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="text-xs font-semibold text-muted-foreground">RajaOngkir (gratis, utama)</div>
                    <span className={cn('rounded-full px-2.5 py-0.5 text-xs font-semibold', status.kelas)}>{status.teks}</span>
                </div>
                <div className="mt-1 font-display text-xl font-semibold">
                    {angka(ro.terpakai)} <span className="text-sm font-medium text-muted-foreground">dari {angka(ro.batas)} permintaan hari ini</span>
                </div>
                <div
                    className="mt-2 h-2 overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    aria-valuenow={ro.terpakai}
                    aria-valuemin={0}
                    aria-valuemax={ro.batas}
                    aria-label="Pemakaian RajaOngkir hari ini"
                >
                    <div
                        className={cn('h-full rounded-full transition-all', persen >= 90 ? 'bg-destructive' : persen >= 70 ? 'bg-gold' : 'bg-green')}
                        style={{ width: `${persen}%` }}
                    />
                </div>
                {ro.aktif && (
                    <p className={cn('mt-1.5 text-xs', ro.terakhir && !ro.terakhir.berhasil ? 'font-semibold text-destructive' : 'text-muted-foreground')}>
                        {ro.terakhir
                            ? `Permintaan terakhir ${ro.terakhir.waktu} WIB (${ro.terakhir.aksi}): ${ro.terakhir.berhasil ? 'berhasil' : `gagal${ro.terakhir.pesan ? `, ${ro.terakhir.pesan}` : ''}`}.`
                            : 'Belum ada permintaan ke RajaOngkir sejak catatan ini dibuat. Hitungan naik saat pelanggan menghitung ongkir di checkout.'}
                    </p>
                )}
                <p className="mt-1.5 text-xs text-muted-foreground">
                    {ro.aktif
                        ? `Sisa ${angka(ro.tersisa)} permintaan sebelum Biteship dipakai. Hitungan nol lagi tiap tengah malam WIB.`
                        : 'Isi RAJAONGKIR_API_KEY di .env untuk mengaktifkan.'}
                </p>
            </div>
            <div className="bg-card p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="text-xs font-semibold text-muted-foreground">Biteship (cadangan, berbayar)</div>
                    <span className={cn('rounded-full px-2.5 py-0.5 text-xs font-semibold', biteship.aktif ? 'bg-green-pale text-green' : 'bg-muted text-muted-foreground')}>
                        {biteship.aktif ? 'Siap' : 'Belum ada API key'}
                    </span>
                </div>
                <div className="mt-1 font-display text-xl font-semibold">
                    {angka(biteship.hariIni)} <span className="text-sm font-medium text-muted-foreground">permintaan hari ini</span>
                </div>
                <p className="mt-2 text-xs text-muted-foreground">
                    Bulan ini {angka(biteship.bulanIni)} permintaan, perkiraan biaya {rupiah(biteship.biayaBulanIni)} (Rp{angka(biteship.tarifPerPermintaan)} per permintaan).
                </p>
            </div>
        </div>
    );
}
