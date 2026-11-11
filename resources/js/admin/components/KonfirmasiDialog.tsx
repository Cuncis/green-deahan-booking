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
}

/** "Are you sure?" dialog for destructive actions (delete, cancel). */
export default function KonfirmasiDialog({ terbuka, onTutup, onLanjut, judul, deskripsi, labelLanjut, memproses }: KonfirmasiDialogProps) {
    return (
        <Dialog open={terbuka} onOpenChange={(buka) => !buka && onTutup()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{judul}</DialogTitle>
                    <DialogDescription>{deskripsi}</DialogDescription>
                </DialogHeader>
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
