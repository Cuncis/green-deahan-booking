import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../../../components/layout/AdminLayout';
import Pagination, { type Paginated } from '../../../../components/Pagination';
import { Badge } from '../../../../components/ui/badge';
import { Card, CardHeader, CardTitle } from '../../../../components/ui/card';
import { Label } from '../../../../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../../components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../../components/ui/table';
import { rupiah } from '../../../../lib/format';

interface PesananBaris {
    id: number;
    kode: string;
    pelanggan: string;
    kota: string;
    jumlahBarang: number;
    total: number;
    untung: number;
    status: string;
    statusLabel: string;
    tanggal: string;
    url: string;
}

interface Props {
    pesanan: Paginated<PesananBaris>;
    filterStatus: string;
    pilihanStatus: { nilai: string; label: string; jumlah: number }[];
}

export const varianStatusPesanan = (status: string) =>
    (({ pending_payment: 'pending', paid: 'pending', ordered_to_supplier: 'info', shipped: 'info', completed: 'confirmed', cancelled: 'cancelled', expired: 'cancelled' }) as const)[
        status as 'paid'
    ] ?? 'info';

/** Shop orders, filtered by status (default: paid, i.e. still to be bought from the supplier). */
export default function Index({ pesanan, filterStatus, pilihanStatus }: Props) {
    const saring = (status: string) => router.get('/superadmin/toko/pesanan', { status }, { preserveState: true, preserveScroll: true, replace: true });

    return (
        <AdminLayout judul="Pesanan Toko" deskripsi="Pesanan berstatus Dibayar perlu segera dibeli ke supplier.">
            <Head title="Pesanan Toko" />
            <Card className="mb-5 p-4">
                <div className="space-y-1.5 sm:w-72">
                    <Label htmlFor="filter-status">Status</Label>
                    <Select value={filterStatus} onValueChange={saring}>
                        <SelectTrigger id="filter-status">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="semua">Semua status</SelectItem>
                            {pilihanStatus.map((item) => (
                                <SelectItem key={item.nilai} value={item.nilai}>
                                    {item.label} ({item.jumlah})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </Card>

            <Card className="overflow-hidden">
                <CardHeader className="border-b border-border py-4">
                    <CardTitle className="text-xs uppercase tracking-wide text-green">{pesanan.total} Pesanan</CardTitle>
                </CardHeader>
                {pesanan.data.length === 0 ? (
                    <p className="px-5 py-6 text-center text-sm text-muted-foreground">Tidak ada pesanan dengan status ini.</p>
                ) : (
                    <>
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Pelanggan</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                        <TableHead className="text-right">Untung</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {pesanan.data.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>
                                                <Link href={item.url} className="font-bold hover:text-green hover:underline">
                                                    {item.kode}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                {item.pelanggan}
                                                <div className="text-xs text-muted-foreground">
                                                    {item.kota}, {item.jumlahBarang} barang
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">{rupiah(item.total)}</TableCell>
                                            <TableCell className="text-right tabular-nums font-semibold text-green">{rupiah(item.untung)}</TableCell>
                                            <TableCell>
                                                <Badge variant={varianStatusPesanan(item.status)}>{item.statusLabel}</Badge>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-ink-mid">{item.tanggal}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <ul className="divide-y divide-cream md:hidden">
                            {pesanan.data.map((item) => (
                                <li key={item.id}>
                                    <Link href={item.url} className="block px-4 py-3.5 active:bg-muted">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="font-bold">{item.kode}</span>
                                            <Badge variant={varianStatusPesanan(item.status)}>{item.statusLabel}</Badge>
                                        </div>
                                        <div className="mt-1 text-sm">
                                            {item.pelanggan}, {item.kota}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {item.tanggal}, {rupiah(item.total)}, untung {rupiah(item.untung)}
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                        <Pagination halaman={pesanan} />
                    </>
                )}
            </Card>
        </AdminLayout>
    );
}
