import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Field from '../../../components/form/Field';
import UnggahFoto from '../../../components/form/UnggahFoto';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Button } from '../../../components/ui/button';
import { Card } from '../../../components/ui/card';
import { Input, Textarea } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';

interface ItemEdit {
    judul: string;
    kategori: string;
    kota: string;
    material: string;
    deskripsi: string;
    urutan: number;
    tampilan_besar: boolean;
    status_aktif: boolean;
    fotoUrl: string | null;
    updateUrl: string;
}

interface Props {
    item: ItemEdit | null;
    pilihanKategori: { nilai: string; label: string }[];
    simpanUrl: string;
    kembaliUrl: string;
}

/** Add and edit a gallery item (same form). Sticky save bar on phones. */
export default function Form({ item, pilihanKategori, simpanUrl, kembaliUrl }: Props) {
    const edit = item !== null;
    const form = useForm({
        judul: item?.judul ?? '',
        kategori: item?.kategori ?? pilihanKategori[0]?.nilai ?? '',
        kota: item?.kota ?? '',
        material: item?.material ?? '',
        deskripsi: item?.deskripsi ?? '',
        urutan: String(item?.urutan ?? 0),
        tampilan_besar: item?.tampilan_besar ?? false,
        status_aktif: item ? (item.status_aktif ? '1' : '0') : '1',
        foto: null as File | null,
    });

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        // Booleans go as 1/0 because the photo upload forces multipart form data.
        form.transform((data) => ({ ...data, tampilan_besar: data.tampilan_besar ? '1' : '0', ...(edit ? { _method: 'put' } : {}) }));
        form.post(edit ? item.updateUrl : simpanUrl, { forceFormData: true });
    };

    return (
        <AdminLayout judul={edit ? 'Edit Item Galeri' : 'Tambah Item Galeri'} deskripsi={edit ? item.judul : 'Foto proyek baru untuk halaman /galeri.'}>
            <Head title={edit ? 'Edit Item Galeri' : 'Tambah Item Galeri'} />

            <form onSubmit={simpan} className="max-w-3xl pb-24 md:pb-0">
                <Card className="grid grid-cols-1 gap-4 p-4 md:grid-cols-2 md:p-6">
                    <Field id="judul" label="Judul" error={form.errors.judul} wajib className="md:col-span-2">
                        <Input id="judul" value={form.data.judul} onChange={(event) => form.setData('judul', event.target.value)} required />
                    </Field>
                    <Field id="kategori" label="Kategori" error={form.errors.kategori} wajib>
                        <Select value={form.data.kategori} onValueChange={(nilai) => form.setData('kategori', nilai)}>
                            <SelectTrigger id="kategori">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {pilihanKategori.map((kategori) => (
                                    <SelectItem key={kategori.nilai} value={kategori.nilai}>
                                        {kategori.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field id="kota" label="Kota" error={form.errors.kota} wajib>
                        <Input id="kota" value={form.data.kota} onChange={(event) => form.setData('kota', event.target.value)} required />
                    </Field>
                    <Field id="material" label="Material" error={form.errors.material} wajib>
                        <Input
                            id="material"
                            value={form.data.material}
                            onChange={(event) => form.setData('material', event.target.value)}
                            placeholder="Misal Rumput sintetis 50mm"
                            required
                        />
                    </Field>
                    <Field id="urutan" label="Urutan tampil" error={form.errors.urutan} hint="Angka lebih kecil tampil lebih dulu.">
                        <Input id="urutan" type="number" inputMode="numeric" min={0} value={form.data.urutan} onChange={(event) => form.setData('urutan', event.target.value)} />
                    </Field>
                    <Field id="deskripsi" label="Deskripsi" error={form.errors.deskripsi} wajib className="md:col-span-2">
                        <Textarea id="deskripsi" rows={3} value={form.data.deskripsi} onChange={(event) => form.setData('deskripsi', event.target.value)} required />
                    </Field>
                    <Field id="foto" label="Foto" error={form.errors.foto} wajib={!edit} className="md:col-span-2">
                        <UnggahFoto
                            id="foto"
                            fotoSekarang={item?.fotoUrl ?? null}
                            file={form.data.foto}
                            onPilih={(file) => form.setData('foto', file)}
                            labelTombol={edit ? 'Ganti foto' : 'Pilih foto'}
                        />
                    </Field>
                    <label className="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-border px-3 text-sm md:col-span-2">
                        <input
                            type="checkbox"
                            checked={form.data.tampilan_besar}
                            onChange={(event) => form.setData('tampilan_besar', event.target.checked)}
                            className="size-5 rounded border-input accent-[#3A6B4A]"
                        />
                        Tampilkan besar di grid (2 baris)
                    </label>
                    {edit && (
                        <Field id="status" label="Status" error={form.errors.status_aktif}>
                            <Select value={form.data.status_aktif} onValueChange={(nilai) => form.setData('status_aktif', nilai)}>
                                <SelectTrigger id="status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="1">Tampil</SelectItem>
                                    <SelectItem value="0">Disembunyikan</SelectItem>
                                </SelectContent>
                            </Select>
                        </Field>
                    )}
                </Card>

                <div className="fixed inset-x-0 bottom-0 z-20 flex gap-2 border-t border-border bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur md:static md:mt-5 md:border-0 md:bg-transparent md:p-0">
                    <Button asChild variant="outline" className="md:order-2">
                        <Link href={kembaliUrl}>Batal</Link>
                    </Button>
                    <Button type="submit" className="flex-1 md:flex-none" disabled={form.processing}>
                        {edit ? 'Simpan Perubahan' : 'Tambah ke Galeri'}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
