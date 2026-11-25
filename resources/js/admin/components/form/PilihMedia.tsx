import { Check, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '../ui/dialog';
import { Input } from '../ui/input';

export interface MediaPilihan {
    path: string;
    nama: string;
    folder: string;
    url: string;
}

interface PilihMediaProps {
    terbuka: boolean;
    onTutup: () => void;
    onPilih: (media: MediaPilihan) => void;
    /** Path of the file already chosen, highlighted in the grid. */
    terpilih?: string | null;
}

interface Respons {
    items: MediaPilihan[];
    adaLagi: boolean;
}

/** Dialog that lists the images of the Media library (searchable, paged) and returns the one picked. */
export default function PilihMedia({ terbuka, onTutup, onPilih, terpilih = null }: PilihMediaProps) {
    const [kata, setKata] = useState('');
    const [kataCari, setKataCari] = useState('');
    const [daftar, setDaftar] = useState<MediaPilihan[]>([]);
    const [halaman, setHalaman] = useState(1);
    const [adaLagi, setAdaLagi] = useState(false);
    const [memuat, setMemuat] = useState(false);
    const [galat, setGalat] = useState('');

    // Wait a moment after typing so the list is not refetched on every keystroke.
    useEffect(() => {
        const tunda = setTimeout(() => setKataCari(kata.trim()), 300);

        return () => clearTimeout(tunda);
    }, [kata]);

    useEffect(() => {
        setHalaman(1);
    }, [kataCari, terbuka]);

    useEffect(() => {
        if (!terbuka) return;

        const batal = new AbortController();
        setMemuat(true);
        setGalat('');
        fetch(`/superadmin/media/daftar?${new URLSearchParams({ q: kataCari, page: String(halaman) })}`, { headers: { Accept: 'application/json' }, signal: batal.signal })
            .then(async (respons) => {
                if (!respons.ok) throw new Error('Daftar media belum bisa dimuat, coba lagi.');

                return (await respons.json()) as Respons;
            })
            .then((data) => {
                setDaftar((lama) => (halaman === 1 ? data.items : [...lama, ...data.items]));
                setAdaLagi(data.adaLagi);
                setMemuat(false);
            })
            .catch((error: Error) => {
                if (error.name !== 'AbortError') {
                    setGalat(error.message);
                    setMemuat(false);
                }
            });

        return () => batal.abort();
    }, [terbuka, kataCari, halaman]);

    return (
        <Dialog open={terbuka} onOpenChange={(buka) => !buka && onTutup()}>
            <DialogContent className="max-h-[90dvh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Pilih dari Media</DialogTitle>
                    <DialogDescription>Gambar yang sudah ada di pustaka Media. Format JPG, PNG, atau WebP.</DialogDescription>
                </DialogHeader>
                <div className="relative">
                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <Input value={kata} onChange={(event) => setKata(event.target.value)} placeholder="Cari nama file atau folder" className="pl-9" aria-label="Cari media" />
                </div>
                {galat && (
                    <p className="text-sm text-destructive" role="alert">
                        {galat}
                    </p>
                )}
                {!galat && !memuat && daftar.length === 0 && <p className="py-8 text-center text-sm text-muted-foreground">Tidak ada gambar yang cocok.</p>}
                <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    {daftar.map((media) => (
                        <li key={media.path}>
                            <button
                                type="button"
                                onClick={() => onPilih(media)}
                                className={cn(
                                    'group relative block w-full overflow-hidden rounded-xl border bg-muted text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                                    terpilih === media.path ? 'border-green ring-2 ring-green' : 'border-border hover:border-green',
                                )}
                                aria-label={`Pilih ${media.nama}`}
                                aria-pressed={terpilih === media.path}
                            >
                                <img src={media.url} alt="" loading="lazy" className="aspect-square w-full object-cover" />
                                {terpilih === media.path && (
                                    <span className="absolute right-2 top-2 flex size-6 items-center justify-center rounded-full bg-green text-white">
                                        <Check className="size-4" aria-hidden="true" />
                                    </span>
                                )}
                                <span className="block truncate px-2 py-1.5 text-xs text-ink-mid">{media.nama}</span>
                            </button>
                        </li>
                    ))}
                </ul>
                {memuat && <p className="text-center text-sm text-muted-foreground">Memuat...</p>}
                {adaLagi && !memuat && (
                    <Button type="button" variant="outline" onClick={() => setHalaman((nilai) => nilai + 1)}>
                        Muat lebih banyak
                    </Button>
                )}
            </DialogContent>
        </Dialog>
    );
}
