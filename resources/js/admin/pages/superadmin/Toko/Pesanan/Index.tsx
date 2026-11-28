import { Head, Link, router } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import WhatsAppIcon from '../../../../../components/fuzio/elements/WhatsAppIcon';
import ChipFilter from '../../../../components/ChipFilter';
import AdminLayout from '../../../../components/layout/AdminLayout';
import Pagination, { type Paginated } from '../../../../components/Pagination';
import { Badge } from '../../../../components/ui/badge';
import { Card, CardHeader, CardTitle } from '../../../../components/ui/card';
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
            className="inline-flex h-10 w-10 items-center justify-center rounded-full xl:h-9 xl:w-9 bg-green text-white shadow-sm transition hover:bg-green-mid focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green focus-visible:ring-offset-2"
        >
            <Mail className="h-4 w-4" />
        </button>
    );
}

/** "Kabupaten Banyuwangi" -> "Kab. Banyuwangi", "Kota Administrasi Jakarta Selatan" -> "Jakarta Selatan". */
const singkatKota = (kota: string) => kota.replace(/^Kota Administrasi\s+/i, '').replace(/^Kabupaten\s+/i, 'Kab. ');

/** Status badge, with the refund badge stacked under it instead of squeezed beside it. */
function StatusPesanan({ item }: { item: PesananBaris }) {
    return (
        <div className="flex flex-col items-start gap-1">
            <Badge variant={varianStatusPesanan(item.status)}>{item.statusLabel}</Badge>
            {item.perluRefund && <Badge variant="pending">Perlu refund</Badge>}
        </div>
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
            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full xl:h-9 xl:w-9 bg-muted text-muted-foreground" title="Nomor HP pelanggan tidak valid untuk WhatsApp">
                <WhatsAppIcon size={18} />
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
            className="inline-flex h-10 w-10 items-center justify-center rounded-full xl:h-9 xl:w-9 bg-[#25D366] text-white shadow-sm transition hover:bg-[#1da851] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green focus-visible:ring-offset-2"
        >
            <WhatsAppIcon size={18} />
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

/** Shop orders, all statuses by default, filtered with the status chips. */
export default function Index({ pesanan, filterStatus, pilihanStatus, bypassBayar }: Props) {
    const totalSemua = pilihanStatus.filter((item) => item.nilai !== 'perlu_refund').reduce((jumlah, item) => jumlah + item.jumlah, 0);
    const saring = (status: string) => router.get('/superadmin/toko/pesanan', { status }, { preserveState: true, preserveScroll: true, replace: true });

    return (
        <AdminLayout judul="Pesanan Toko" deskripsi="Semua pesanan toko. Pesanan berstatus Dibayar perlu segera dibeli ke supplier.">
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
                        <span className="block text-xs text-muted-foreground sm:text-sm">
                            Selama sandbox Mayar belum bisa dipakai. Saat aktif dan Anda login sebagai superadmin, checkout tidak membuka Mayar dan
                            halaman pesanan menampilkan gelembung oranye di kanan untuk menandai pesanan dibayar. Pengunjung biasa tetap memakai Mayar.
                        </span>
                    </span>
                </label>
            </Card>
            <ChipFilter
                pilihan={[{ nilai: 'semua', label: 'Semua', jumlah: totalSemua }, ...pilihanStatus]}
                aktif={filterStatus}
                onPilih={saring}
                label="Filter status pesanan"
            />

            <Card className="overflow-hidden">
                <CardHeader className="border-b border-border py-4">
                    <CardTitle className="text-xs uppercase tracking-wide text-green">{pesanan.total} Pesanan</CardTitle>
                </CardHeader>
                {pesanan.data.length === 0 ? (
                    <p className="px-5 py-6 text-center text-sm text-muted-foreground">Tidak ada pesanan dengan status ini.</p>
                ) : (
                    <>
                        <div className="hidden xl:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Pesanan</TableHead>
                                        <TableHead>Pelanggan</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                        <TableHead className="w-28 text-center">Kabari</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {pesanan.data.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="whitespace-nowrap py-3.5">
                                                <Link href={item.url} className="font-bold tracking-tight hover:text-green hover:underline">
                                                    {item.kode}
                                                </Link>
                                                <div className="mt-0.5 text-xs text-muted-foreground">{item.tanggal}</div>
                                            </TableCell>
                                            <TableCell className="max-w-[16rem] py-3.5">
                                                <div className="truncate font-medium" title={item.pelanggan}>
                                                    {item.pelanggan}
                                                </div>
                                                <div className="truncate text-xs text-muted-foreground" title={`${item.kota}, ${item.jumlahBarang} barang`}>
                                                    {singkatKota(item.kota)} · {item.jumlahBarang} barang
                                                </div>
                                            </TableCell>
                                            <TableCell className="py-3.5">
                                                <StatusPesanan item={item} />
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap py-3.5 text-right tabular-nums">
                                                <div className="font-semibold">{rupiah(item.total)}</div>
                                                <div className="text-xs font-semibold text-green">untung {rupiah(item.untung)}</div>
                                            </TableCell>
                                            <TableCell className="py-3.5">
                                                <TombolKabari item={item} />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <ul className="divide-y divide-cream xl:hidden">
                            {pesanan.data.map((item) => (
                                <li key={item.id} className="flex items-center gap-3 pr-4">
                                    <Link href={item.url} className="block min-w-0 flex-1 px-4 py-3.5 active:bg-muted">
                                        <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-1.5">
                                            <span className="whitespace-nowrap font-bold tracking-tight">{item.kode}</span>
                                            <StatusPesanan item={item} />
                                        </div>
                                        <div className="mt-1 truncate text-sm">
                                            {item.pelanggan} · {singkatKota(item.kota)}
                                        </div>
                                        <div className="text-xs text-muted-foreground">{item.tanggal}</div>
                                        <div className="mt-0.5 flex flex-wrap gap-x-2 text-sm tabular-nums">
                                            <span className="whitespace-nowrap font-semibold">{rupiah(item.total)}</span>
                                            <span className="whitespace-nowrap text-xs font-semibold leading-5 text-green">untung {rupiah(item.untung)}</span>
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
