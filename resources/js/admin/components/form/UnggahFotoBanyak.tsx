import { ImagePlus, Star, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';

export interface FotoAda {
    id: number;
    url: string;
}

interface UnggahFotoBanyakProps {
    id: string;
    /** Saved photos, in display order (the first one is the main photo). */
    fotoAda: FotoAda[];
    onFotoAda: (foto: FotoAda[]) => void;
    /** Photos still to be downloaded from the Shopee CDN when the product is saved. */
    alamatImpor: string[];
    onAlamatImpor: (alamat: string[]) => void;
    /** Files picked in this session. */
    fotoBaru: File[];
    onFotoBaru: (file: File[]) => void;
    maks: number;
    /** Key of the photo chosen as main photo (see kunciFoto); null = the first photo. */
    utama: string | null;
    onUtama: (kunci: string | null) => void;
}

type Ubin = { kunci: string; url: string; aksi: 'ada' | 'impor' | 'baru'; indeks: number };

/** Stable key per photo, so the chosen main photo survives removing other photos. */
const kunciAda = (foto: FotoAda) => `ada-${foto.id}`;
const kunciImpor = (url: string) => `impor-${url}`;
const kunciBaru = (file: File) => `baru-${file.name}-${file.size}-${file.lastModified}`;

function daftarUbin(fotoAda: FotoAda[], alamatImpor: string[], fotoBaru: File[]): Ubin[] {
    return [
        ...fotoAda.map((foto, indeks) => ({ kunci: kunciAda(foto), url: foto.url, aksi: 'ada' as const, indeks })),
        ...alamatImpor.map((url, indeks) => ({ kunci: kunciImpor(url), url, aksi: 'impor' as const, indeks })),
        ...fotoBaru.map((file, indeks) => ({ kunci: kunciBaru(file), url: '', aksi: 'baru' as const, indeks })),
    ];
}

/**
 * The chosen main photo as the server expects it in "foto_utama":
 * "ada:{id}", "impor:{index}" or "baru:{index}". Null when none was chosen
 * (or it was removed), so the server keeps the first photo.
 */
export function fotoUtamaUntukServer(utama: string | null, fotoAda: FotoAda[], alamatImpor: string[], fotoBaru: File[]): string | null {
    const ubin = daftarUbin(fotoAda, alamatImpor, fotoBaru).find((item) => item.kunci === utama);
    if (!ubin) return null;

    return ubin.aksi === 'ada' ? `ada:${fotoAda[ubin.indeks].id}` : `${ubin.aksi}:${ubin.indeks}`;
}

/** Preview URL for a picked file, released when the tile goes away. */
function useUrlFile(file: File | null): string | null {
    const [url, setUrl] = useState<string | null>(null);

    useEffect(() => {
        if (!file) {
            setUrl(null);
            return;
        }
        const objek = URL.createObjectURL(file);
        setUrl(objek);

        return () => URL.revokeObjectURL(objek);
    }, [file]);

    return url;
}

function UbinFile({ file, children }: { file: File; children: (url: string | null) => React.ReactNode }) {
    return <>{children(useUrlFile(file))}</>;
}

function Tombol({ label, onClick, children }: { label: string; onClick: () => void; children: React.ReactNode }) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={label}
            title={label}
            className="flex size-8 items-center justify-center rounded-full bg-background/90 text-foreground shadow transition hover:bg-background"
        >
            {children}
        </button>
    );
}

interface GambarProps {
    url: string | null;
    utama: boolean;
    dariShopee: boolean;
    onHapus: () => void;
    onUtama: () => void;
}

function Gambar({ url, utama, dariShopee, onHapus, onUtama }: GambarProps) {
    return (
        <div className={cn('group relative aspect-square overflow-hidden rounded-xl border bg-muted', utama ? 'border-primary ring-2 ring-primary/30' : 'border-border')}>
            {url ? <img src={url} alt="" className="size-full object-cover" /> : <ImagePlus className="m-auto size-8 text-muted-foreground" aria-hidden="true" />}
            <div className="absolute left-1.5 top-1.5 flex flex-wrap gap-1">
                {utama && <span className="rounded-full bg-primary px-2 py-0.5 text-[11px] font-semibold text-primary-foreground">Utama</span>}
                {dariShopee && <span className="rounded-full bg-background/90 px-2 py-0.5 text-[11px] text-muted-foreground">Shopee</span>}
            </div>
            <div className="absolute right-1.5 top-1.5">
                <Tombol label="Hapus foto" onClick={onHapus}>
                    <X className="size-4" />
                </Tombol>
            </div>
            {!utama && (
                <button
                    type="button"
                    onClick={onUtama}
                    className="absolute inset-x-1.5 bottom-1.5 flex min-h-8 items-center justify-center gap-1 rounded-lg bg-background/90 px-2 text-xs font-semibold text-foreground shadow transition hover:bg-primary hover:text-primary-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                    <Star className="size-3.5" aria-hidden="true" />
                    Jadikan utama
                </button>
            )}
        </div>
    );
}

/**
 * Several product photos: saved ones (remove, make main), photos imported from
 * Shopee (downloaded on save), and new files. The first tile is the main photo.
 */
export default function UnggahFotoBanyak({ id, fotoAda, onFotoAda, alamatImpor, onAlamatImpor, fotoBaru, onFotoBaru, maks, utama, onUtama }: UnggahFotoBanyakProps) {
    const jumlah = fotoAda.length + alamatImpor.length + fotoBaru.length;

    // The main photo is shown first, the rest keep their order.
    const semua = daftarUbin(fotoAda, alamatImpor, fotoBaru);
    const pilihan = semua.find((item) => item.kunci === utama) ?? semua[0];
    const ubin: Ubin[] = pilihan ? [pilihan, ...semua.filter((item) => item !== pilihan)] : [];

    const hapus = (item: Ubin) => {
        if (item.aksi === 'ada') onFotoAda(fotoAda.filter((_, i) => i !== item.indeks));
        if (item.aksi === 'impor') onAlamatImpor(alamatImpor.filter((_, i) => i !== item.indeks));
        if (item.aksi === 'baru') onFotoBaru(fotoBaru.filter((_, i) => i !== item.indeks));
    };

    const tambah = (daftar: FileList | null) => {
        if (!daftar) return;
        onFotoBaru([...fotoBaru, ...Array.from(daftar)].slice(0, maks - fotoAda.length - alamatImpor.length));
    };

    return (
        <div className="space-y-3">
            <div className="grid grid-cols-3 gap-3 sm:grid-cols-4">
                {ubin.map((item, urutan) =>
                    item.aksi === 'baru' ? (
                        <UbinFile key={item.kunci} file={fotoBaru[item.indeks]}>
                            {(url) => <Gambar url={url} utama={urutan === 0} dariShopee={false} onHapus={() => hapus(item)} onUtama={() => onUtama(item.kunci)} />}
                        </UbinFile>
                    ) : (
                        <Gambar
                            key={item.kunci}
                            url={item.url}
                            utama={urutan === 0}
                            dariShopee={item.aksi === 'impor'}
                            onHapus={() => hapus(item)}
                            onUtama={() => onUtama(item.kunci)}
                        />
                    ),
                )}
                {jumlah < maks && (
                    <label
                        htmlFor={id}
                        className="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-border text-sm text-muted-foreground transition hover:bg-muted"
                    >
                        <ImagePlus className="size-6" aria-hidden="true" />
                        Tambah foto
                    </label>
                )}
            </div>
            <input
                id={id}
                type="file"
                accept="image/*"
                multiple
                className="sr-only"
                onChange={(event) => {
                    tambah(event.target.files);
                    event.target.value = '';
                }}
            />
            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                <span>
                    {jumlah} dari {maks} foto.
                </span>
                {fotoBaru.length + fotoAda.length + alamatImpor.length > 0 && (
                    <Button type="button" variant="ghost" size="sm" onClick={() => (onFotoAda([]), onAlamatImpor([]), onFotoBaru([]), onUtama(null))}>
                        Hapus semua
                    </Button>
                )}
            </div>
        </div>
    );
}
