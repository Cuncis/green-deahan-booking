import { Head, router, useForm } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Pencil, Plus, Power } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import Field from '../../components/form/Field';
import AdminLayout from '../../components/layout/AdminLayout';
import { Badge, type BadgeProps } from '../../components/ui/badge';
import { Button } from '../../components/ui/button';
import { Card, CardHeader, CardTitle } from '../../components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../components/ui/dialog';
import { Input } from '../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../components/ui/table';
import { rupiah } from '../../lib/format';
import { cn } from '../../lib/utils';

interface Slot {
    id: number;
    jam: string;
    lapangan: string;
    harga: number;
    status: string;
    bisaDiubahStatus: boolean;
}

interface Props {
    daftarLapangan: { id: number; nama: string }[];
    lapanganId: number | null;
    mingguAwal: string;
    labelMinggu: string;
    tanggalDipilih: string;
    labelTanggal: string;
    ringkasanMingguan: {
        tanggal: string;
        hari: string;
        tanggalPendek: string;
        total: number;
        terisi: number;
    }[];
    slot: Slot[];
}

const DATA_JADWAL = ['lapanganId', 'mingguAwal', 'labelMinggu', 'tanggalDipilih', 'labelTanggal', 'ringkasanMingguan', 'slot'];

const STATUS: Record<string, { label: string; varian: BadgeProps['variant'] }> = {
    kosong: { label: 'Kosong', varian: 'confirmed' },
    hold: { label: 'Hold', varian: 'pending' },
    booked: { label: 'Booked', varian: 'info' },
    nonaktif: { label: 'Nonaktif', varian: 'cancelled' },
};

const INTERVAL = [
    { value: '0.5', label: '30 menit' },
    { value: '1', label: '1 jam' },
    { value: '1.5', label: '1,5 jam' },
    { value: '2', label: '2 jam' },
];

const geserTanggal = (tanggal: string, hari: number): string => {
    const hasil = new Date(`${tanggal}T00:00:00`);
    hasil.setDate(hasil.getDate() + hari);

    return `${hasil.getFullYear()}-${String(hasil.getMonth() + 1).padStart(2, '0')}-${String(hasil.getDate()).padStart(2, '0')}`;
};

function StatusSlot({ status }: { status: string }) {
    const info = STATUS[status] ?? { label: status, varian: 'info' };

    return <Badge variant={info.varian}>{info.label}</Badge>;
}

function AksiSlot({ slot, onEditHarga }: { slot: Slot; onEditHarga: (slot: Slot) => void }) {
    return (
        <div className="flex flex-wrap gap-1.5">
            <Button size="sm" variant="outline" onClick={() => onEditHarga(slot)}>
                <Pencil />
                Harga
            </Button>
            {slot.bisaDiubahStatus && (
                <Button
                    size="sm"
                    variant={slot.status === 'nonaktif' ? 'secondary' : 'destructive-soft'}
                    onClick={() => router.post(`/admin/jadwal/slot/${slot.id}/status`, {}, { preserveScroll: true })}
                >
                    <Power />
                    {slot.status === 'nonaktif' ? 'Aktifkan' : 'Nonaktifkan'}
                </Button>
            )}
        </div>
    );
}

export default function Jadwal(props: Props) {
    const { daftarLapangan, lapanganId, tanggalDipilih } = props;
    const [generateTerbuka, setGenerateTerbuka] = useState(false);
    const [slotHarga, setSlotHarga] = useState<Slot | null>(null);
    const [harga, setHarga] = useState('');

    const buka = (query: { minggu?: string; tanggal?: string; lapangan?: number | null }) => {
        const gabungan = {
            minggu: props.mingguAwal,
            tanggal: tanggalDipilih,
            lapangan: lapanganId,
            ...query,
        };
        router.get('/admin/jadwal', Object.fromEntries(Object.entries(gabungan).filter(([, nilai]) => nilai !== null && nilai !== undefined)), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: DATA_JADWAL,
        });
    };

    const formGenerate = useForm({
        lapangan_id: String(lapanganId ?? daftarLapangan[0]?.id ?? ''),
        tanggal_mulai: tanggalDipilih,
        tanggal_selesai: tanggalDipilih,
        interval_jam: '1',
        harga: '',
    });

    const bukaGenerate = () => {
        formGenerate.setData({
            lapangan_id: String(lapanganId ?? daftarLapangan[0]?.id ?? ''),
            tanggal_mulai: tanggalDipilih,
            tanggal_selesai: tanggalDipilih,
            interval_jam: '1',
            harga: '',
        });
        formGenerate.clearErrors();
        setGenerateTerbuka(true);
    };

    const generate = (event: FormEvent) => {
        event.preventDefault();
        formGenerate.post('/admin/jadwal/generate', {
            preserveScroll: true,
            onSuccess: () => setGenerateTerbuka(false),
        });
    };

    const simpanHarga = (event: FormEvent) => {
        event.preventDefault();
        if (!slotHarga) {
            return;
        }
        router.put(`/admin/jadwal/slot/${slotHarga.id}/harga`, { harga }, { preserveScroll: true, onSuccess: () => setSlotHarga(null) });
    };

    return (
        <AdminLayout
            judul="Jadwal"
            deskripsi="Atur slot per hari: buat otomatis, ubah harga, atau nonaktifkan."
            aksi={
                <Button className="w-full sm:w-auto" onClick={bukaGenerate} disabled={daftarLapangan.length === 0}>
                    <Plus />
                    Generate Slot
                </Button>
            }
        >
            <Head title="Jadwal" />

            <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                <span className="text-xs font-bold uppercase tracking-wide text-muted-foreground">Lapangan</span>
                <Select
                    value={lapanganId ? String(lapanganId) : 'semua'}
                    onValueChange={(nilai) =>
                        buka({
                            lapangan: nilai === 'semua' ? null : Number(nilai),
                        })
                    }
                >
                    <SelectTrigger className="sm:w-64" aria-label="Pilih lapangan">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua Lapangan</SelectItem>
                        {daftarLapangan.map((lapangan) => (
                            <SelectItem key={lapangan.id} value={String(lapangan.id)}>
                                {lapangan.nama}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <Card className="mb-5 p-3 md:p-4">
                <div className="mb-3 flex items-center justify-between gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Minggu sebelumnya"
                        onClick={() =>
                            buka({
                                minggu: geserTanggal(props.mingguAwal, -7),
                                tanggal: geserTanggal(props.mingguAwal, -7),
                            })
                        }
                    >
                        <ChevronLeft />
                    </Button>
                    <span className="text-center text-sm font-bold">{props.labelMinggu}</span>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Minggu berikutnya"
                        onClick={() =>
                            buka({
                                minggu: geserTanggal(props.mingguAwal, 7),
                                tanggal: geserTanggal(props.mingguAwal, 7),
                            })
                        }
                    >
                        <ChevronRight />
                    </Button>
                </div>
                <div className="-mx-1 flex snap-x gap-2 overflow-x-auto px-1 pb-1 md:mx-0 md:grid md:grid-cols-7 md:overflow-visible md:px-0">
                    {props.ringkasanMingguan.map((hari) => {
                        const dipilih = hari.tanggal === tanggalDipilih;

                        return (
                            <button
                                key={hari.tanggal}
                                type="button"
                                onClick={() => buka({ tanggal: hari.tanggal })}
                                aria-pressed={dipilih}
                                className={cn(
                                    'min-w-[4.5rem] shrink-0 snap-start rounded-xl border px-2 py-2.5 text-center transition-colors md:min-w-0',
                                    dipilih ? 'border-primary bg-green-pale' : 'border-border hover:border-sand',
                                )}
                            >
                                <div className="text-xs uppercase tracking-wide text-muted-foreground">{hari.hari}</div>
                                <div className="font-display text-sm font-semibold">{hari.tanggalPendek}</div>
                                <div className="mt-0.5 text-xs text-muted-foreground">
                                    {hari.terisi}/{hari.total}
                                </div>
                            </button>
                        );
                    })}
                </div>
            </Card>

            <Card className="overflow-hidden">
                <CardHeader className="border-b border-border py-4">
                    <CardTitle className="text-xs uppercase tracking-wide text-green">Slot {props.labelTanggal}</CardTitle>
                </CardHeader>
                {props.slot.length === 0 ? (
                    <p className="px-5 py-6 text-center text-sm text-muted-foreground">Belum ada slot di tanggal ini. Pakai tombol Generate Slot untuk membuatnya.</p>
                ) : (
                    <>
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Jam</TableHead>
                                        {!lapanganId && <TableHead>Lapangan</TableHead>}
                                        <TableHead>Harga</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {props.slot.map((slot) => (
                                        <TableRow key={slot.id}>
                                            <TableCell className="whitespace-nowrap font-semibold">{slot.jam}</TableCell>
                                            {!lapanganId && <TableCell>{slot.lapangan}</TableCell>}
                                            <TableCell className="whitespace-nowrap">{rupiah(slot.harga)}</TableCell>
                                            <TableCell>
                                                <StatusSlot status={slot.status} />
                                            </TableCell>
                                            <TableCell>
                                                <AksiSlot
                                                    slot={slot}
                                                    onEditHarga={(dipilih) => {
                                                        setHarga(String(dipilih.harga));
                                                        setSlotHarga(dipilih);
                                                    }}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <ul className="divide-y divide-cream md:hidden">
                            {props.slot.map((slot) => (
                                <li key={slot.id} className="space-y-2.5 px-4 py-3.5">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <div className="font-bold">{slot.jam}</div>
                                            <div className="text-sm text-muted-foreground">
                                                {!lapanganId && `${slot.lapangan} · `}
                                                {rupiah(slot.harga)}
                                            </div>
                                        </div>
                                        <StatusSlot status={slot.status} />
                                    </div>
                                    <AksiSlot
                                        slot={slot}
                                        onEditHarga={(dipilih) => {
                                            setHarga(String(dipilih.harga));
                                            setSlotHarga(dipilih);
                                        }}
                                    />
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </Card>

            <Dialog open={generateTerbuka} onOpenChange={setGenerateTerbuka}>
                <DialogContent>
                    <form onSubmit={generate} className="grid gap-4">
                        <DialogHeader>
                            <DialogTitle>Generate Slot</DialogTitle>
                            <DialogDescription>Slot dibuat mengikuti jam buka dan jam tutup cabang lapangan yang dipilih. Slot yang sudah ada dilewati.</DialogDescription>
                        </DialogHeader>
                        <Field id="gen-lapangan" label="Lapangan" error={formGenerate.errors.lapangan_id}>
                            <Select value={formGenerate.data.lapangan_id} onValueChange={(nilai) => formGenerate.setData('lapangan_id', nilai)}>
                                <SelectTrigger id="gen-lapangan">
                                    <SelectValue placeholder="Pilih lapangan" />
                                </SelectTrigger>
                                <SelectContent>
                                    {daftarLapangan.map((lapangan) => (
                                        <SelectItem key={lapangan.id} value={String(lapangan.id)}>
                                            {lapangan.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                        <div className="grid grid-cols-2 gap-3">
                            <Field id="gen-mulai" label="Dari tanggal" error={formGenerate.errors.tanggal_mulai}>
                                <Input
                                    id="gen-mulai"
                                    type="date"
                                    value={formGenerate.data.tanggal_mulai}
                                    onChange={(event) => formGenerate.setData('tanggal_mulai', event.target.value)}
                                    required
                                />
                            </Field>
                            <Field id="gen-selesai" label="Sampai tanggal" error={formGenerate.errors.tanggal_selesai}>
                                <Input
                                    id="gen-selesai"
                                    type="date"
                                    value={formGenerate.data.tanggal_selesai}
                                    onChange={(event) => formGenerate.setData('tanggal_selesai', event.target.value)}
                                    required
                                />
                            </Field>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <Field id="gen-interval" label="Durasi per slot" error={formGenerate.errors.interval_jam}>
                                <Select value={formGenerate.data.interval_jam} onValueChange={(nilai) => formGenerate.setData('interval_jam', nilai)}>
                                    <SelectTrigger id="gen-interval">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {INTERVAL.map((interval) => (
                                            <SelectItem key={interval.value} value={interval.value}>
                                                {interval.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                            <Field id="gen-harga" label="Harga per slot (Rp)" error={formGenerate.errors.harga}>
                                <Input
                                    id="gen-harga"
                                    type="number"
                                    inputMode="numeric"
                                    min={0}
                                    value={formGenerate.data.harga}
                                    onChange={(event) => formGenerate.setData('harga', event.target.value)}
                                    required
                                />
                            </Field>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setGenerateTerbuka(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={formGenerate.processing}>
                                Generate
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={slotHarga !== null} onOpenChange={(buka) => !buka && setSlotHarga(null)}>
                <DialogContent>
                    <form onSubmit={simpanHarga} className="grid gap-4">
                        <DialogHeader>
                            <DialogTitle>Ubah Harga Slot</DialogTitle>
                            <DialogDescription>
                                {slotHarga?.lapangan}, {slotHarga?.jam}
                            </DialogDescription>
                        </DialogHeader>
                        <Field id="harga-slot" label="Harga (Rp)">
                            <Input id="harga-slot" type="number" inputMode="numeric" min={0} value={harga} onChange={(event) => setHarga(event.target.value)} required autoFocus />
                        </Field>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setSlotHarga(null)}>
                                Batal
                            </Button>
                            <Button type="submit">Simpan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
