import { Head, Link, router } from '@inertiajs/react';
import { Mail, MessageCircle } from 'lucide-react';
import AdminLayout from '../../../../components/layout/AdminLayout';
import Pagination, { type Paginated } from '../../../../components/Pagination';
import { Badge } from '../../../../components/ui/badge';
import { Card, CardHeader, CardTitle } from '../../../../components/ui/card';
import { Label } from '../../../../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../../components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../../components/ui/table';
import { rupiah } from '../../../../lib/format';
import { bukaChatWhatsApp } from '../../../../lib/whatsapp';

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
    perluRefund: boolean;
    tanggal: string;
    url: string;
    whatsapp: { template: string; isi: string; url: string } | null;
    pesanUrl: string;
    emailUlangUrl: string;
    email: string;
}

/**
 * Opens WhatsApp with a message about the order's current status (link to the
 * customer's status page included) and logs it in the order's message history.
 */
/**
 * Resends the email for the order's current status (e.g. the customer says it
 * never arrived). Asks first so a stray click does not send twice.
 */
function TombolEmailUlang({ item }: { item: PesananBaris }) {
    const kirim = () => {
        if (!window.confirm(`Kirim ulang email status "${item.statusLabel}" ke ${item.email}?`)) return;
        router.post(item.emailUlangUrl, {}, { preserveScroll: true, preserveState: true });
    };

    return (
        <button
            type="button"
            onClick={kirim}
            title={`Kirim ulang email ke ${item.email} (status: ${item.statusLabel})`}
            aria-label={`Kirim ulang email status ${item.statusLabel} ke ${item.email}`}
            className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-green text-white shadow-sm transition hover:bg-green-mid focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green focus-visible:ring-offset-2"
        >
            <Mail className="h-4 w-4" />
        </button>
    );
}

function TombolKabari({ item }: { item: PesananBaris }) {
    return (
        <div className="flex items-center justify-center gap-1.5">
            <TombolWhatsApp item={item} />
            <TombolEmailUlang item={item} />
        </div>
    );
}

function TombolWhatsApp({ item }: { item: PesananBaris }) {
    if (!item.whatsapp) {
        return (
            <span className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-muted text-muted-foreground" title="Nomor HP pelanggan tidak valid untuk WhatsApp">
                <MessageCircle className="h-4 w-4" />
            </span>
        );
    }

    const kirim = () => {
        if (!item.whatsapp) return;
        bukaChatWhatsApp(item.whatsapp.url);
        router.post(item.pesanUrl, { channel: 'whatsapp', template: item.whatsapp.template, body: item.whatsapp.isi }, { preserveScroll: true, preserveState: true });
    };

    return (
        <button
            type="button"
            onClick={kirim}
            title={`Kabari ${item.pelanggan} lewat WhatsApp (status: ${item.statusLabel})`}
            aria-label={`Kabari ${item.pelanggan} lewat WhatsApp, status ${item.statusLabel}`}
            className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#25D366] text-white shadow-sm transition hover:bg-[#1da851] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green focus-visible:ring-offset-2"
        >
            <MessageCircle className="h-4 w-4" />
        </button>
    );
}

interface Props {
    pesanan: Paginated<PesananBaris>;
    filterStatus: string;
    pilihanStatus: { nilai: string; label: string; jumlah: number }[];
    bypassBayar: { aktif: boolean; url: string };
}

export const varianStatusPesanan = (status: string) =>
    (
        ({
            pending_payment: 'pending',
            paid: 'pending',
            ordered_to_supplier: 'info',
            shipped: 'info',
            completed: 'confirmed',
            cancelled: 'cancelled',
            expired: 'cancelled',
        }) as const
    )[status as 'paid'] ?? 'info';

/** Shop orders, filtered by status (default: paid, i.e. still to be bought from the supplier). */
export default function Index({ pesanan, filterStatus, pilihanStatus, bypassBayar }: Props) {
    const saring = (status: string) => router.get('/superadmin/toko/pesanan', { status }, { preserveState: true, preserveScroll: true, replace: true });

    return (
        <AdminLayout judul="Pesanan Toko" deskripsi="Pesanan berstatus Dibayar perlu segera dibeli ke supplier.">
            <Head title="Pesanan Toko" />
            {/* TEMPORARY (BypassBayarToko): remove once the Mayar sandbox works again. */}
            <Card className={`mb-5 border-2 border-dashed p-4 ${bypassBayar.aktif ? 'border-orange-400 bg-orange-50' : ''}`}>
                <label className="flex cursor-pointer items-start gap-3">
                    <input
                        type="checkbox"
                        className="mt-0.5 h-5 w-5 shrink-0 accent-green"
                        checked={bypassBayar.aktif}
                        onChange={(event) => router.put(bypassBayar.url, { aktif: event.target.checked }, { preserveScroll: true })}
                    />
                    <span className="space-y-1">
                        <span className="block text-sm font-semibold">Mode uji bayar (lewati Mayar)</span>
                        <span className="block text-sm text-muted-foreground">
                            Selama sandbox Mayar belum bisa dipakai. Saat aktif dan Anda login sebagai superadmin, checkout tidak membuka Mayar dan
                            halaman pesanan menampilkan gelembung oranye di kanan untuk menandai pesanan dibayar. Pengunjung biasa tetap memakai Mayar.
                        </span>
                    </span>
                </label>
            </Card>
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
                                        <TableHead className="text-center">Kabari</TableHead>
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
                                                {item.perluRefund && (
                                                    <Badge variant="pending" className="ml-1.5">
                                                        Perlu refund
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-ink-mid">{item.tanggal}</TableCell>
                                            <TableCell className="text-center">
                                                <TombolKabari item={item} />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <ul className="divide-y divide-cream md:hidden">
                            {pesanan.data.map((item) => (
                                <li key={item.id} className="flex items-center gap-2 pr-4">
                                    <Link href={item.url} className="block min-w-0 flex-1 px-4 py-3.5 active:bg-muted">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="font-bold">{item.kode}</span>
                                            <span className="flex items-center gap-1.5">
                                                <Badge variant={varianStatusPesanan(item.status)}>{item.statusLabel}</Badge>
                                                {item.perluRefund && <Badge variant="pending">Perlu refund</Badge>}
                                            </span>
                                        </div>
                                        <div className="mt-1 text-sm">
                                            {item.pelanggan}, {item.kota}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {item.tanggal}, {rupiah(item.total)}, untung {rupiah(item.untung)}
                                        </div>
                                    </Link>
                                    <TombolKabari item={item} />
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
