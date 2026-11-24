import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import Field from '../../../components/form/Field';
import KonfirmasiDialog from '../../../components/KonfirmasiDialog';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Button } from '../../../components/ui/button';
import { Card } from '../../../components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../../components/ui/dialog';
import { Input } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
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

/** Shop promo codes: list with an add/edit dialog. Customers apply them at checkout. */
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
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {promo.map((item) => {
                        const status = statusPromo(item);

                        return (
                            <Card key={item.id} className="flex flex-col p-5">
                                <div className="mb-1 flex items-start justify-between gap-2">
                                    <span className="break-all font-mono text-base font-bold tracking-wide">{item.code}</span>
                                    <span className={cn('shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold', status.kelas)}>{status.label}</span>
                                </div>
                                <p className="mb-1 text-sm font-semibold text-green">{item.ringkasan}</p>
                                {item.description && <p className="mb-2 text-sm text-ink-mid">{item.description}</p>}
                                <dl className="mb-3 space-y-0.5 text-xs text-muted-foreground">
                                    <div>
                                        Dipakai {item.terpakai}
                                        {item.quota !== null ? ` dari ${item.quota}` : ''} kali
                                    </div>
                                    <div>
                                        {item.starts_on || item.ends_on
                                            ? `Berlaku ${item.starts_on ? tanggal(item.starts_on) : 'sekarang'} sampai ${item.ends_on ? tanggal(item.ends_on) : 'tanpa batas'}`
                                            : 'Berlaku tanpa batas waktu'}
                                    </div>
                                </dl>
                                <div className="mt-auto flex flex-wrap gap-2">
                                    <Button variant="ghost" size="sm" onClick={() => buka(item)}>
                                        <Pencil />
                                        Edit
                                    </Button>
                                    <Button variant="ghost" size="sm" className="text-destructive hover:bg-destructive/10 hover:text-destructive" onClick={() => setAkanDihapus(item)}>
                                        <Trash2 />
                                        Hapus
                                    </Button>
                                </div>
                            </Card>
                        );
                    })}
                </div>
            )}

            <Dialog open={diedit !== null} onOpenChange={(terbuka) => !terbuka && setDiedit(null)}>
                <DialogContent className="max-h-[90dvh] overflow-y-auto">
                    <form onSubmit={simpan} className="grid gap-4">
                        <DialogHeader>
                            <DialogTitle>{diedit === 'baru' ? 'Tambah Kode Promo' : 'Edit Kode Promo'}</DialogTitle>
                            <DialogDescription>Potongan hanya untuk harga barang, ongkir tidak ikut dipotong.</DialogDescription>
                        </DialogHeader>
                        <Field id="promo-kode" label="Kode" error={form.errors.code} hint="Huruf besar dan angka tanpa spasi, misal HEMAT10." wajib>
                            <Input
                                id="promo-kode"
                                value={String(form.data.code)}
                                onChange={(event) => form.setData('code', event.target.value.toUpperCase().replace(/\s/g, ''))}
                                maxLength={40}
                                autoCapitalize="characters"
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
