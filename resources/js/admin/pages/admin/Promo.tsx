import { Head, useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import Field from '../../components/form/Field';
import AdminLayout from '../../components/layout/AdminLayout';
import { Badge } from '../../components/ui/badge';
import { Button } from '../../components/ui/button';
import { Card, CardHeader, CardTitle } from '../../components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../components/ui/dialog';
import { Input } from '../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../components/ui/table';
import { rupiah } from '../../lib/format';

interface PromoRingkas {
    id: number;
    kode: string;
    tipe: string;
    nilai: number;
    mulai: string;
    berakhir: string;
    kuota: number | null;
    aktif: boolean;
}

interface Props {
    promo: PromoRingkas[];
    bawaan: { tanggal_mulai: string; tanggal_berakhir: string };
}

const diskon = (promo: PromoRingkas) => (promo.tipe === 'persen' ? `${promo.nilai}%` : rupiah(promo.nilai));

export default function Promo({ promo, bawaan }: Props) {
    const [dialogTerbuka, setDialogTerbuka] = useState(false);
    const form = useForm({
        kode: '',
        tipe_diskon: 'persen',
        nilai: '',
        tanggal_mulai: bawaan.tanggal_mulai,
        tanggal_berakhir: bawaan.tanggal_berakhir,
        kuota: '',
    });

    const buka = () => {
        form.setData({
            kode: '',
            tipe_diskon: 'persen',
            nilai: '',
            tanggal_mulai: bawaan.tanggal_mulai,
            tanggal_berakhir: bawaan.tanggal_berakhir,
            kuota: '',
        });
        form.clearErrors();
        setDialogTerbuka(true);
    };

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/promo', {
            preserveScroll: true,
            onSuccess: () => setDialogTerbuka(false),
        });
    };

    return (
        <AdminLayout
            judul="Kode Promo"
            deskripsi="Buat kode diskon yang bisa dipakai customer saat booking."
            aksi={
                <Button className="w-full sm:w-auto" onClick={buka}>
                    <Plus />
                    Tambah Kode Baru
                </Button>
            }
        >
            <Head title="Kode Promo" />

            <Card className="overflow-hidden">
                <CardHeader className="border-b border-border py-4">
                    <CardTitle className="text-xs uppercase tracking-wide text-green">Kode Promo</CardTitle>
                </CardHeader>
                {promo.length === 0 ? (
                    <p className="px-5 py-6 text-center text-sm text-muted-foreground">Belum ada kode promo.</p>
                ) : (
                    <>
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Diskon</TableHead>
                                        <TableHead>Periode</TableHead>
                                        <TableHead>Kuota</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {promo.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="font-bold">{item.kode}</TableCell>
                                            <TableCell>{diskon(item)}</TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {item.mulai} sampai {item.berakhir}
                                            </TableCell>
                                            <TableCell>{item.kuota ?? 'Tidak terbatas'}</TableCell>
                                            <TableCell>
                                                <Badge variant={item.aktif ? 'confirmed' : 'info'}>{item.aktif ? 'Aktif' : 'Nonaktif'}</Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <ul className="divide-y divide-cream md:hidden">
                            {promo.map((item) => (
                                <li key={item.id} className="px-4 py-3.5">
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="font-bold tracking-wide">{item.kode}</span>
                                        <Badge variant={item.aktif ? 'confirmed' : 'info'}>{item.aktif ? 'Aktif' : 'Nonaktif'}</Badge>
                                    </div>
                                    <div className="mt-1 text-sm text-ink-mid">
                                        Diskon <strong>{diskon(item)}</strong>, kuota {item.kuota ?? 'tidak terbatas'}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        {item.mulai} sampai {item.berakhir}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </Card>

            <Dialog open={dialogTerbuka} onOpenChange={setDialogTerbuka}>
                <DialogContent>
                    <form onSubmit={simpan} className="grid gap-4">
                        <DialogHeader>
                            <DialogTitle>Tambah Kode Promo</DialogTitle>
                            <DialogDescription>Kode otomatis ditulis huruf besar.</DialogDescription>
                        </DialogHeader>
                        <div className="grid grid-cols-2 gap-3">
                            <Field id="promo-kode" label="Kode" error={form.errors.kode} wajib>
                                <Input
                                    id="promo-kode"
                                    value={form.data.kode}
                                    onChange={(event) => form.setData('kode', event.target.value)}
                                    placeholder="Misal SEPI20"
                                    autoCapitalize="characters"
                                    required
                                />
                            </Field>
                            <Field id="promo-tipe" label="Tipe diskon" error={form.errors.tipe_diskon}>
                                <Select value={form.data.tipe_diskon} onValueChange={(nilai) => form.setData('tipe_diskon', nilai)}>
                                    <SelectTrigger id="promo-tipe">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="persen">Persen (%)</SelectItem>
                                        <SelectItem value="nominal">Nominal (Rp)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </Field>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <Field id="promo-nilai" label={form.data.tipe_diskon === 'persen' ? 'Diskon (%)' : 'Diskon (Rp)'} error={form.errors.nilai} wajib>
                                <Input
                                    id="promo-nilai"
                                    type="number"
                                    inputMode="numeric"
                                    min={1}
                                    value={form.data.nilai}
                                    onChange={(event) => form.setData('nilai', event.target.value)}
                                    required
                                />
                            </Field>
                            <Field id="promo-kuota" label="Kuota" hint="Kosongkan kalau tidak terbatas." error={form.errors.kuota}>
                                <Input
                                    id="promo-kuota"
                                    type="number"
                                    inputMode="numeric"
                                    min={1}
                                    value={form.data.kuota}
                                    onChange={(event) => form.setData('kuota', event.target.value)}
                                    placeholder="Tidak terbatas"
                                />
                            </Field>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <Field id="promo-mulai" label="Mulai" error={form.errors.tanggal_mulai}>
                                <Input
                                    id="promo-mulai"
                                    type="date"
                                    value={form.data.tanggal_mulai}
                                    onChange={(event) => form.setData('tanggal_mulai', event.target.value)}
                                    required
                                />
                            </Field>
                            <Field id="promo-berakhir" label="Berakhir" error={form.errors.tanggal_berakhir}>
                                <Input
                                    id="promo-berakhir"
                                    type="date"
                                    value={form.data.tanggal_berakhir}
                                    onChange={(event) => form.setData('tanggal_berakhir', event.target.value)}
                                    required
                                />
                            </Field>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogTerbuka(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                Simpan Kode
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
