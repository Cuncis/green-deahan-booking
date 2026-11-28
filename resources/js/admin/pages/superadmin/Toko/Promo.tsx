import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import AksiKartu from '../../../components/AksiKartu';
import Field from '../../../components/form/Field';
import KonfirmasiDialog from '../../../components/KonfirmasiDialog';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Button } from '../../../components/ui/button';
import { Card, CardHeader, CardTitle } from '../../../components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../../components/ui/dialog';
import { Input } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { rupiah } from '../../../lib/format';
import { cn } from '../../../lib/utils';

interface Promo {
    id: number;
    code: string;
    description: string | null;
    type: 'percent' | 'fixed';
    value: number;
    max_discount: number | null;
    min_subtotal: number;
    quota: number | null;
    starts_on: string | null;
    ends_on: string | null;
    is_active: boolean;
    terpakai: number;
    ringkasan: string;
}

const KOSONG = { code: '', description: '', type: 'percent', value: '', max_discount: '', min_subtotal: '', quota: '', starts_on: '', ends_on: '', is_active: true };

const tanggal = (nilai: string) => new Date(`${nilai}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

/** Status shown on each card, computed from the dates, quota, and the active switch. */
function statusPromo(promo: Promo): { label: string; kelas: string } {
    const hariIni = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' });

    if (!promo.is_active) return { label: 'Nonaktif', kelas: 'bg-muted text-muted-foreground' };
    if (promo.ends_on && promo.ends_on < hariIni) return { label: 'Berakhir', kelas: 'bg-muted text-muted-foreground' };
    if (promo.starts_on && promo.starts_on > hariIni) return { label: 'Belum mulai', kelas: 'bg-gold-pale text-ink' };
    if (promo.quota !== null && promo.terpakai >= promo.quota) return { label: 'Kuota habis', kelas: 'bg-muted text-muted-foreground' };

    return { label: 'Aktif', kelas: 'bg-green-pale text-green' };
}

const masaBerlaku = (promo: Promo) =>
    promo.starts_on || promo.ends_on
        ? `${promo.starts_on ? tanggal(promo.starts_on) : 'Sekarang'} sampai ${promo.ends_on ? tanggal(promo.ends_on) : 'tanpa batas'}`
        : 'Tanpa batas waktu';

/** Times used, with a small bar when the code has a quota. */
function Pemakaian({ item }: { item: Promo }) {
    const persen = item.quota ? Math.min(100, Math.round((item.terpakai / item.quota) * 100)) : null;
    return (
        <div className="text-xs text-muted-foreground">
            <span className="font-semibold tabular-nums text-ink">{item.terpakai}</span>
            {item.quota !== null ? ` dari ${item.quota}` : ''} kali
            {persen !== null && (
                <div className="mt-1 h-1.5 w-full max-w-32 overflow-hidden rounded-full bg-muted" role="presentation">
                    <div className={cn('h-full rounded-full', persen >= 100 ? 'bg-muted-foreground' : 'bg-green')} style={{ width: `${persen}%` }} />
                </div>
            )}
        </div>
    );
}

/** Shop promo codes: table (list on smaller screens) with an add/edit dialog. Customers apply them at checkout. */
export default function PromoPage({ promo }: { promo: Promo[] }) {
    const [diedit, setDiedit] = useState<Promo | 'baru' | null>(null);
    const [akanDihapus, setAkanDihapus] = useState<Promo | null>(null);
    const form = useForm<Record<string, string | boolean>>(KOSONG);
    const persen = form.data.type === 'percent';

    const buka = (target: Promo | 'baru') => {
        form.clearErrors();
        form.setData(
            target === 'baru'
                ? KOSONG
                : {
                      code: target.code,
                      description: target.description ?? '',
                      type: target.type,
                      value: String(target.value),
                      max_discount: target.max_discount === null ? '' : String(target.max_discount),
                      min_subtotal: target.min_subtotal > 0 ? String(target.min_subtotal) : '',
                      quota: target.quota === null ? '' : String(target.quota),
                      starts_on: target.starts_on ?? '',
                      ends_on: target.ends_on ?? '',
                      is_active: target.is_active,
                  },
        );
        setDiedit(target);
    };

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        const opsi = { preserveScroll: true, onSuccess: () => setDiedit(null) };
        if (diedit === 'baru') {
            form.post('/superadmin/toko/promo', opsi);
        } else if (diedit) {
            form.put(`/superadmin/toko/promo/${diedit.id}`, opsi);
        }
    };

    const angka = (nama: string) => ({
        value: String(form.data[nama] ?? ''),
        inputMode: 'numeric' as const,
        onChange: (event: React.ChangeEvent<HTMLInputElement>) => form.setData(nama, event.target.value.replace(/\D/g, '')),
    });

    return (
        <AdminLayout
            judul="Kode Promo"
            deskripsi="Kode diskon yang bisa diisi pelanggan di halaman checkout toko."
            aksi={
                <Button className="w-full sm:w-auto" onClick={() => buka('baru')}>
                    <Plus />
                    Tambah Kode Promo
                </Button>
            }
        >
            <Head title="Kode Promo" />
            {promo.length === 0 ? (
                <Card className="px-5 py-8 text-center text-sm text-muted-foreground">Belum ada kode promo.</Card>
            ) : (
                <Card className="overflow-hidden">
                    <CardHeader className="border-b border-border py-4">
                        <CardTitle className="text-xs uppercase tracking-wide text-green">{promo.length} Kode Promo</CardTitle>
                    </CardHeader>
                    <div className="hidden xl:block">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Promo</TableHead>
                                    <TableHead className="w-36">Dipakai</TableHead>
                                    <TableHead className="w-52">Berlaku</TableHead>
                                    <TableHead className="w-28">Status</TableHead>
                                    <TableHead className="w-28 text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {promo.map((item) => {
                                    const status = statusPromo(item);
                                    return (
                                        <TableRow key={item.id}>
                                            <TableCell className="max-w-0 py-3.5">
                                                <div className="truncate font-mono font-bold tracking-wide" title={item.code}>
                                                    {item.code}
                                                </div>
                                                <div className="text-sm font-semibold text-green">{item.ringkasan}</div>
                                                {item.description && (
                                                    <div className="truncate text-xs text-muted-foreground" title={item.description}>
                                                        {item.description}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell className="py-3.5">
                                                <Pemakaian item={item} />
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap py-3.5 text-sm text-ink-mid">{masaBerlaku(item)}</TableCell>
                                            <TableCell className="py-3.5">
                                                <span className={cn('whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold', status.kelas)}>{status.label}</span>
                                            </TableCell>
                                            <TableCell className="py-3.5">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    <Button type="button" variant="outline" size="icon" className="size-9 md:size-9" onClick={() => buka(item)} title="Edit kode promo" aria-label={`Edit ${item.code}`}>
                                                        <Pencil />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        className="size-9 text-destructive hover:bg-destructive/10 hover:text-destructive md:size-9"
                                                        onClick={() => setAkanDihapus(item)}
                                                        title="Hapus kode promo"
                                                        aria-label={`Hapus ${item.code}`}
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                    <ul className="divide-y divide-cream xl:hidden">
                        {promo.map((item) => {
                            const status = statusPromo(item);
                            return (
                                <li key={item.id} className="px-4 py-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <span className="min-w-0 break-all font-mono font-bold tracking-wide">{item.code}</span>
                                        <span className={cn('shrink-0 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold', status.kelas)}>{status.label}</span>
                                    </div>
                                    <p className="mt-0.5 text-sm font-semibold text-green">{item.ringkasan}</p>
                                    {item.description && <p className="mt-0.5 line-clamp-2 text-sm text-ink-mid">{item.description}</p>}
                                    <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                        <Pemakaian item={item} />
                                        <div className="text-xs text-muted-foreground">{masaBerlaku(item)}</div>
                                    </div>
                                    <AksiKartu className="mt-3" onEdit={() => buka(item)} onHapus={() => setAkanDihapus(item)} />
                                </li>
                            );
                        })}
                    </ul>
                </Card>
            )}

            <Dialog open={diedit !== null} onOpenChange={(terbuka) => !terbuka && setDiedit(null)}>
                <DialogContent>
                    <form onSubmit={simpan} className="grid gap-4">
                        <DialogHeader>
                            <DialogTitle>{diedit === 'baru' ? 'Tambah Kode Promo' : 'Edit Kode Promo'}</DialogTitle>
                            <DialogDescription>Potongan hanya untuk harga barang, ongkir tidak ikut dipotong.</DialogDescription>
                        </DialogHeader>
                        <Field
                            id="promo-kode"
                            label="Kode"
                            error={form.errors.code}
                            hint={diedit === 'baru' ? 'Huruf besar dan angka tanpa spasi, misal HEMAT10.' : 'Kode tidak bisa diganti. Buat kode baru kalau perlu kode lain.'}
                            wajib
                        >
                            <Input
                                id="promo-kode"
                                value={String(form.data.code)}
                                onChange={(event) => form.setData('code', event.target.value.toUpperCase().replace(/\s/g, ''))}
                                maxLength={40}
                                autoCapitalize="characters"
                                readOnly={diedit !== 'baru'}
                                required
                            />
                        </Field>
                        <Field id="promo-ket" label="Keterangan" error={form.errors.description} hint="Catatan untuk admin, tidak tampil ke pelanggan.">
                            <Input id="promo-ket" value={String(form.data.description)} onChange={(event) => form.setData('description', event.target.value)} maxLength={150} />
                        </Field>
                        <div className="grid grid-cols-2 gap-3">
                            <Field id="promo-jenis" label="Jenis potongan" error={form.errors.type}>
                                <Select value={String(form.data.type)} onValueChange={(nilai) => form.setData('type', nilai)}>
                                    <SelectTrigger id="promo-jenis">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="percent">Persen (%)</SelectItem>
                                        <SelectItem value="fixed">Nominal (Rp)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </Field>
                            <Field id="promo-nilai" label={persen ? 'Persen diskon' : 'Potongan (Rp)'} error={form.errors.value} wajib>
                                <Input id="promo-nilai" placeholder={persen ? '10' : '25000'} required {...angka('value')} />
                            </Field>
                        </div>
                        {persen && (
                            <Field id="promo-maks" label="Maksimal diskon (Rp)" error={form.errors.max_discount} hint="Kosongkan kalau tanpa batas.">
                                <Input id="promo-maks" placeholder="50000" {...angka('max_discount')} />
                            </Field>
                        )}
                        <div className="grid grid-cols-2 gap-3">
                            <Field id="promo-min" label="Minimal belanja (Rp)" error={form.errors.min_subtotal} hint="Kosongkan kalau tanpa minimal.">
                                <Input id="promo-min" placeholder="200000" {...angka('min_subtotal')} />
                            </Field>
                            <Field id="promo-kuota" label="Kuota pemakaian" error={form.errors.quota} hint="Kosongkan kalau tanpa batas.">
                                <Input id="promo-kuota" placeholder="100" {...angka('quota')} />
                            </Field>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <Field id="promo-mulai" label="Mulai" error={form.errors.starts_on}>
                                <Input id="promo-mulai" type="date" value={String(form.data.starts_on)} onChange={(event) => form.setData('starts_on', event.target.value)} />
                            </Field>
                            <Field id="promo-akhir" label="Berakhir" error={form.errors.ends_on}>
                                <Input id="promo-akhir" type="date" value={String(form.data.ends_on)} onChange={(event) => form.setData('ends_on', event.target.value)} />
                            </Field>
                        </div>
                        {persen && form.data.value !== '' && form.data.max_discount === '' && Number(form.data.value) >= 50 && (
                            <p className="rounded-lg bg-gold-pale px-3 py-2 text-xs text-ink">
                                Diskon {String(form.data.value)}% tanpa batas maksimal bisa memotong besar. Pertimbangkan mengisi maksimal diskon, misal {rupiah(100000)}.
                            </p>
                        )}
                        <label className="flex min-h-10 items-center gap-2 text-sm">
                            <input type="checkbox" checked={Boolean(form.data.is_active)} onChange={(event) => form.setData('is_active', event.target.checked)} />
                            Aktif (bisa dipakai pelanggan)
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
                onLanjut={() => akanDihapus && router.delete(`/superadmin/toko/promo/${akanDihapus.id}`, { preserveScroll: true, onFinish: () => setAkanDihapus(null) })}
                judul="Hapus kode promo?"
                deskripsi={`Kode ${akanDihapus?.code ?? ''} tidak bisa dipakai lagi. Pesanan yang sudah memakainya tidak berubah.`}
                labelLanjut="Ya, Hapus"
            />
        </AdminLayout>
    );
}
