import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Field from '../../../../components/form/Field';
import UnggahFoto from '../../../../components/form/UnggahFoto';
import AdminLayout from '../../../../components/layout/AdminLayout';
import { Button } from '../../../../components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '../../../../components/ui/card';
import { Input, Textarea } from '../../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../../components/ui/select';
import { rupiah } from '../../../../lib/format';

interface ProdukEdit {
    name: string;
    slug: string;
    category: string;
    unit: string;
    badge: string | null;
    description: string | null;
    sell_price: number;
    compare_price: number | null;
    cost_price: number;
    supplier_id: number | null;
    supplier_product_url: string | null;
    weight_gram: number;
    is_active: boolean;
    imageUrl: string | null;
    updateUrl: string;
}

interface Props {
    produk: ProdukEdit | null;
    pilihanKategori: { nilai: string; label: string }[];
    pilihanSatuan: string[];
    pilihanSupplier: { id: number; name: string }[];
    simpanUrl: string;
    kembaliUrl: string;
}

const TANPA_SUPPLIER = 'tanpa';

/** Add and edit a shop product (same form). */
export default function Form({ produk, pilihanKategori, pilihanSatuan, pilihanSupplier, simpanUrl, kembaliUrl }: Props) {
    const edit = produk !== null;
    const form = useForm({
        name: produk?.name ?? '',
        slug: produk?.slug ?? '',
        category: produk?.category ?? pilihanKategori[0]?.nilai ?? '',
        unit: produk?.unit ?? 'pcs',
        badge: produk?.badge ?? '',
        description: produk?.description ?? '',
        sell_price: produk ? String(produk.sell_price) : '',
        compare_price: produk?.compare_price ? String(produk.compare_price) : '',
        cost_price: produk ? String(produk.cost_price) : '',
        supplier_id: produk?.supplier_id ? String(produk.supplier_id) : TANPA_SUPPLIER,
        supplier_product_url: produk?.supplier_product_url ?? '',
        weight_gram: produk ? String(produk.weight_gram) : '0',
        is_active: produk ? (produk.is_active ? '1' : '0') : '1',
        foto: null as File | null,
    });

    const jual = Number(form.data.sell_price) || 0;
    const modal = Number(form.data.cost_price) || 0;

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, supplier_id: data.supplier_id === TANPA_SUPPLIER ? '' : data.supplier_id, ...(edit ? { _method: 'put' } : {}) }));
        form.post(edit ? produk.updateUrl : simpanUrl, { forceFormData: true });
    };

    return (
        <AdminLayout judul={edit ? 'Edit Produk' : 'Tambah Produk'} deskripsi={edit ? produk.name : 'Produk baru untuk halaman /toko.'}>
            <Head title={edit ? 'Edit Produk' : 'Tambah Produk'} />
            <form onSubmit={simpan} className="grid gap-5 pb-24 md:pb-0 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
                <Card className="grid grid-cols-1 gap-4 p-4 md:grid-cols-2 md:p-6">
                    <Field id="name" label="Nama produk" error={form.errors.name} wajib className="md:col-span-2">
                        <Input id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} required />
                    </Field>
                    <Field id="category" label="Kategori" error={form.errors.category} wajib>
                        <Select value={form.data.category} onValueChange={(nilai) => form.setData('category', nilai)}>
                            <SelectTrigger id="category">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {pilihanKategori.map((item) => (
                                    <SelectItem key={item.nilai} value={item.nilai}>
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field id="unit" label="Satuan jual" error={form.errors.unit} wajib hint="Harga jual dihitung per satuan ini.">
                        <Select value={form.data.unit} onValueChange={(nilai) => form.setData('unit', nilai)}>
                            <SelectTrigger id="unit">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {pilihanSatuan.map((satuan) => (
                                    <SelectItem key={satuan} value={satuan}>
                                        per {satuan}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field id="badge" label="Badge" error={form.errors.badge} hint="Opsional, misal Terlaris, Baru, Promo.">
                        <Input id="badge" value={form.data.badge} maxLength={30} onChange={(event) => form.setData('badge', event.target.value)} />
                    </Field>
                    <Field id="weight" label="Berat (gram)" error={form.errors.weight_gram}>
                        <Input
                            id="weight"
                            type="number"
                            inputMode="numeric"
                            min={0}
                            value={form.data.weight_gram}
                            onChange={(event) => form.setData('weight_gram', event.target.value)}
                        />
                    </Field>
                    <Field id="description" label="Deskripsi" error={form.errors.description} className="md:col-span-2">
                        <Textarea id="description" rows={4} value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} />
                    </Field>
                    <Field id="slug" label="Alamat halaman (slug)" error={form.errors.slug} hint="Kosongkan untuk dibuat otomatis dari nama." className="md:col-span-2">
                        <Input
                            id="slug"
                            value={form.data.slug}
                            onChange={(event) => form.setData('slug', event.target.value)}
                            placeholder="bola-padel-isi-3"
                            autoCapitalize="none"
                        />
                    </Field>
                    <Field id="foto" label="Foto" error={form.errors.foto} hint="Tanpa foto, halaman toko memakai gambar placeholder." className="md:col-span-2">
                        <UnggahFoto
                            id="foto"
                            fotoSekarang={produk?.imageUrl ?? null}
                            file={form.data.foto}
                            onPilih={(file) => form.setData('foto', file)}
                            labelTombol={edit ? 'Ganti foto' : 'Pilih foto'}
                        />
                    </Field>
                </Card>

                <div className="space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Harga</CardTitle>
                            <CardDescription>Harga modal tidak pernah tampil ke pelanggan.</CardDescription>
                        </CardHeader>
                        <div className="grid grid-cols-1 gap-4 px-5 pb-5">
                            <Field id="sell" label="Harga jual (Rp)" error={form.errors.sell_price} wajib>
                                <Input
                                    id="sell"
                                    type="number"
                                    inputMode="numeric"
                                    min={0}
                                    value={form.data.sell_price}
                                    onChange={(event) => form.setData('sell_price', event.target.value)}
                                    required
                                />
                            </Field>
                            <Field
                                id="compare"
                                label="Harga coret (Rp)"
                                error={form.errors.compare_price}
                                hint="Opsional. Harga lama yang dicoret, harus lebih mahal dari harga jual."
                            >
                                <Input
                                    id="compare"
                                    type="number"
                                    inputMode="numeric"
                                    min={0}
                                    value={form.data.compare_price}
                                    onChange={(event) => form.setData('compare_price', event.target.value)}
                                />
                            </Field>
                            <Field id="cost" label="Harga modal (Rp)" error={form.errors.cost_price} wajib>
                                <Input
                                    id="cost"
                                    type="number"
                                    inputMode="numeric"
                                    min={0}
                                    value={form.data.cost_price}
                                    onChange={(event) => form.setData('cost_price', event.target.value)}
                                    required
                                />
                            </Field>
                            <div className="rounded-lg bg-muted px-3 py-2 text-sm">
                                Untung per barang: <strong className={jual - modal < 0 ? 'text-destructive' : 'text-green'}>{rupiah(jual - modal)}</strong>
                            </div>
                        </div>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Supplier</CardTitle>
                        </CardHeader>
                        <div className="grid grid-cols-1 gap-4 px-5 pb-5">
                            <Field id="supplier" label="Supplier" error={form.errors.supplier_id}>
                                <Select value={form.data.supplier_id} onValueChange={(nilai) => form.setData('supplier_id', nilai)}>
                                    <SelectTrigger id="supplier">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TANPA_SUPPLIER}>Belum ada</SelectItem>
                                        {pilihanSupplier.map((item) => (
                                            <SelectItem key={item.id} value={String(item.id)}>
                                                {item.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                            <Field id="supplier-url" label="Link produk di supplier" error={form.errors.supplier_product_url}>
                                <Input
                                    id="supplier-url"
                                    type="url"
                                    inputMode="url"
                                    value={form.data.supplier_product_url}
                                    onChange={(event) => form.setData('supplier_product_url', event.target.value)}
                                    placeholder="https://shopee.co.id/..."
                                />
                            </Field>
                            <Field id="aktif" label="Status" error={form.errors.is_active}>
                                <Select value={form.data.is_active} onValueChange={(nilai) => form.setData('is_active', nilai)}>
                                    <SelectTrigger id="aktif">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Dijual</SelectItem>
                                        <SelectItem value="0">Disembunyikan</SelectItem>
                                    </SelectContent>
                                </Select>
                            </Field>
                        </div>
                    </Card>
                    <div className="fixed inset-x-0 bottom-0 z-20 flex gap-2 border-t border-border bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur md:static md:border-0 md:bg-transparent md:p-0">
                        <Button asChild variant="outline">
                            <Link href={kembaliUrl}>Batal</Link>
                        </Button>
                        <Button type="submit" className="flex-1" disabled={form.processing}>
                            {edit ? 'Simpan Perubahan' : 'Tambah Produk'}
                        </Button>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
