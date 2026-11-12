import { Head, router, useForm } from '@inertiajs/react';
import { ExternalLink, MessageCircle, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import Field from '../../../components/form/Field';
import KonfirmasiDialog from '../../../components/KonfirmasiDialog';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Button } from '../../../components/ui/button';
import { Card } from '../../../components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../../components/ui/dialog';
import { Input, Textarea } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { bukaChatWhatsApp } from '../../../lib/whatsapp';

interface Supplier {
    id: number;
    name: string;
    platform: string;
    shop_url: string | null;
    whatsapp: string | null;
    notes: string | null;
    jumlahProduk: number;
}

const LABEL_PLATFORM: Record<string, string> = { shopee: 'Shopee', tokopedia: 'Tokopedia', direct: 'Langsung' };
const KOSONG = { name: '', platform: 'shopee', shop_url: '', whatsapp: '', notes: '' };

/** Shop suppliers: simple list with an add/edit dialog. */
export default function SupplierPage({ supplier, pilihanPlatform }: { supplier: Supplier[]; pilihanPlatform: string[] }) {
    const [diedit, setDiedit] = useState<Supplier | 'baru' | null>(null);
    const [akanDihapus, setAkanDihapus] = useState<Supplier | null>(null);
    const form = useForm(KOSONG);

    const buka = (target: Supplier | 'baru') => {
        form.clearErrors();
        form.setData(target === 'baru' ? KOSONG : { name: target.name, platform: target.platform, shop_url: target.shop_url ?? '', whatsapp: target.whatsapp ?? '', notes: target.notes ?? '' });
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
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {supplier.map((item) => (
                        <Card key={item.id} className="flex flex-col p-5">
                            <div className="mb-1 flex items-start justify-between gap-2">
                                <span className="font-display text-base font-semibold">{item.name}</span>
                                <span className="rounded-full bg-muted px-2.5 py-0.5 text-xs font-semibold">{LABEL_PLATFORM[item.platform] ?? item.platform}</span>
                            </div>
                            <p className="mb-3 text-xs text-muted-foreground">{item.jumlahProduk} produk</p>
                            {item.notes && <p className="mb-3 text-sm text-ink-mid">{item.notes}</p>}
                            <div className="mt-auto flex flex-wrap gap-2">
                                {item.shop_url && (
                                    <Button asChild variant="outline" size="sm">
                                        <a href={item.shop_url} target="_blank" rel="noopener noreferrer">
                                            <ExternalLink />
                                            Toko
                                        </a>
                                    </Button>
                                )}
                                {item.whatsapp && (
                                    <Button variant="outline" size="sm" onClick={() => bukaChatWhatsApp(`https://wa.me/${item.whatsapp!.replace(/\D/g, '').replace(/^0/, '62')}`)}>
                                        <MessageCircle />
                                        Chat
                                    </Button>
                                )}
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
                    ))}
                </div>
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
                        <Field id="sup-url" label="Link toko" error={form.errors.shop_url}>
                            <Input id="sup-url" type="url" inputMode="url" value={form.data.shop_url} onChange={(event) => form.setData('shop_url', event.target.value)} placeholder="https://" />
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
