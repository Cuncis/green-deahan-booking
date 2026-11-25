import { Images, ImagePlus } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '../ui/button';
import PilihMedia, { type MediaPilihan } from './PilihMedia';

interface PilihGambarProps {
    id: string;
    gambarSekarang: string | null;
    file: File | null;
    media: MediaPilihan | null;
    onFile: (file: File | null) => void;
    onMedia: (media: MediaPilihan | null) => void;
}

/**
 * Image field with two sources: upload from this computer, or pick a file that is
 * already in the Media library. Choosing one source clears the other.
 */
export default function PilihGambar({ id, gambarSekarang, file, media, onFile, onMedia }: PilihGambarProps) {
    const [dialogMedia, setDialogMedia] = useState(false);
    const [pratinjauFile, setPratinjauFile] = useState<string | null>(null);

    useEffect(() => {
        if (!file) {
            setPratinjauFile(null);
            return;
        }
        const url = URL.createObjectURL(file);
        setPratinjauFile(url);

        return () => URL.revokeObjectURL(url);
    }, [file]);

    const pratinjau = pratinjauFile ?? media?.url ?? gambarSekarang;
    const keterangan = file ? file.name : media ? `Dari Media: ${media.nama}` : gambarSekarang ? 'Gambar saat ini' : 'Belum ada gambar dipilih.';

    return (
        <div className="flex flex-col gap-3">
            <div className="flex aspect-[16/10] w-full items-center justify-center overflow-hidden rounded-xl border border-border bg-muted">
                {pratinjau ? (
                    <img src={pratinjau} alt="Pratinjau gambar banner" className="size-full object-contain" />
                ) : (
                    <ImagePlus className="size-8 text-muted-foreground" aria-hidden="true" />
                )}
            </div>
            <div className="flex flex-wrap gap-2">
                <Button asChild variant="outline">
                    <label htmlFor={id} className="cursor-pointer">
                        <ImagePlus />
                        Upload dari komputer
                    </label>
                </Button>
                <input
                    id={id}
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    className="sr-only"
                    onChange={(event) => {
                        const dipilih = event.target.files?.[0] ?? null;
                        onFile(dipilih);
                        if (dipilih) onMedia(null);
                        event.target.value = '';
                    }}
                />
                <Button type="button" variant="outline" onClick={() => setDialogMedia(true)}>
                    <Images />
                    Pilih dari Media
                </Button>
            </div>
            <p className="truncate text-xs text-muted-foreground">{keterangan}</p>
            <PilihMedia
                terbuka={dialogMedia}
                onTutup={() => setDialogMedia(false)}
                terpilih={media?.path ?? null}
                onPilih={(dipilih) => {
                    onMedia(dipilih);
                    onFile(null);
                    setDialogMedia(false);
                }}
            />
        </div>
    );
}
