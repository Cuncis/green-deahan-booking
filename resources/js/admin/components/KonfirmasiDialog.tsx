import type { ReactNode } from 'react';
import { Button } from './ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from './ui/dialog';

interface KonfirmasiDialogProps {
    terbuka: boolean;
    onTutup: () => void;
    onLanjut: () => void;
    judul: string;
    deskripsi: string;
    labelLanjut: string;
    memproses?: boolean;
    /** Extra content between the description and the buttons, e.g. a reason field. */
    children?: ReactNode;
}

/** "Are you sure?" dialog for destructive actions (delete, cancel). */
export default function KonfirmasiDialog({ terbuka, onTutup, onLanjut, judul, deskripsi, labelLanjut, memproses, children }: KonfirmasiDialogProps) {
    return (
        <Dialog open={terbuka} onOpenChange={(buka) => !buka && onTutup()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{judul}</DialogTitle>
                    <DialogDescription>{deskripsi}</DialogDescription>
                </DialogHeader>
                {children}
                <DialogFooter>
                    <Button variant="outline" onClick={onTutup}>
                        Batal
                    </Button>
                    <Button variant="destructive" disabled={memproses} onClick={onLanjut}>
                        {labelLanjut}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
