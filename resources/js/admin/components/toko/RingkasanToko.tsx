import { Link } from '@inertiajs/react';
import { ArrowRight, ShoppingBag } from 'lucide-react';
import { angka, rupiah } from '../../lib/format';
import { Card, CardHeader, CardTitle } from '../ui/card';

export interface DataRingkasanToko {
    perluDiproses: number;
    hariIni: { pesanan: number; omzet: number; untung: number };
    harian: { tanggal: string; label: string; pesanan: number; omzet: number; untung: number }[];
}

/** Online shop summary on the superadmin dashboard: today's numbers and the last 7 days. */
export default function RingkasanToko({ data, pesananUrl }: { data: DataRingkasanToko; pesananUrl: string }) {
    const tujuhHari = data.harian.reduce((jumlah, hari) => ({ omzet: jumlah.omzet + hari.omzet, untung: jumlah.untung + hari.untung }), { omzet: 0, untung: 0 });

    return (
        <Card className="mb-6 overflow-hidden">
            <CardHeader className="flex-row items-center justify-between gap-3 border-b border-border py-4">
                <CardTitle className="flex items-center gap-2 text-xs uppercase tracking-wide text-green">
                    <ShoppingBag className="size-4" aria-hidden="true" />
                    Toko Online
                </CardTitle>
                <Link href={pesananUrl} className="inline-flex min-h-10 items-center gap-1 text-xs font-semibold text-plum hover:underline">
                    Pesanan toko
                    <ArrowRight className="size-3.5" />
                </Link>
            </CardHeader>
            <div className="grid grid-cols-2 gap-px bg-border md:grid-cols-4">
                {[
                    { label: 'Perlu dibeli ke supplier', nilai: angka(data.perluDiproses), sorot: data.perluDiproses > 0 },
                    { label: 'Pesanan dibayar hari ini', nilai: angka(data.hariIni.pesanan) },
                    { label: 'Omzet hari ini', nilai: rupiah(data.hariIni.omzet) },
                    { label: 'Untung hari ini', nilai: rupiah(data.hariIni.untung) },
                ].map((item) => (
                    <div key={item.label} className="bg-card p-4">
                        <div className="text-xs font-semibold text-muted-foreground">{item.label}</div>
                        <div className={`mt-1 font-display text-xl font-semibold ${item.sorot ? 'text-amber' : ''}`}>{item.nilai}</div>
                    </div>
                ))}
            </div>
            <div className="overflow-x-auto border-t border-border">
                <table className="w-full min-w-[28rem] text-sm">
                    <thead>
                        <tr className="bg-muted text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th className="px-4 py-2.5 font-bold">Tanggal</th>
                            <th className="px-4 py-2.5 text-right font-bold">Pesanan</th>
                            <th className="px-4 py-2.5 text-right font-bold">Omzet</th>
                            <th className="px-4 py-2.5 text-right font-bold">Untung</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-cream">
                        {[...data.harian].reverse().map((hari) => (
                            <tr key={hari.tanggal}>
                                <td className="px-4 py-2.5">{hari.label}</td>
                                <td className="px-4 py-2.5 text-right tabular-nums">{angka(hari.pesanan)}</td>
                                <td className="px-4 py-2.5 text-right tabular-nums">{rupiah(hari.omzet)}</td>
                                <td className="px-4 py-2.5 text-right tabular-nums">{rupiah(hari.untung)}</td>
                            </tr>
                        ))}
                        <tr className="font-bold">
                            <td className="px-4 py-2.5">7 hari</td>
                            <td className="px-4 py-2.5 text-right tabular-nums">{angka(data.harian.reduce((n, hari) => n + hari.pesanan, 0))}</td>
                            <td className="px-4 py-2.5 text-right tabular-nums">{rupiah(tujuhHari.omzet)}</td>
                            <td className="px-4 py-2.5 text-right tabular-nums">{rupiah(tujuhHari.untung)}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    );
}
