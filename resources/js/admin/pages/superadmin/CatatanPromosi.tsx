import { Head, router, useForm } from '@inertiajs/react';
import { Copy, Eye, Pencil, Pin, Plus, Search, Trash2 } from 'lucide-react';
import { useMemo, useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import AksiKartu from '../../components/AksiKartu';
import ChipFilter from '../../components/ChipFilter';
import Field from '../../components/form/Field';
import CatatanPratinjau from '../../components/CatatanPratinjau';
import KonfirmasiDialog from '../../components/KonfirmasiDialog';
import AdminLayout from '../../components/layout/AdminLayout';
import { Button } from '../../components/ui/button';
import { Card, CardHeader, CardTitle } from '../../components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../components/ui/dialog';
import { Input, Textarea } from '../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../components/ui/table';

interface Catatan {
    id: number;
    judul: string;
    kategori: string;
    isi: string;
    disematkan: boolean;
    diubah: string;
}

interface Kategori {
    nilai: string;
    label: string;
}

const KOSONG = { judul: '', kategori: 'google-ads', isi: '', disematkan: '0' };

/** Note body flattened to one line, for the truncated preview in the table. */
const cuplikan = (isi: string) => isi.replace(/\s+/g, ' ').trim();

function LabelKategori({ label }: { label: string }) {
    return <span className="whitespace-nowrap rounded-full bg-green-pale px-2.5 py-0.5 text-xs font-semibold text-green">{label}</span>;
}

/** Superadmin notes for promotions and ads: campaign plans, keyword lists, image prompts. Plain text only. */
export default function CatatanPromosiPage({ catatan, kategori }: { catatan: Catatan[]; kategori: Kategori[] }) {
    const [diedit, setDiedit] = useState<Catatan | 'baru' | null>(null);
    const [dilihat, setDilihat] = useState<Catatan | null>(null);
    const [akanDihapus, setAkanDihapus] = useState<Catatan | null>(null);
    const [filter, setFilter] = useState('semua');
    const [kata, setKata] = useState('');
    const form = useForm(KOSONG);
    const labelKategori = useMemo(() => Object.fromEntries(kategori.map((item) => [item.nilai, item.label])), [kategori]);

    const tampil = useMemo(() => {
        const cari = kata.trim().toLowerCase();

        return catatan.filter((item) => (filter === 'semua' || item.kategori === filter) && (cari === '' || `${item.judul} ${item.isi}`.toLowerCase().includes(cari)));
    }, [catatan, filter, kata]);

    const buka = (target: Catatan | 'baru') => {
        form.clearErrors();
        form.setData(target === 'baru' ? KOSONG : { judul: target.judul, kategori: target.kategori, isi: target.isi, disematkan: target.disematkan ? '1' : '0' });
        setDiedit(target);
    };

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        const opsi = { preserveScroll: true, onSuccess: () => setDiedit(null) };
        if (diedit === 'baru') {
            form.post('/superadmin/catatan-promosi', opsi);
        } else if (diedit) {
            form.put(`/superadmin/catatan-promosi/${diedit.id}`, opsi);
        }
    };

    const salin = async (item: Catatan) => {
        try {
            await navigator.clipboard.writeText(item.isi);
            toast.success('Isi catatan disalin');
        } catch {
            toast.error('Gagal menyalin, salin manual dari kotak edit.');
        }
    };

    return (
        <AdminLayout
            judul="Catatan Promosi"
            deskripsi="Catatan rencana promosi dan iklan: kampanye Google Ads, kata kunci, prompt gambar, dan ide lainnya."
            aksi={
                <Button className="w-full sm:w-auto" onClick={() => buka('baru')}>
                    <Plus />
                    Tambah Catatan
                </Button>
            }
        >
            <Head title="Catatan Promosi" />

            <div className="relative mb-4 sm:max-w-xs">
                <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                <Input value={kata} onChange={(event) => setKata(event.target.value)} placeholder="Cari judul atau isi" className="pl-9" aria-label="Cari catatan" />
            </div>
            <ChipFilter
                pilihan={[{ nilai: 'semua', label: 'Semua', jumlah: catatan.length }, ...kategori.map((item) => ({ ...item, jumlah: catatan.filter((c) => c.kategori === item.nilai).length }))]}
                aktif={filter}
                onPilih={setFilter}
                label="Filter kategori catatan"
            />

            {catatan.length === 0 ? (
                <Card className="px-5 py-8 text-center text-sm text-muted-foreground">Belum ada catatan. Tambahkan catatan pertama, misalnya rencana kampanye Google Ads.</Card>
            ) : tampil.length === 0 ? (
                <Card className="px-5 py-8 text-center text-sm text-muted-foreground">Tidak ada catatan yang cocok.</Card>
            ) : (
                <Card className="overflow-hidden">
                    <CardHeader className="border-b border-border py-4">
                        <CardTitle className="text-xs uppercase tracking-wide text-green">
                            {tampil.length === catatan.length ? `${catatan.length} Catatan` : `${tampil.length} dari ${catatan.length} Catatan`}
                        </CardTitle>
                    </CardHeader>
                    <div className="hidden xl:block">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Catatan</TableHead>
                                    <TableHead className="w-36">Kategori</TableHead>
                                    <TableHead className="w-36">Diubah</TableHead>
                                    <TableHead className="w-48 text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tampil.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="max-w-0 py-3.5">
                                            <button type="button" onClick={() => setDilihat(item)} className="flex max-w-full items-start gap-1.5 text-left font-bold leading-snug hover:text-green hover:underline" title="Pratinjau catatan">
                                                {item.disematkan && <Pin className="mt-0.5 size-3.5 shrink-0 text-gold" aria-label="Disematkan" />}
                                                <span className="line-clamp-2">{item.judul}</span>
                                            </button>
                                            <p className="mt-0.5 truncate text-xs text-muted-foreground">{cuplikan(item.isi)}</p>
                                        </TableCell>
                                        <TableCell className="py-3.5">
                                            <LabelKategori label={labelKategori[item.kategori] ?? item.kategori} />
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap py-3.5 text-sm text-ink-mid">{item.diubah}</TableCell>
                                        <TableCell className="py-3.5">
                                            <div className="flex items-center justify-end gap-1.5">
                                                <Button type="button" variant="outline" size="icon" className="size-9 md:size-9" onClick={() => setDilihat(item)} title="Pratinjau" aria-label={`Pratinjau ${item.judul}`}>
                                                    <Eye />
                                                </Button>
                                                <Button type="button" variant="outline" size="icon" className="size-9 md:size-9" onClick={() => void salin(item)} title="Salin isi" aria-label={`Salin isi ${item.judul}`}>
                                                    <Copy />
                                                </Button>
                                                <Button type="button" variant="outline" size="icon" className="size-9 md:size-9" onClick={() => buka(item)} title="Edit catatan" aria-label={`Edit ${item.judul}`}>
                                                    <Pencil />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    className="size-9 text-destructive hover:bg-destructive/10 hover:text-destructive md:size-9"
                                                    onClick={() => setAkanDihapus(item)}
                                                    title="Hapus catatan"
                                                    aria-label={`Hapus ${item.judul}`}
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    <ul className="divide-y divide-cream xl:hidden">
                        {tampil.map((item) => (
                            <li key={item.id} className="min-w-0 px-4 py-4">
                                <div className="flex items-start justify-between gap-2">
                                    <button type="button" onClick={() => setDilihat(item)} className="min-w-0 text-left font-bold leading-snug">
                                        {item.judul}
                                    </button>
                                    {item.disematkan && <Pin className="mt-1 size-4 shrink-0 text-gold" aria-label="Disematkan" />}
                                </div>
                                <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                    <LabelKategori label={labelKategori[item.kategori] ?? item.kategori} />
                                    <span>Diubah {item.diubah}</span>
                                </div>
                                <p className="mt-2 line-clamp-3 whitespace-pre-wrap text-sm text-ink-mid [overflow-wrap:anywhere]">{item.isi}</p>
                                <AksiKartu
                                    className="mt-3"
                                    kiri={
                                        <>
                                            <Button variant="outline" size="sm" onClick={() => setDilihat(item)} aria-label="Pratinjau catatan">
                                                <Eye />
                                                <span className="hidden sm:inline">Pratinjau</span>
                                            </Button>
                                            <Button variant="outline" size="sm" onClick={() => void salin(item)} aria-label="Salin isi catatan">
                                                <Copy />
                                                <span className="hidden sm:inline">Salin</span>
                                            </Button>
                                        </>
                                    }
                                    onEdit={() => buka(item)}
                                    onHapus={() => setAkanDihapus(item)}
                                />
                            </li>
                        ))}
                    </ul>
                </Card>
            )}

            <Dialog open={dilihat !== null} onOpenChange={(terbuka) => !terbuka && setDilihat(null)}>
                <DialogContent className="sm:max-w-3xl">
                    <DialogHeader className="pr-8">
                        <DialogTitle className="text-xl leading-snug">{dilihat?.judul}</DialogTitle>
                        <DialogDescription>{dilihat ? `${labelKategori[dilihat.kategori] ?? dilihat.kategori}, diubah ${dilihat.diubah}` : ''}</DialogDescription>
                    </DialogHeader>
                    {dilihat && <CatatanPratinjau isi={dilihat.isi} />}
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => dilihat && void salin(dilihat)}>
                            <Copy />
                            Salin semua
                        </Button>
                        <Button
                            type="button"
                            onClick={() => {
                                const target = dilihat;
                                setDilihat(null);
                                if (target) buka(target);
                            }}
                        >
                            <Pencil />
                            Edit
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={diedit !== null} onOpenChange={(terbuka) => !terbuka && setDiedit(null)}>
                <DialogContent className="sm:max-w-2xl">
                    <form onSubmit={simpan} className="grid gap-4">
                        <DialogHeader>
                            <DialogTitle>{diedit === 'baru' ? 'Tambah Catatan' : 'Catatan Promosi'}</DialogTitle>
                            <DialogDescription>Teks biasa. Tempel langsung rencana iklan atau prompt, format baris tetap terjaga.</DialogDescription>
                        </DialogHeader>
                        <Field id="catatan-judul" label="Judul" error={form.errors.judul} wajib>
                            <Input id="catatan-judul" value={form.data.judul} onChange={(event) => form.setData('judul', event.target.value)} maxLength={150} required />
                        </Field>
                        <Field id="catatan-kategori" label="Kategori" error={form.errors.kategori}>
                            <Select value={form.data.kategori} onValueChange={(nilai) => form.setData('kategori', nilai)}>
                                <SelectTrigger id="catatan-kategori">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {kategori.map((item) => (
                                        <SelectItem key={item.nilai} value={item.nilai}>
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field id="catatan-isi" label="Isi catatan" error={form.errors.isi} wajib>
                            <Textarea
                                id="catatan-isi"
                                rows={14}
                                value={form.data.isi}
                                onChange={(event) => form.setData('isi', event.target.value)}
                                className="font-mono text-xs leading-relaxed"
                                required
                            />
                        </Field>
                        <label className="flex min-h-10 items-center gap-2 text-sm">
                            <input type="checkbox" checked={form.data.disematkan === '1'} onChange={(event) => form.setData('disematkan', event.target.checked ? '1' : '0')} />
                            Sematkan di paling atas
                        </label>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDiedit(null)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <KonfirmasiDialog
                terbuka={akanDihapus !== null}
                onTutup={() => setAkanDihapus(null)}
                onLanjut={() => akanDihapus && router.delete(`/superadmin/catatan-promosi/${akanDihapus.id}`, { preserveScroll: true, onFinish: () => setAkanDihapus(null) })}
                judul="Hapus catatan?"
                deskripsi={`Catatan "${akanDihapus?.judul ?? ''}" dihapus permanen.`}
                labelLanjut="Ya, Hapus"
            />
        </AdminLayout>
    );
}
