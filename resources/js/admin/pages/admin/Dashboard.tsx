import { Head, usePage, usePoll } from '@inertiajs/react';
import { Crown } from 'lucide-react';
import { useEffect } from 'react';
import DaftarBooking, { type BookingRingkas } from '../../components/booking/DaftarBooking';
import FilterCabang, { type DataFilterCabang } from '../../components/FilterCabang';
import PanelReminder, { type ReminderRingkas } from '../../components/dashboard/PanelReminder';
import PanelStaf, { type StafRingkas } from '../../components/dashboard/PanelStaf';
import AdminLayout from '../../components/layout/AdminLayout';
import { Card, CardHeader, CardTitle } from '../../components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../components/ui/table';
import { angka, rupiah } from '../../lib/format';
import type { SharedProps } from '../../types';

interface Props {
    filterCabang: DataFilterCabang | null;
    statistik: {
        bookingHariIni: number;
        menungguKonfirmasi: number;
        bookingMingguIni: number | null;
        tingkatKeterisian: number | null;
    };
    bookingTerbaru: BookingRingkas[];
    perbandinganCabang:
        | {
              nama: string;
              totalBooking: number;
              pendapatan: number;
              keterisian: number;
          }[]
        | null;
    staf: StafRingkas[] | null;
    membership: { tier: string; label: string; jumlah: number; persen: number }[] | null;
    reminder: ReminderRingkas[] | null;
}

/** Everything except the shared layout props, so polling refreshes only the dashboard data. */
const DATA_DASHBOARD = ['filterCabang', 'statistik', 'bookingTerbaru', 'perbandinganCabang', 'staf', 'membership', 'reminder'];

function KartuAngka({ label, nilai }: { label: string; nilai: string }) {
    return (
        <Card className="p-4 md:p-5">
            <div className="text-xs font-semibold text-muted-foreground">{label}</div>
            <div className="mt-1.5 font-display text-2xl font-semibold">{nilai}</div>
        </Card>
    );
}

function PerbandinganCabang({ data }: { data: NonNullable<Props['perbandinganCabang']> }) {
    return (
        <Card id="analitik" className="mb-6 scroll-mt-24 overflow-hidden">
            <CardHeader className="border-b border-border py-4">
                <CardTitle className="text-xs uppercase tracking-wide text-green">Perbandingan Performa Cabang</CardTitle>
            </CardHeader>
            {data.length === 0 ? (
                <p className="px-5 py-6 text-center text-sm text-muted-foreground">Belum ada cabang.</p>
            ) : (
                <>
                    <div className="hidden md:block">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Ranking</TableHead>
                                    <TableHead>Cabang</TableHead>
                                    <TableHead>Booking</TableHead>
                                    <TableHead>Pendapatan</TableHead>
                                    <TableHead>Keterisian</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.map((item, index) => (
                                    <TableRow key={item.nama}>
                                        <TableCell className="font-bold">#{index + 1}</TableCell>
                                        <TableCell>{item.nama}</TableCell>
                                        <TableCell>{angka(item.totalBooking)}</TableCell>
                                        <TableCell>{rupiah(item.pendapatan)}</TableCell>
                                        <TableCell>{item.keterisian}%</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    <ol className="divide-y divide-cream md:hidden">
                        {data.map((item, index) => (
                            <li key={item.nama} className="flex items-center gap-3 px-4 py-3.5">
                                <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-green-pale text-sm font-bold text-green">#{index + 1}</span>
                                <div className="min-w-0 flex-1">
                                    <div className="truncate font-bold">{item.nama}</div>
                                    <div className="text-xs text-muted-foreground">
                                        {angka(item.totalBooking)} booking, keterisian {item.keterisian}%
                                    </div>
                                </div>
                                <div className="shrink-0 text-right text-sm font-bold">{rupiah(item.pendapatan)}</div>
                            </li>
                        ))}
                    </ol>
                </>
            )}
        </Card>
    );
}

function PanelMembership({ data }: { data: NonNullable<Props['membership']> }) {
    return (
        <Card id="membership" className="mb-6 scroll-mt-24 p-5">
            <div className="mb-4 text-xs font-bold uppercase tracking-wide text-green">Membership Tiers</div>
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                {data.map((tier) => (
                    <div key={tier.tier} className="rounded-xl border border-border p-4">
                        <div className="mb-2 flex items-center gap-2">
                            <Crown className="size-4 text-gold" aria-hidden="true" />
                            <span className="font-bold">{tier.label}</span>
                        </div>
                        <div className="font-display text-xl font-semibold">{angka(tier.jumlah)}</div>
                        <div className="text-xs text-muted-foreground">member, diskon {tier.persen}%</div>
                    </div>
                ))}
            </div>
        </Card>
    );
}

export default function Dashboard(props: Props) {
    const { tenant } = usePage<SharedProps>().props;
    const { statistik } = props;
    const statistikLengkap = statistik.bookingMingguIni !== null;

    // Same as the old wire:poll.30s, but only the dashboard data is refreshed.
    usePoll(30000, { only: DATA_DASHBOARD });

    // Sidebar links like /admin#staf: scroll to the section once it has rendered.
    useEffect(() => {
        const id = window.location.hash.slice(1);
        if (id) {
            document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, []);

    return (
        <AdminLayout judul="Dashboard" deskripsi={`Ringkasan booking dan performa ${tenant?.nama_bisnis ?? ''}.`}>
            <Head title="Dashboard" />

            {props.filterCabang && <FilterCabang filter={props.filterCabang} url="/admin" only={DATA_DASHBOARD} />}

            <div className={`mb-6 grid grid-cols-2 gap-3 md:gap-3.5 ${statistikLengkap ? 'lg:grid-cols-4' : ''}`}>
                <KartuAngka label="Booking Hari Ini" nilai={angka(statistik.bookingHariIni)} />
                <KartuAngka label="Menunggu Konfirmasi" nilai={angka(statistik.menungguKonfirmasi)} />
                {statistikLengkap && (
                    <>
                        <KartuAngka label="Booking Minggu Ini" nilai={angka(statistik.bookingMingguIni ?? 0)} />
                        <KartuAngka label="Tingkat Keterisian" nilai={`${statistik.tingkatKeterisian ?? 0}%`} />
                    </>
                )}
            </div>

            <DaftarBooking booking={props.bookingTerbaru} />
            {props.perbandinganCabang && <PerbandinganCabang data={props.perbandinganCabang} />}
            {props.staf && <PanelStaf staf={props.staf} />}
            {props.membership && <PanelMembership data={props.membership} />}
            {props.reminder && <PanelReminder reminder={props.reminder} />}
        </AdminLayout>
    );
}
