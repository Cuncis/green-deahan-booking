import { Head, Link, router } from '@inertiajs/react';
import { Mail, Plus, Power, RotateCcw } from 'lucide-react';
import { useState } from 'react';
import KonfirmasiDialog from '../../../components/KonfirmasiDialog';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Badge, varianPaket } from '../../../components/ui/badge';
import { Button } from '../../../components/ui/button';
import { Card, CardHeader, CardTitle } from '../../../components/ui/card';
import { Label } from '../../../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { angka } from '../../../lib/format';

export interface TenantAksi {
    id: number;
    namaBisnis: string;
    aktif: boolean;
    punyaEmail: boolean;
    url: string;
    aktifkanUrl: string;
    nonaktifkanUrl: string;
    undangUrl: string;
}

interface TenantBaris extends TenantAksi {
    domain: string;
    paket: string;
    berakhir: string | null;
    jumlahBooking: number;
}

interface Props {
    daftarTenant: TenantBaris[];
    filter: { paket: string | null; status: string | null };
}

const SEMUA = 'semua';

export function StatusTenant({ aktif }: { aktif: boolean }) {
    return <Badge variant={aktif ? 'confirmed' : 'cancelled'}>{aktif ? 'Aktif' : 'Nonaktif'}</Badge>;
}

/**
 * Activate / deactivate / send owner invitation. Deactivating asks first,
 * because the tenant's booking site stops working right away.
 */
export function AksiTenant({ tenant, ukuran = 'sm', className }: { tenant: TenantAksi; ukuran?: 'sm' | 'default'; className?: string }) {
    const [konfirmasi, setKonfirmasi] = useState(false);
    const [memproses, setMemproses] = useState(false);

    const kirim = (url: string) =>
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                onStart: () => setMemproses(true),
                onFinish: () => {
                    setMemproses(false);
                    setKonfirmasi(false);
                },
            },
        );

    return (
        <div className={className ?? 'flex flex-wrap gap-2'}>
            {tenant.aktif ? (
                <Button variant="outline" size={ukuran} disabled={memproses} onClick={() => setKonfirmasi(true)}>
                    <Power />
                    Nonaktifkan
                </Button>
            ) : (
                <Button variant="outline" size={ukuran} disabled={memproses} onClick={() => kirim(tenant.aktifkanUrl)}>
                    <Power />
                    Aktifkan
                </Button>
            )}
            <Button
                variant="outline"
                size={ukuran}
                disabled={memproses || !tenant.punyaEmail}
                title={tenant.punyaEmail ? undefined : 'Tenant belum punya email PIC'}
                onClick={() => kirim(tenant.undangUrl)}
            >
                <Mail />
                Undang Owner
            </Button>

            <KonfirmasiDialog
                terbuka={konfirmasi}
                onTutup={() => setKonfirmasi(false)}
                onLanjut={() => kirim(tenant.nonaktifkanUrl)}
                judul={`Nonaktifkan ${tenant.namaBisnis}?`}
                deskripsi="Halaman booking tenant ini langsung tidak bisa dipakai customer sampai diaktifkan lagi."
                labelLanjut="Nonaktifkan"
                memproses={memproses}
            />
        </div>
    );
}

export default function Index({ daftarTenant, filter }: Props) {
    const terapkan = (perubahan: Partial<Props['filter']>) => {
        const gabungan = { ...filter, ...perubahan };
        const query = Object.fromEntries(Object.entries(gabungan).filter(([, nilai]) => nilai && nilai !== SEMUA));
        router.get('/superadmin/tenants', query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };
    const adaFilter = Boolean(filter.paket || filter.status);

    return (
        <AdminLayout
            judul="Semua Tenant"
            deskripsi="Kelola seluruh tenant yang terdaftar di platform."
            aksi={
                <Button asChild className="w-full sm:w-auto">
                    <Link href="/superadmin/tenants/buat">
                        <Plus />
                        Tambah Tenant
                    </Link>
                </Button>
            }
        >
            <Head title="Semua Tenant" />

            <Card className="mb-5 p-4">
                <div className="grid grid-cols-2 gap-3 md:flex md:items-end">
                    <div className="space-y-1.5 md:w-52">
                        <Label htmlFor="filter-paket">Paket</Label>
                        <Select value={filter.paket ?? SEMUA} onValueChange={(paket) => terapkan({ paket })}>
                            <SelectTrigger id="filter-paket">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={SEMUA}>Semua Paket</SelectItem>
                                <SelectItem value="basic">Basic</SelectItem>
                                <SelectItem value="pro">Pro</SelectItem>
                                <SelectItem value="premium">Premium</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1.5 md:w-52">
                        <Label htmlFor="filter-status">Status</Label>
                        <Select value={filter.status ?? SEMUA} onValueChange={(status) => terapkan({ status })}>
                            <SelectTrigger id="filter-status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={SEMUA}>Semua Status</SelectItem>
                                <SelectItem value="aktif">Aktif</SelectItem>
                                <SelectItem value="nonaktif">Nonaktif</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    {adaFilter && (
                        <Button variant="ghost" className="col-span-2 md:col-span-1" onClick={() => router.get('/superadmin/tenants', {}, { preserveScroll: true, replace: true })}>
                            <RotateCcw />
                            Reset filter
                        </Button>
                    )}
                </div>
            </Card>

            <Card className="overflow-hidden">
                <CardHeader className="border-b border-border py-4">
                    <CardTitle className="text-xs uppercase tracking-wide text-green">{angka(daftarTenant.length)} Tenant</CardTitle>
                </CardHeader>

                {daftarTenant.length === 0 ? (
                    <p className="px-5 py-6 text-center text-sm text-muted-foreground">
                        {adaFilter ? 'Tidak ada tenant yang cocok dengan filter ini.' : 'Belum ada tenant terdaftar.'}
                    </p>
                ) : (
                    <>
                        <div className="hidden lg:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama Bisnis</TableHead>
                                        <TableHead>Paket</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Berakhir</TableHead>
                                        <TableHead className="text-right">Booking</TableHead>
                                        <TableHead>Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {daftarTenant.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>
                                                <Link href={item.url} className="font-bold hover:text-green hover:underline">
                                                    {item.namaBisnis}
                                                </Link>
                                                <div className="text-xs text-muted-foreground">{item.domain}</div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={varianPaket(item.paket)}>{item.paket}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <StatusTenant aktif={item.aktif} />
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-ink-mid">{item.berakhir ?? '-'}</TableCell>
                                            <TableCell className="text-right tabular-nums text-ink-mid">{angka(item.jumlahBooking)}</TableCell>
                                            <TableCell>
                                                <AksiTenant tenant={item} />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <ul className="divide-y divide-cream lg:hidden">
                            {daftarTenant.map((item) => (
                                <li key={item.id} className="px-4 py-4">
                                    <Link href={item.url} className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="truncate font-bold">{item.namaBisnis}</div>
                                            <div className="truncate text-xs text-muted-foreground">{item.domain}</div>
                                        </div>
                                        <div className="flex shrink-0 flex-col items-end gap-1.5">
                                            <Badge variant={varianPaket(item.paket)}>{item.paket}</Badge>
                                            <StatusTenant aktif={item.aktif} />
                                        </div>
                                    </Link>
                                    <div className="mt-2 text-xs text-ink-mid">
                                        Berakhir {item.berakhir ?? '-'}, {angka(item.jumlahBooking)} booking
                                    </div>
                                    <AksiTenant tenant={item} className="mt-3 grid grid-cols-2 gap-2" />
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </Card>
        </AdminLayout>
    );
}
