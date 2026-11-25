import { Head, router, useForm } from '@inertiajs/react';
import { Copy, Eye, Pencil, Pin, Plus, Search } from 'lucide-react';
import { useMemo, useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import AksiKartu from '../../components/AksiKartu';
import Field from '../../components/form/Field';
import CatatanPratinjau from '../../components/CatatanPratinjau';
import KonfirmasiDialog from '../../components/KonfirmasiDialog';
import AdminLayout from '../../components/layout/AdminLayout';
import { Button } from '../../components/ui/button';
import { Card } from '../../components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../components/ui/dialog';
import { Input, Textarea } from '../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../components/ui/select';
import { cn } from '../../lib/utils';

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

            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div className="relative sm:max-w-xs sm:flex-1">
                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <Input value={kata} onChange={(event) => setKata(event.target.value)} placeholder="Cari judul atau isi" className="pl-9" aria-label="Cari catatan" />
                </div>
                <div className="flex flex-wrap gap-2" role="group" aria-label="Filter kategori">
                    {[{ nilai: 'semua', label: 'Semua' }, ...kategori].map((item) => (
                        <button
                            key={item.nilai}
                            type="button"
                            onClick={() => setFilter(item.nilai)}
                            aria-pressed={filter === item.nilai}
                            className={cn(
                                'min-h-10 rounded-full border px-4 text-sm font-medium transition-colors',
                                filter === item.nilai ? 'border-green bg-green text-white' : 'border-border bg-card text-ink-mid hover:bg-accent',
                            )}
                        >
                            {item.label}
                        </button>
                    ))}
                </div>
            </div>

            {catatan.length === 0 ? (
                <Card className="px-5 py-8 text-center text-sm text-muted-foreground">Belum ada catatan. Tambahkan catatan pertama, misalnya rencana kampanye Google Ads.</Card>
            ) : tampil.length === 0 ? (
                <Card className="px-5 py-8 text-center text-sm text-muted-foreground">Tidak ada catatan yang cocok.</Card>
            ) : (
                <div className="grid gap-4 md:grid-cols-2">
                    {tampil.map((item) => (
                        <Card key={item.id} className="flex min-w-0 flex-col p-5">
                            <div className="mb-2 flex items-start justify-between gap-2">
                                <span className="font-display text-base font-semibold">{item.judul}</span>
                                {item.disematkan && <Pin className="mt-1 size-4 shrink-0 text-gold" aria-label="Disematkan" />}
                            </div>
                            <div className="mb-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <span className="rounded-full bg-green-pale px-2.5 py-0.5 font-semibold text-green">{labelKategori[item.kategori] ?? item.kategori}</span>
                                <span>Diubah {item.diubah}</span>
                            </div>
                            <p className="mb-4 line-clamp-6 whitespace-pre-wrap [overflow-wrap:anywhere] text-sm text-ink-mid">{item.isi}</p>
                            <AksiKartu
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
                        </Card>
                    ))}
                </div>
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
