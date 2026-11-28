import { Head, router, useForm } from '@inertiajs/react';
import { ExternalLink, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';
import WhatsAppIcon from '../../../../components/fuzio/elements/WhatsAppIcon';
import AksiKartu from '../../../components/AksiKartu';
import Field from '../../../components/form/Field';
import KonfirmasiDialog from '../../../components/KonfirmasiDialog';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Badge } from '../../../components/ui/badge';
import { Button } from '../../../components/ui/button';
import { Card, CardHeader, CardTitle } from '../../../components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../../components/ui/dialog';
import { Input, Textarea } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';
import { bukaChatWhatsApp } from '../../../lib/whatsapp';

interface Supplier {
    id: number;
    name: string;
    platform: string;
    shop_url: string | null;
    whatsapp: string | null;
    origin_postal_code: string | null;
    notes: string | null;
    jumlahProduk: number;
}

const LABEL_PLATFORM: Record<string, string> = { shopee: 'Shopee', tokopedia: 'Tokopedia', direct: 'Langsung' };
const KOSONG = { name: '', platform: 'shopee', shop_url: '', whatsapp: '', origin_postal_code: '', notes: '' };

const chatSupplier = (item: Supplier) => item.whatsapp && bukaChatWhatsApp(`https://wa.me/${item.whatsapp.replace(/\D/g, '').replace(/^0/, '62')}`);

/** Compact icon actions for the wide table, so the supplier name keeps the room. */
function AksiIkon({ item, onEdit, onHapus }: { item: Supplier; onEdit: () => void; onHapus: () => void }) {
    const kelas = 'size-9 md:size-9';
    const kosong = (judul: string, ikon: ReactNode) => (
        <span className={`${kelas} inline-flex items-center justify-center text-muted-foreground/40`} title={judul}>
            {ikon}
        </span>
    );
    return (
        <div className="flex items-center justify-end gap-1.5">
            {item.shop_url ? (
                <Button asChild variant="outline" size="icon" className={kelas}>
                    <a href={item.shop_url} target="_blank" rel="noopener noreferrer" title="Buka toko / lokasi" aria-label={`Buka toko ${item.name}`}>
                        <ExternalLink />
                    </a>
                </Button>
            ) : (
                kosong('Link toko belum diisi', <ExternalLink className="size-4" aria-hidden="true" />)
            )}
            {item.whatsapp ? (
                <Button type="button" variant="outline" size="icon" className={kelas} onClick={() => chatSupplier(item)} title="Chat WhatsApp" aria-label={`Chat WhatsApp ${item.name}`}>
                    <WhatsAppIcon size={16} className="text-[#25D366]" />
                </Button>
            ) : (
                kosong('Nomor WhatsApp belum diisi', <WhatsAppIcon size={16} />)
            )}
            <Button type="button" variant="outline" size="icon" className={kelas} onClick={onEdit} title="Edit supplier" aria-label={`Edit ${item.name}`}>
                <Pencil />
            </Button>
            <Button
                type="button"
                variant="outline"
                size="icon"
                className={`${kelas} text-destructive hover:bg-destructive/10 hover:text-destructive`}
                onClick={onHapus}
                title="Hapus supplier"
                aria-label={`Hapus ${item.name}`}
            >
                <Trash2 />
            </Button>
        </div>
    );
}

/** Shop suppliers: searchable table (list on smaller screens) with an add/edit dialog. */
export default function SupplierPage({ supplier, pilihanPlatform }: { supplier: Supplier[]; pilihanPlatform: string[] }) {
    const [diedit, setDiedit] = useState<Supplier | 'baru' | null>(null);
    const [akanDihapus, setAkanDihapus] = useState<Supplier | null>(null);
    const form = useForm(KOSONG);
    const [kata, setKata] = useState('');
    const cari = kata.trim().toLowerCase();
    const tampil = cari === '' ? supplier : supplier.filter((item) => [item.name, item.notes ?? '', item.origin_postal_code ?? ''].some((teks) => teks.toLowerCase().includes(cari)));

    const buka = (target: Supplier | 'baru') => {
        form.clearErrors();
        form.setData(
            target === 'baru'
                ? KOSONG
                : {
                      name: target.name,
                      platform: target.platform,
                      shop_url: target.shop_url ?? '',
                      whatsapp: target.whatsapp ?? '',
                      origin_postal_code: target.origin_postal_code ?? '',
                      notes: target.notes ?? '',
                  },
        );
        setDiedit(target);
    };

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        const opsi = { preserveScroll: true, onSuccess: () => setDiedit(null) };
        if (diedit === 'baru') {
            form.post('/superadmin/toko/supplier', opsi);
        } else if (diedit) {
            form.put(`/superadmin/toko/supplier/${diedit.id}`, opsi);
        }
    };

    return (
        <AdminLayout
            judul="Supplier"
            deskripsi="Toko tempat membeli barang untuk pesanan dropship."
            aksi={
                <Button className="w-full sm:w-auto" onClick={() => buka('baru')}>
                    <Plus />
                    Tambah Supplier
                </Button>
            }
        >
            <Head title="Supplier" />
            {supplier.length === 0 ? (
                <Card className="px-5 py-8 text-center text-sm text-muted-foreground">Belum ada supplier.</Card>
            ) : (
                <>
                    <div className="relative mb-4 sm:max-w-xs">
                        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                        <Input value={kata} onChange={(event) => setKata(event.target.value)} placeholder="Cari nama, area, atau kode pos" className="pl-9" aria-label="Cari supplier" />
                    </div>
                    <Card className="overflow-hidden">
                        <CardHeader className="border-b border-border py-4">
                            <CardTitle className="text-xs uppercase tracking-wide text-green">
                                {tampil.length === supplier.length ? `${supplier.length} Supplier` : `${tampil.length} dari ${supplier.length} Supplier`}
                            </CardTitle>
                        </CardHeader>
                        {tampil.length === 0 ? (
                            <p className="px-5 py-6 text-center text-sm text-muted-foreground">Tidak ada supplier yang cocok dengan "{kata}".</p>
                        ) : (
                            <>
                                <div className="hidden xl:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Supplier</TableHead>
                                                <TableHead className="w-44">Kirim dari</TableHead>
                                                <TableHead className="w-24 text-right">Produk</TableHead>
                                                <TableHead className="w-44 text-right">Aksi</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {tampil.map((item) => (
                                                <TableRow key={item.id}>
                                                    <TableCell className="max-w-0 py-3.5">
                                                        <div className="flex items-center gap-2">
                                                            <span className="truncate font-bold" title={item.name}>
                                                                {item.name}
                                                            </span>
                                                            <Badge variant="info" className="shrink-0">
                                                                {LABEL_PLATFORM[item.platform] ?? item.platform}
                                                            </Badge>
                                                        </div>
                                                        {item.notes && (
                                                            <div className="mt-0.5 truncate text-xs text-muted-foreground" title={item.notes}>
                                                                {item.notes.replace(/\n+/g, ' · ')}
                                                            </div>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="whitespace-nowrap py-3.5 tabular-nums text-ink-mid">
                                                        {item.origin_postal_code ? `Kode pos ${item.origin_postal_code}` : <span className="text-muted-foreground">-</span>}
                                                    </TableCell>
                                                    <TableCell className="whitespace-nowrap py-3.5 text-right tabular-nums">{item.jumlahProduk}</TableCell>
                                                    <TableCell className="py-3.5">
                                                        <AksiIkon item={item} onEdit={() => buka(item)} onHapus={() => setAkanDihapus(item)} />
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                                <ul className="divide-y divide-cream xl:hidden">
                                    {tampil.map((item) => (
                                        <li key={item.id} className="px-4 py-4">
                                            <div className="flex items-start justify-between gap-3">
                                                <span className="min-w-0 font-bold leading-snug">{item.name}</span>
                                                <Badge variant="info" className="shrink-0">
                                                    {LABEL_PLATFORM[item.platform] ?? item.platform}
                                                </Badge>
                                            </div>
                                            <div className="mt-0.5 text-xs text-muted-foreground">
                                                {item.jumlahProduk} produk{item.origin_postal_code ? ` · kode pos ${item.origin_postal_code}` : ''}
                                            </div>
                                            {item.notes && <p className="mt-1 line-clamp-2 whitespace-pre-line text-sm text-ink-mid">{item.notes}</p>}
                                            <AksiKartu
                                                className="mt-3"
                                                kiri={
                                                    <>
                                                        {item.shop_url && (
                                                            <Button asChild variant="outline" size="sm">
                                                                <a href={item.shop_url} target="_blank" rel="noopener noreferrer" aria-label={`Buka toko ${item.name}`}>
                                                                    <ExternalLink />
                                                                    <span className="hidden sm:inline">Toko</span>
                                                                </a>
                                                            </Button>
                                                        )}
                                                        {item.whatsapp && (
                                                            <Button variant="outline" size="sm" onClick={() => chatSupplier(item)} aria-label={`Chat WhatsApp ${item.name}`}>
                                                                <WhatsAppIcon size={16} className="text-[#25D366]" />
                                                                <span className="hidden sm:inline">Chat</span>
                                                            </Button>
                                                        )}
                                                    </>
                                                }
                                                onEdit={() => buka(item)}
                                                onHapus={() => setAkanDihapus(item)}
                                            />
                                        </li>
                                    ))}
                                </ul>
                            </>
                        )}
                    </Card>
                </>
            )}

            <Dialog open={diedit !== null} onOpenChange={(buka) => !buka && setDiedit(null)}>
                <DialogContent>
                    <form onSubmit={simpan} className="grid gap-4">
                        <DialogHeader>
                            <DialogTitle>{diedit === 'baru' ? 'Tambah Supplier' : 'Edit Supplier'}</DialogTitle>
                            <DialogDescription>Hanya terlihat oleh admin.</DialogDescription>
                        </DialogHeader>
                        <Field id="sup-nama" label="Nama" error={form.errors.name} wajib>
                            <Input id="sup-nama" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} required />
                        </Field>
                        <div className="grid grid-cols-2 gap-3">
                            <Field id="sup-platform" label="Platform" error={form.errors.platform}>
                                <Select value={form.data.platform} onValueChange={(nilai) => form.setData('platform', nilai)}>
                                    <SelectTrigger id="sup-platform">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {pilihanPlatform.map((nilai) => (
                                            <SelectItem key={nilai} value={nilai}>
                                                {LABEL_PLATFORM[nilai] ?? nilai}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                            <Field id="sup-wa" label="WhatsApp" error={form.errors.whatsapp}>
                                <Input id="sup-wa" type="tel" inputMode="tel" value={form.data.whatsapp} onChange={(event) => form.setData('whatsapp', event.target.value)} />
                            </Field>
                        </div>
                        <Field
                            id="sup-pos"
                            label="Kode pos asal pengiriman"
                            error={form.errors.origin_postal_code}
                            hint="Kode pos gudang atau toko supplier. Dipakai Biteship untuk menghitung ongkir."
                        >
                            <Input
                                id="sup-pos"
                                inputMode="numeric"
                                maxLength={5}
                                value={form.data.origin_postal_code}
                                onChange={(event) => form.setData('origin_postal_code', event.target.value.replace(/\D/g, ''))}
                                placeholder="13330"
                            />
                        </Field>
                        <Field id="sup-url" label="Link toko" error={form.errors.shop_url}>
                            <Input
                                id="sup-url"
                                type="url"
                                inputMode="url"
                                value={form.data.shop_url}
                                onChange={(event) => form.setData('shop_url', event.target.value)}
                                placeholder="https://"
                            />
                        </Field>
                        <Field id="sup-catatan" label="Catatan" error={form.errors.notes}>
                            <Textarea id="sup-catatan" rows={3} value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
                        </Field>
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
                onLanjut={() => akanDihapus && router.delete(`/superadmin/toko/supplier/${akanDihapus.id}`, { preserveScroll: true, onFinish: () => setAkanDihapus(null) })}
                judul="Hapus supplier?"
                deskripsi={`"${akanDihapus?.name ?? ''}" dihapus. Produknya tetap ada, hanya tanpa supplier.`}
                labelLanjut="Ya, Hapus"
            />
        </AdminLayout>
    );
}
