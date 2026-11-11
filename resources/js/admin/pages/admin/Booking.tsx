import { Head, router } from '@inertiajs/react';
import { Download, RotateCcw, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import DaftarBooking, { type BookingRingkas } from '../../components/booking/DaftarBooking';
import AdminLayout from '../../components/layout/AdminLayout';
import Pagination, { type Paginated } from '../../components/Pagination';
import { Button } from '../../components/ui/button';
import { Card } from '../../components/ui/card';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../components/ui/select';

interface Filter {
    status: string | null;
    tanggal: string | null;
    lapangan: string | null;
    cari: string | null;
}

interface Props {
    booking: Paginated<BookingRingkas>;
    daftarLapangan: { id: number; nama: string }[];
    filter: Filter;
    exportUrl: string;
}

const STATUS = [
    { value: 'menunggu', label: 'Menunggu' },
    { value: 'dikonfirmasi', label: 'Dikonfirmasi' },
    { value: 'selesai', label: 'Selesai' },
    { value: 'dibatalkan', label: 'Dibatalkan' },
];

const SEMUA = 'semua';

export default function Booking({ booking, daftarLapangan, filter, exportUrl }: Props) {
    const [cari, setCari] = useState(filter.cari ?? '');
    const pertama = useRef(true);

    const terapkan = (perubahan: Partial<Filter>) => {
        const gabungan = { ...filter, cari: cari || null, ...perubahan };
        const query = Object.fromEntries(Object.entries(gabungan).filter(([, nilai]) => nilai !== null && nilai !== '' && nilai !== SEMUA));
        router.get('/admin/booking', query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['booking', 'filter', 'exportUrl'],
        });
    };

    // Search as you type, after a short pause.
    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        const jeda = window.setTimeout(() => terapkan({ cari: cari || null }), 350);

        return () => window.clearTimeout(jeda);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [cari]);

    const adaFilter = Boolean(filter.status || filter.tanggal || filter.lapangan || filter.cari);

    return (
        <AdminLayout
            judul="Semua Booking"
            deskripsi="Cari, saring, dan kelola semua booking."
            aksi={
                <Button asChild variant="outline" className="w-full sm:w-auto">
                    <a href={exportUrl}>
                        <Download />
                        Download CSV
                    </a>
                </Button>
            }
        >
            <Head title="Semua Booking" />

            <Card className="mb-5 p-4">
                <div className="grid grid-cols-2 gap-3 md:grid-cols-5">
                    <div className="col-span-2 space-y-1.5">
                        <Label htmlFor="cari-booking">Cari</Label>
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="cari-booking"
                                type="search"
                                value={cari}
                                onChange={(event) => setCari(event.target.value)}
                                placeholder="Nama customer atau kode booking"
                                className="pl-9"
                            />
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="filter-status">Status</Label>
                        <Select value={filter.status ?? SEMUA} onValueChange={(status) => terapkan({ status })}>
                            <SelectTrigger id="filter-status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={SEMUA}>Semua Status</SelectItem>
                                {STATUS.map((status) => (
                                    <SelectItem key={status.value} value={status.value}>
                                        {status.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="filter-tanggal">Tanggal</Label>
                        <Input
                            id="filter-tanggal"
                            type="date"
                            value={filter.tanggal ?? ''}
                            onChange={(event) =>
                                terapkan({
                                    tanggal: event.target.value || null,
                                })
                            }
                        />
                    </div>
                    <div className="col-span-2 space-y-1.5 md:col-span-1">
                        <Label htmlFor="filter-lapangan">Lapangan</Label>
                        <Select value={filter.lapangan ?? SEMUA} onValueChange={(lapangan) => terapkan({ lapangan })}>
                            <SelectTrigger id="filter-lapangan">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={SEMUA}>Semua Lapangan</SelectItem>
                                {daftarLapangan.map((lapangan) => (
                                    <SelectItem key={lapangan.id} value={String(lapangan.id)}>
                                        {lapangan.nama}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                {adaFilter && (
                    <div className="mt-3 flex justify-end">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                setCari('');
                                router.get('/admin/booking', {}, { preserveScroll: true, replace: true });
                            }}
                        >
                            <RotateCcw />
                            Reset filter
                        </Button>
                    </div>
                )}
            </Card>

            <DaftarBooking
                booking={booking.data}
                judul={`${booking.total} Booking`}
                tampilkanKode
                kosong={adaFilter ? 'Tidak ada booking yang cocok dengan filter.' : 'Belum ada booking.'}
                footer={<Pagination halaman={booking} />}
            />
        </AdminLayout>
    );
}
