import { ImagePlus } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '../../lib/utils';
import { Button } from '../ui/button';

interface UnggahFotoProps {
    id: string;
    fotoSekarang: string | null;
    file: File | null;
    onPilih: (file: File | null) => void;
    labelTombol?: string;
    /** Always stack preview above the button, for narrow columns. */
    bertumpuk?: boolean;
}

/** Photo picker with a preview of the current or newly chosen image. */
export default function UnggahFoto({ id, fotoSekarang, file, onPilih, labelTombol = 'Pilih foto', bertumpuk = false }: UnggahFotoProps) {
    const [pratinjau, setPratinjau] = useState<string | null>(fotoSekarang);

    useEffect(() => {
        if (!file) {
            setPratinjau(fotoSekarang);
            return;
        }
        const url = URL.createObjectURL(file);
        setPratinjau(url);

        return () => URL.revokeObjectURL(url);
    }, [file, fotoSekarang]);

    return (
        <div className={cn('flex flex-col gap-3', !bertumpuk && 'sm:flex-row sm:items-center')}>
            <div
                className={cn(
                    'flex w-full shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-muted',
                    bertumpuk ? 'aspect-[16/10]' : 'h-28 sm:w-40',
                )}
            >
                {pratinjau ? (
                    <img src={pratinjau} alt="Pratinjau foto" className="size-full object-cover" />
                ) : (
                    <ImagePlus className="size-8 text-muted-foreground" aria-hidden="true" />
                )}
            </div>
            <div className="min-w-0 space-y-1.5">
                <Button asChild variant="outline">
                    <label htmlFor={id} className="cursor-pointer">
                        <ImagePlus />
                        {file ? 'Ganti foto' : labelTombol}
                    </label>
                </Button>
                <input id={id} type="file" accept="image/*" className="sr-only" onChange={(event) => onPilih(event.target.files?.[0] ?? null)} />
                <p className="max-w-xs truncate text-xs text-muted-foreground">{file ? file.name : 'JPG atau PNG, maksimal 4 MB.'}</p>
            </div>
        </div>
    );
}
