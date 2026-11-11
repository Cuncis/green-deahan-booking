import { router } from '@inertiajs/react';
import { Check, MessageCircle, X } from 'lucide-react';
import { useState } from 'react';
import { rupiah } from '../../lib/format';
import { bukaChatWhatsApp } from '../../lib/whatsapp';
import { Badge, varianStatusBooking } from '../ui/badge';
import { Button } from '../ui/button';
import { Card, CardHeader, CardTitle } from '../ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../ui/dialog';
import { Label } from '../ui/label';
import { Textarea } from '../ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../ui/table';

export interface BookingRingkas {
    id: number;
    kode: string;
    customer: string;
    telepon: string;
    waUrl: string;
    lapangan: string;
    jadwal: string;
    status: string;
    total: number;
    bisaDikonfirmasi: boolean;
    bisaDibatalkan: boolean;
    konfirmasiUrl: string;
    batalkanUrl: string;
}

const labelStatus = (status: string) => status.charAt(0).toUpperCase() + status.slice(1);

function AksiBooking({ booking, onBatalkan }: { booking: BookingRingkas; onBatalkan: (booking: BookingRingkas) => void }) {
    const [memproses, setMemproses] = useState(false);

    return (
        <div className="flex flex-wrap items-center gap-1.5">
            <Button size="sm" onClick={() => bukaChatWhatsApp(booking.waUrl)} aria-label={`Chat WhatsApp ${booking.customer}`}>
                <MessageCircle />
                Chat
            </Button>
            {booking.bisaDikonfirmasi && (
                <Button
                    size="sm"
                    variant="outline"
                    disabled={memproses}
                    onClick={() =>
                        router.post(
                            booking.konfirmasiUrl,
                            {},
                            {
                                preserveScroll: true,
                                onStart: () => setMemproses(true),
                                onFinish: () => setMemproses(false),
                            },
                        )
                    }
                >
                    <Check />
                    Konfirmasi
                </Button>
            )}
            {booking.bisaDibatalkan && (
                <Button size="sm" variant="destructive-soft" onClick={() => onBatalkan(booking)}>
                    <X />
                    Batalkan
                </Button>
            )}
        </div>
    );
}

interface DaftarBookingProps {
    booking: BookingRingkas[];
    judul?: string;
    /** show the booking code column (Semua Booking page) */
    tampilkanKode?: boolean;
    kosong?: string;
    /** rendered under the list, e.g. pagination */
    footer?: React.ReactNode;
}

/**
 * Booking list: table on desktop, cards on phones. Confirm/cancel use the
 * booking endpoints, which send the admin back to the page they were on.
 */
export default function DaftarBooking({ booking, judul = 'Booking Terbaru', tampilkanKode = false, kosong = 'Belum ada booking.', footer }: DaftarBookingProps) {
    const [dibatalkan, setDibatalkan] = useState<BookingRingkas | null>(null);
    const [alasan, setAlasan] = useState('');
    const [memproses, setMemproses] = useState(false);

    const batalkan = () => {
        if (!dibatalkan) {
            return;
        }
        router.post(
            dibatalkan.batalkanUrl,
            { alasan },
            {
                preserveScroll: true,
                onStart: () => setMemproses(true),
                onFinish: () => {
                    setMemproses(false);
                    setDibatalkan(null);
                    setAlasan('');
                },
            },
        );
    };

    return (
        <Card className="mb-6 overflow-hidden">
            <CardHeader className="border-b border-border py-4">
                <CardTitle className="text-xs uppercase tracking-wide text-green">{judul}</CardTitle>
            </CardHeader>

            {booking.length === 0 ? (
                <p className="px-5 py-6 text-center text-sm text-muted-foreground">{kosong}</p>
            ) : (
                <>
                    <div className="hidden lg:block">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {tampilkanKode && <TableHead>Kode</TableHead>}
                                    <TableHead>Customer</TableHead>
                                    <TableHead>Lapangan</TableHead>
                                    <TableHead>Jadwal</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {booking.map((item) => (
                                    <TableRow key={item.id}>
                                        {tampilkanKode && <TableCell className="whitespace-nowrap font-bold">{item.kode}</TableCell>}
                                        <TableCell>
                                            <div className="font-bold">{item.customer}</div>
                                            <div className="text-xs text-muted-foreground">{item.telepon}</div>
                                        </TableCell>
                                        <TableCell>{item.lapangan}</TableCell>
                                        <TableCell className="whitespace-nowrap">{item.jadwal}</TableCell>
                                        <TableCell>
                                            <Badge variant={varianStatusBooking(item.status)}>{labelStatus(item.status)}</Badge>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">{rupiah(item.total)}</TableCell>
                                        <TableCell>
                                            <AksiBooking booking={item} onBatalkan={setDibatalkan} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    <ul className="divide-y divide-cream lg:hidden">
                        {booking.map((item) => (
                            <li key={item.id} className="space-y-3 px-4 py-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <div className="truncate font-bold">{item.customer}</div>
                                        <div className="text-xs text-muted-foreground">
                                            {tampilkanKode ? `${item.kode} · ` : ''}
                                            {item.telepon}
                                        </div>
                                    </div>
                                    <Badge variant={varianStatusBooking(item.status)}>{labelStatus(item.status)}</Badge>
                                </div>
                                <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
                                    <dt className="text-muted-foreground">Lapangan</dt>
                                    <dd className="text-right font-medium">{item.lapangan}</dd>
                                    <dt className="text-muted-foreground">Jadwal</dt>
                                    <dd className="text-right font-medium">{item.jadwal}</dd>
                                    <dt className="text-muted-foreground">Total</dt>
                                    <dd className="text-right font-bold">{rupiah(item.total)}</dd>
                                </dl>
                                <AksiBooking booking={item} onBatalkan={setDibatalkan} />
                            </li>
                        ))}
                    </ul>
                </>
            )}

            {footer}

            <Dialog open={dibatalkan !== null} onOpenChange={(buka) => !buka && setDibatalkan(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Batalkan Booking</DialogTitle>
                        <DialogDescription>Booking {dibatalkan?.kode} akan dibatalkan, slot akan kembali kosong.</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-1.5">
                        <Label htmlFor="alasan-batal">Alasan pembatalan (opsional)</Label>
                        <Textarea id="alasan-batal" rows={3} value={alasan} onChange={(event) => setAlasan(event.target.value)} placeholder="Misal, customer minta ganti jadwal" />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDibatalkan(null)}>
                            Tutup
                        </Button>
                        <Button variant="destructive" disabled={memproses} onClick={batalkan}>
                            Ya, Batalkan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Card>
    );
}
