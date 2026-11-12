import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import AdminLayout from '../../components/layout/AdminLayout';
import StatistikPengunjung, { type Statistik } from '../../components/StatistikPengunjung';
import RingkasanToko, { type DataRingkasanToko } from '../../components/toko/RingkasanToko';
import { Badge, varianPaket } from '../../components/ui/badge';
import { Card, CardHeader, CardTitle } from '../../components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../components/ui/table';
import { angka } from '../../lib/format';

interface TenantRingkas {
    id: number;
    namaBisnis: string;
    domain: string;
    paket: string;
    aktif: boolean;
    url: string;
}

interface Props {
    statistikPengunjung: Statistik;
    ringkasan: {
        totalTenant: number;
        tenantAktif: number;
        bookingHariIni: number;
    };
    tenantTerbaru: TenantRingkas[];
    semuaTenantUrl: string;
    toko: DataRingkasanToko;
    pesananTokoUrl: string;
}

function AngkaRingkas({ label, nilai }: { label: string; nilai: number }) {
    return (
        <Card className="p-4 md:p-5">
            <div className="text-xs font-semibold text-muted-foreground">{label}</div>
            <div className="mt-1.5 font-display text-2xl font-semibold">{angka(nilai)}</div>
        </Card>
    );
}

function StatusTenant({ aktif }: { aktif: boolean }) {
    return <Badge variant={aktif ? 'confirmed' : 'cancelled'}>{aktif ? 'Aktif' : 'Nonaktif'}</Badge>;
}

export default function Dashboard({ statistikPengunjung, ringkasan, tenantTerbaru, semuaTenantUrl, toko, pesananTokoUrl }: Props) {
    return (
        <AdminLayout judul="Dashboard" deskripsi="Ringkasan seluruh tenant dan pengunjung website di platform.">
            <Head title="Dashboard" />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-3.5">
                <AngkaRingkas label="Total Tenant" nilai={ringkasan.totalTenant} />
                <AngkaRingkas label="Tenant Aktif" nilai={ringkasan.tenantAktif} />
                <div className="col-span-2 md:col-span-1">
                    <AngkaRingkas label="Booking Hari Ini (Semua Tenant)" nilai={ringkasan.bookingHariIni} />
                </div>
            </div>

            <RingkasanToko data={toko} pesananUrl={pesananTokoUrl} />

            <section className="mb-6" aria-labelledby="judul-statistik-pengunjung">
                <StatistikPengunjung data={statistikPengunjung} />
            </section>

            <Card className="overflow-hidden">
                <CardHeader className="flex-row items-center justify-between gap-3 border-b border-border py-4">
                    <CardTitle className="text-xs uppercase tracking-wide text-green">5 Tenant Terbaru</CardTitle>
                    <Link href={semuaTenantUrl} className="inline-flex min-h-10 items-center gap-1 text-xs font-semibold text-plum hover:underline">
                        Lihat semua
                        <ArrowRight className="size-3.5" />
                    </Link>
                </CardHeader>

                {tenantTerbaru.length === 0 ? (
                    <p className="px-5 py-6 text-center text-sm text-muted-foreground">Belum ada tenant terdaftar.</p>
                ) : (
                    <>
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama Bisnis</TableHead>
                                        <TableHead>Domain</TableHead>
                                        <TableHead>Paket</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {tenantTerbaru.map((tenant) => (
                                        <TableRow key={tenant.id}>
                                            <TableCell className="font-bold">
                                                <Link href={tenant.url} className="hover:text-green hover:underline">
                                                    {tenant.namaBisnis}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="text-ink-mid">{tenant.domain}</TableCell>
                                            <TableCell>
                                                <Badge variant={varianPaket(tenant.paket)}>{tenant.paket}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <StatusTenant aktif={tenant.aktif} />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <ul className="divide-y divide-cream md:hidden">
                            {tenantTerbaru.map((tenant) => (
                                <li key={tenant.id}>
                                    <Link href={tenant.url} className="flex items-center justify-between gap-3 px-4 py-3.5 active:bg-muted">
                                        <div className="min-w-0">
                                            <div className="truncate font-bold">{tenant.namaBisnis}</div>
                                            <div className="truncate text-xs text-muted-foreground">{tenant.domain}</div>
                                        </div>
                                        <div className="flex shrink-0 flex-col items-end gap-1.5">
                                            <Badge variant={varianPaket(tenant.paket)}>{tenant.paket}</Badge>
                                            <StatusTenant aktif={tenant.aktif} />
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </Card>
        </AdminLayout>
    );
}
