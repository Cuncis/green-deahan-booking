import { Head, router } from '@inertiajs/react';
import { Copy, Search } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import AdminLayout from '../../components/layout/AdminLayout';
import Pagination, { type Paginated } from '../../components/Pagination';
import { Badge, type BadgeProps } from '../../components/ui/badge';
import { Button } from '../../components/ui/button';
import { Card } from '../../components/ui/card';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../components/ui/select';

interface Entri {
    id: string;
    waktu: string;
    lingkungan: string;
    level: string;
    pesan: string;
    kelas: string | null;
    lokasi: string | null;
    detail: string;
}

interface Props {
    daftar: Paginated<Entri>;
    filter: { q: string; level: string; perHalaman: number };
    pilihanLevel: { nilai: string; label: string }[];
    pilihanPerHalaman: number[];
    terpotong: boolean;
    ukuranLogMb: number;
    bacaMaksMb: number;
}

const varianLevel = (level: string): BadgeProps['variant'] =>
    ['emergency', 'alert', 'critical', 'error'].includes(level) ? 'cancelled' : level === 'warning' ? 'pending' : 'info';

/** Laravel log file as a searchable list: message, file and line, and the stack trace. */
export default function LogError({ daftar, filter, pilihanLevel, pilihanPerHalaman, terpotong, ukuranLogMb, bacaMaksMb }: Props) {
    const [kata, setKata] = useState(filter.q);

    const buka = (perubahan: Partial<{ q: string; level: string; per_halaman: number }>) =>
        router.get(
            '/superadmin/log-error',
            { q: kata, level: filter.level, per_halaman: filter.perHalaman, ...perubahan },
            { preserveScroll: true, preserveState: true, replace: true },
        );

    const cari = (event: FormEvent) => {
        event.preventDefault();
        buka({ q: kata });
    };

    const salin = (teks: string) => navigator.clipboard?.writeText(teks).then(() => toast.success('Detail error disalin'));

    return (
        <AdminLayout judul="Log Error" deskripsi="Error yang tercatat di file log aplikasi, yang terbaru di atas.">
            <Head title="Log Error" />

            <Card className="mb-5 p-4">
                <form onSubmit={cari} className="grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem_9rem_auto] md:items-end">
                    <div className="space-y-1.5">
                        <Label htmlFor="cari-log">Cari</Label>
                        <Input id="cari-log" value={kata} maxLength={100} placeholder="Pesan, jenis error, atau file" onChange={(event) => setKata(event.target.value)} />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="filter-level">Level</Label>
                        <Select value={filter.level} onValueChange={(level) => buka({ level })}>
                            <SelectTrigger id="filter-level">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {pilihanLevel.map((item) => (
                                    <SelectItem key={item.nilai} value={item.nilai}>
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="per-halaman">Per halaman</Label>
                        <Select value={String(filter.perHalaman)} onValueChange={(nilai) => buka({ per_halaman: Number(nilai) })}>
                            <SelectTrigger id="per-halaman">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {pilihanPerHalaman.map((jumlah) => (
                                    <SelectItem key={jumlah} value={String(jumlah)}>
                                        {jumlah}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit">
                        <Search />
                        Cari
                    </Button>
                </form>
                {terpotong && (
                    <p className="mt-3 text-xs text-muted-foreground">
                        Halaman ini membaca {bacaMaksMb} MB terakhir dari {ukuranLogMb} MB log, jadi error yang lebih lama tidak tampil.
                    </p>
                )}
            </Card>

            <Card className="overflow-hidden">
                <div className="border-b border-border px-4 py-3 text-xs font-bold uppercase tracking-wide text-green md:px-5">{daftar.total} Entri</div>
                {daftar.data.length === 0 ? (
                    <p className="px-5 py-8 text-center text-sm text-muted-foreground">Tidak ada entri log yang cocok.</p>
                ) : (
                    <>
                        <ul className="divide-y divide-border">
                            {daftar.data.map((entri) => (
                                <li key={entri.id}>
                                    <details className="group">
                                        <summary className="flex min-h-14 cursor-pointer list-none flex-col gap-1 px-4 py-3 hover:bg-accent md:px-5 [&::-webkit-details-marker]:hidden">
                                            <span className="flex flex-wrap items-center gap-2">
                                                <Badge variant={varianLevel(entri.level)} className="uppercase">
                                                    {entri.level}
                                                </Badge>
                                                <span className="text-xs text-muted-foreground">
                                                    {entri.waktu} ({entri.lingkungan})
                                                </span>
                                            </span>
                                            <span className="break-words text-sm font-semibold">{entri.pesan || '(tanpa pesan)'}</span>
                                            {(entri.kelas || entri.lokasi) && (
                                                <span className="break-all text-xs text-muted-foreground">
                                                    {entri.kelas}
                                                    {entri.kelas && entri.lokasi && ' di '}
                                                    {entri.lokasi}
                                                </span>
                                            )}
                                        </summary>
                                        <div className="border-t border-border bg-muted/40 px-4 py-3 md:px-5">
                                            <div className="mb-2 flex justify-end">
                                                <Button type="button" variant="outline" size="sm" onClick={() => salin(entri.detail)}>
                                                    <Copy />
                                                    Salin
                                                </Button>
                                            </div>
                                            <pre className="max-h-96 overflow-auto whitespace-pre-wrap break-words text-xs leading-relaxed">{entri.detail}</pre>
                                        </div>
                                    </details>
                                </li>
                            ))}
                        </ul>
                        <Pagination halaman={daftar} />
                    </>
                )}
            </Card>
        </AdminLayout>
    );
}
