import { Head } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, Cell, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import FilterCabang, { type DataFilterCabang } from '../../components/FilterCabang';
import AdminLayout from '../../components/layout/AdminLayout';
import { Card, CardDescription, CardHeader, CardTitle } from '../../components/ui/card';
import { rupiah } from '../../lib/format';

interface Props {
    filterCabang: DataFilterCabang | null;
    pendapatanMingguan: { tanggal: string; hari: string; pendapatan: number }[];
    jamRamai: number[];
}

const HIJAU = '#3A6B4A';
const HIJAU_MUDA = '#D6EAD9';

const ribuan = (nilai: number) => (nilai >= 1_000_000 ? `${(nilai / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })}jt` : `${Math.round(nilai / 1000)}rb`);
const jamLabel = (jam: number) => `${String(jam).padStart(2, '0')}.00`;

function TooltipPendapatan({ active, payload, label }: { active?: boolean; payload?: { value: number }[]; label?: string }) {
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className="rounded-lg border border-border bg-card px-3 py-2 text-xs shadow-md">
            <div className="font-bold">{label}</div>
            <div className="text-ink-mid">{rupiah(payload[0].value)}</div>
        </div>
    );
}

function SelJam({ jam, jumlah, maks }: { jam: number; jumlah: number; maks: number }) {
    const intensitas = jumlah > 0 ? Math.max(jumlah / maks, 0.15) : 0.05;

    return (
        <div className="flex flex-col items-center gap-1">
            <div
                className="h-9 w-full rounded-md"
                style={{ backgroundColor: `rgba(58, 107, 74, ${intensitas})` }}
                title={`Jam ${jamLabel(jam)}, ${jumlah} booking`}
                aria-label={`Jam ${jamLabel(jam)}, ${jumlah} booking`}
            />
            <span className="h-4 text-xs text-muted-foreground">{jam % 3 === 0 ? String(jam).padStart(2, '0') : ''}</span>
        </div>
    );
}

export default function Laporan({ filterCabang, pendapatanMingguan, jamRamai }: Props) {
    const total = pendapatanMingguan.reduce((jumlah, hari) => jumlah + hari.pendapatan, 0);
    const tertinggi = Math.max(0, ...pendapatanMingguan.map((hari) => hari.pendapatan));
    const maksJam = Math.max(1, ...jamRamai);
    const tigaTeramai = jamRamai
        .map((jumlah, jam) => ({ jam, jumlah }))
        .filter((item) => item.jumlah > 0)
        .sort((a, b) => b.jumlah - a.jumlah)
        .slice(0, 3);

    return (
        <AdminLayout judul="Laporan Pendapatan" deskripsi="Pendapatan 7 hari terakhir dan jam paling ramai.">
            <Head title="Laporan Pendapatan" />

            {filterCabang && <FilterCabang filter={filterCabang} url="/admin/laporan" only={['filterCabang', 'pendapatanMingguan', 'jamRamai']} />}

            <Card className="mb-5">
                <CardHeader className="pb-2">
                    <CardTitle className="text-xs uppercase tracking-wide text-green">Pendapatan 7 Hari Terakhir</CardTitle>
                    <div className="font-display text-2xl font-semibold">{rupiah(total)}</div>
                    <CardDescription>Dari booking yang sudah dikonfirmasi atau selesai.</CardDescription>
                </CardHeader>
                <div className="h-64 px-2 pb-4">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={pendapatanMingguan} margin={{ top: 8, right: 12, left: 0, bottom: 0 }}>
                            <CartesianGrid vertical={false} stroke="#E0D5C0" strokeDasharray="3 3" />
                            <XAxis dataKey="hari" tickLine={false} axisLine={false} tick={{ fill: '#8A7260', fontSize: 12 }} />
                            <YAxis tickLine={false} axisLine={false} width={56} tickFormatter={ribuan} tick={{ fill: '#8A7260', fontSize: 12 }} />
                            <Tooltip content={<TooltipPendapatan />} cursor={{ fill: 'rgba(58,107,74,0.06)' }} />
                            <Bar dataKey="pendapatan" radius={[6, 6, 0, 0]} maxBarSize={44}>
                                {pendapatanMingguan.map((hari) => (
                                    <Cell key={hari.tanggal} fill={hari.pendapatan === tertinggi && tertinggi > 0 ? HIJAU : HIJAU_MUDA} />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </Card>

            <Card className="p-5">
                <div className="mb-1 text-xs font-bold uppercase tracking-wide text-green">Jam Ramai</div>
                <p className="mb-4 text-sm text-muted-foreground">Semakin gelap, semakin sering jam itu dibooking.</p>

                {/* Phones: two rows of 12 hours; desktop: one row of 24. */}
                <div className="grid grid-cols-12 gap-1 md:hidden">
                    {jamRamai.slice(0, 12).map((jumlah, jam) => (
                        <SelJam key={jam} jam={jam} jumlah={jumlah} maks={maksJam} />
                    ))}
                </div>
                <div className="mt-2 grid grid-cols-12 gap-1 md:hidden">
                    {jamRamai.slice(12).map((jumlah, index) => (
                        <SelJam key={index + 12} jam={index + 12} jumlah={jumlah} maks={maksJam} />
                    ))}
                </div>
                <div
                    className="hidden grid-cols-24 gap-1 md:grid"
                    style={{
                        gridTemplateColumns: 'repeat(24, minmax(0, 1fr))',
                    }}
                >
                    {jamRamai.map((jumlah, jam) => (
                        <SelJam key={jam} jam={jam} jumlah={jumlah} maks={maksJam} />
                    ))}
                </div>

                <div className="mt-4 border-t border-border pt-4 text-sm">
                    {tigaTeramai.length === 0 ? (
                        <span className="text-muted-foreground">Belum ada slot yang dibooking.</span>
                    ) : (
                        <>
                            <span className="font-semibold">Paling ramai: </span>
                            {tigaTeramai.map((item, index) => (
                                <span key={item.jam}>
                                    {index > 0 && ', '}
                                    {jamLabel(item.jam)} ({item.jumlah} booking)
                                </span>
                            ))}
                        </>
                    )}
                </div>
            </Card>
        </AdminLayout>
    );
}
