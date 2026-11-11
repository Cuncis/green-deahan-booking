import { CircleAlert, CircleCheck, MessageCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import { bukaChatWhatsApp } from '../../admin/lib/whatsapp';
import { Button } from '../../admin/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '../../admin/components/ui/dialog';
import { api } from '../lib/api';
import { rupiah } from '../lib/aksen';
import type { StatusBooking } from '../lib/types';

interface Props {
    kodeBooking: string;
    totalAwal: number | null;
    galatPembayaran: string | null;
    namaBisnis: string;
    whatsappBisnis: string | null;
    menitPantau: number;
    onTutup: () => void;
}

const JEDA_MS = 4000;

const TEKS: Record<'menunggu' | 'dikonfirmasi' | 'dibatalkan', { judul: string; isi: string }> = {
    menunggu: { judul: 'Booking Berhasil Dibuat', isi: 'Slot kamu sudah diamankan. Kami sedang menunggu konfirmasi pembayaran.' },
    dikonfirmasi: { judul: 'Pembayaran Diterima', isi: 'Terima kasih, pembayaran kamu sudah diterima dan booking sudah dikonfirmasi.' },
    dibatalkan: { judul: 'Pembayaran Dibatalkan', isi: 'Pembayaran tidak berhasil, slot sudah dilepas kembali. Silakan booking ulang atau hubungi admin.' },
};

/**
 * Shown after booking (when the payment link could not be created) or when
 * the customer comes back from the payment page. Polls the booking status
 * until the payment webhook confirms or cancels it.
 */
export default function StatusPembayaran({ kodeBooking, totalAwal, galatPembayaran, namaBisnis, whatsappBisnis, menitPantau, onTutup }: Props) {
    const [status, setStatus] = useState<StatusBooking>('menunggu');
    const [total, setTotal] = useState<number | null>(totalAwal);

    useEffect(() => {
        let sisa = Math.floor((menitPantau * 60 * 1000) / JEDA_MS);
        let selesai = false;

        const cek = async () => {
            try {
                const hasil = await api<{ data: { status_booking: StatusBooking; total_bayar: number } }>(`/api/booking/${encodeURIComponent(kodeBooking)}/status`);
                if (selesai) {
                    return;
                }
                setTotal(hasil.data.total_bayar);
                if (hasil.data.status_booking !== 'menunggu') {
                    setStatus(hasil.data.status_booking);
                    selesai = true;
                }
            } catch {
                // Coba lagi di putaran berikutnya.
            }
        };

        cek();
        const jeda = window.setInterval(() => {
            sisa -= 1;
            if (selesai || sisa <= 0) {
                window.clearInterval(jeda);
                return;
            }
            cek();
        }, JEDA_MS);

        return () => {
            selesai = true;
            window.clearInterval(jeda);
        };
    }, [kodeBooking, menitPantau]);

    const teks = TEKS[status === 'selesai' ? 'dikonfirmasi' : status];
    const berhasil = status === 'dikonfirmasi' || status === 'selesai';
    const linkWa = whatsappBisnis
        ? `https://wa.me/${whatsappBisnis}?text=${encodeURIComponent(`Halo, saya sudah booking di ${namaBisnis}.\n\nKode booking: ${kodeBooking}\nMohon info langkah pembayaran selanjutnya. Terima kasih.`)}`
        : null;

    return (
        <Dialog open onOpenChange={(buka) => !buka && onTutup()}>
            <DialogContent className="text-center sm:max-w-sm">
                <div
                    className={`mx-auto mb-1 flex size-14 items-center justify-center rounded-full ${status === 'dibatalkan' ? 'bg-danger-pale text-destructive' : 'bg-green-pale text-green'}`}
                >
                    {status === 'dibatalkan' ? <CircleAlert className="size-7" /> : <CircleCheck className="size-7" />}
                </div>
                <DialogHeader className="text-center sm:text-center">
                    <DialogTitle>{teks.judul}</DialogTitle>
                    <DialogDescription>{teks.isi}</DialogDescription>
                </DialogHeader>

                <div className="rounded-lg bg-muted px-4 py-3 text-left text-sm">
                    <div className="flex justify-between py-0.5">
                        <span className="text-muted-foreground">Kode booking</span>
                        <strong className="tracking-wide">{kodeBooking}</strong>
                    </div>
                    {total !== null && (
                        <div className="flex justify-between py-0.5">
                            <span className="text-muted-foreground">Total</span>
                            <strong>{rupiah(total)}</strong>
                        </div>
                    )}
                </div>

                {status === 'menunggu' && (
                    <div className="flex items-center justify-center gap-2 text-xs text-muted-foreground" aria-live="polite">
                        <span className="size-1.5 animate-pulse rounded-full bg-amber" />
                        Menunggu konfirmasi pembayaran
                    </div>
                )}

                {galatPembayaran && (
                    <p className="text-sm text-destructive" role="alert">
                        {galatPembayaran}
                    </p>
                )}

                <div className="grid gap-2">
                    {!berhasil && linkWa && (
                        <Button className="w-full" onClick={() => bukaChatWhatsApp(linkWa)}>
                            <MessageCircle />
                            Chat Admin di WhatsApp
                        </Button>
                    )}
                    <Button variant={berhasil ? 'default' : 'ghost'} className="w-full" onClick={onTutup}>
                        {berhasil ? 'Selesai' : 'Tutup'}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
