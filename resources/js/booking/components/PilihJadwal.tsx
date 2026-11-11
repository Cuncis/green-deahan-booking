import { CircleAlert, LoaderCircle, RotateCw } from 'lucide-react';
import { Skeleton } from '../../admin/components/ui/skeleton';
import { cn } from '../../admin/lib/utils';
import { rupiah } from '../lib/aksen';
import type { PilihanTanggal, Slot } from '../lib/types';

interface Props {
    tanggalPilihan: PilihanTanggal[];
    tanggal: string;
    onTanggal: (tanggal: string) => void;
    slot: Slot[] | null;
    galatMuat: string | null;
    onMuatUlang: () => void;
    slotDipilih: number | null;
    slotDiproses: number | null;
    pesan: string | null;
    onPilihSlot: (slot: Slot) => void;
}

const LABEL_TIDAK_TERSEDIA: Record<Slot['status'], string> = {
    tersedia: '',
    terisi: 'Terisi',
    tutup: 'Tutup',
    lewat: 'Lewat',
};

/** Date strip (7 days) and the hour grid for the chosen court. */
export default function PilihJadwal({ tanggalPilihan, tanggal, onTanggal, slot, galatMuat, onMuatUlang, slotDipilih, slotDiproses, pesan, onPilihSlot }: Props) {
    return (
        <div>
            <h2 className="mb-3 text-xs font-bold uppercase tracking-wide text-[var(--aksen)]">Pilih Tanggal</h2>
            <div className="-mx-4 mb-6 flex snap-x gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0" role="radiogroup" aria-label="Tanggal main">
                {tanggalPilihan.map((pilihan) => {
                    const aktif = pilihan.tanggal === tanggal;

                    return (
                        <button
                            key={pilihan.tanggal}
                            type="button"
                            role="radio"
                            aria-checked={aktif}
                            aria-label={pilihan.label}
                            onClick={() => onTanggal(pilihan.tanggal)}
                            className={cn(
                                'flex h-16 w-14 flex-none snap-start flex-col items-center justify-center rounded-xl border transition-colors',
                                aktif ? 'border-[var(--aksen)] bg-[var(--aksen)] text-[var(--aksen-teks)]' : 'border-border bg-card text-ink-mid hover:border-[var(--aksen)]',
                            )}
                        >
                            <span className={cn('text-xs uppercase', aktif ? 'opacity-90' : 'text-muted-foreground')}>{pilihan.hari}</span>
                            <span className="text-lg font-bold leading-tight">{pilihan.angka}</span>
                        </button>
                    );
                })}
            </div>

            <h2 className="mb-3 text-xs font-bold uppercase tracking-wide text-[var(--aksen)]">Pilih Jam</h2>

            {pesan && (
                <div className="mb-3 flex items-start gap-2 rounded-lg bg-danger-pale px-3 py-2.5 text-sm text-destructive" role="alert">
                    <CircleAlert className="mt-0.5 size-4 shrink-0" />
                    {pesan}
                </div>
            )}

            {galatMuat ? (
                <div className="rounded-xl border border-dashed border-border px-4 py-6 text-center text-sm text-ink-mid">
                    <p className="mb-3">{galatMuat}</p>
                    <button
                        type="button"
                        onClick={onMuatUlang}
                        className="inline-flex min-h-10 items-center gap-1.5 font-semibold text-[var(--aksen)] underline underline-offset-2"
                    >
                        <RotateCw className="size-4" />
                        Muat ulang jadwal
                    </button>
                </div>
            ) : slot === null ? (
                <div className="grid grid-cols-3 gap-2.5 sm:grid-cols-4" aria-label="Memuat jadwal">
                    {Array.from({ length: 8 }, (_, i) => (
                        <Skeleton key={i} className="h-[3.75rem] rounded-xl" />
                    ))}
                </div>
            ) : slot.length === 0 ? (
                <p className="rounded-xl border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground">Belum ada jadwal untuk tanggal ini.</p>
            ) : (
                <div className="grid grid-cols-3 gap-2.5 sm:grid-cols-4">
                    {slot.map((item) => {
                        const dipilih = item.id === slotDipilih;
                        const diproses = item.id === slotDiproses;
                        const bisaDipilih = item.tersedia || dipilih;

                        return (
                            <button
                                key={item.id}
                                type="button"
                                disabled={!bisaDipilih || slotDiproses !== null}
                                aria-pressed={dipilih}
                                onClick={() => !dipilih && onPilihSlot(item)}
                                className={cn(
                                    'relative flex h-[3.75rem] flex-col items-center justify-center rounded-xl border text-center transition-colors disabled:cursor-not-allowed',
                                    dipilih && 'border-[var(--aksen)] bg-[var(--aksen)] text-[var(--aksen-teks)]',
                                    !dipilih && item.tersedia && 'border-border bg-card text-foreground hover:border-[var(--aksen)] hover:bg-[var(--aksen-pucat)]',
                                    !bisaDipilih && 'border-transparent bg-muted text-muted-foreground',
                                )}
                            >
                                <span className={cn('text-sm font-bold', !bisaDipilih && 'line-through decoration-1')}>{item.jam_mulai}</span>
                                <span className={cn('text-xs', dipilih ? 'opacity-90' : 'text-muted-foreground')}>
                                    {dipilih ? 'Dipilih' : bisaDipilih ? rupiah(item.harga) : LABEL_TIDAK_TERSEDIA[item.status]}
                                </span>
                                {diproses && (
                                    <span className="absolute inset-0 flex items-center justify-center rounded-xl bg-card/80">
                                        <LoaderCircle className="size-5 animate-spin text-[var(--aksen)]" aria-label="Menahan slot" />
                                    </span>
                                )}
                            </button>
                        );
                    })}
                </div>
            )}

            <div className="mt-4 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-muted-foreground">
                <span className="inline-flex items-center gap-1.5">
                    <i className="inline-block size-2.5 rounded-sm border border-border bg-card" /> Tersedia
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <i className="inline-block size-2.5 rounded-sm bg-[var(--aksen)]" /> Dipilih
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <i className="inline-block size-2.5 rounded-sm bg-muted" /> Tidak tersedia
                </span>
            </div>
        </div>
    );
}
