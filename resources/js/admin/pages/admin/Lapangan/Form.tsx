import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Field from '../../../components/form/Field';
import UnggahFoto from '../../../components/form/UnggahFoto';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Button } from '../../../components/ui/button';
import { Card } from '../../../components/ui/card';
import { Input, Textarea } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';

interface LapanganEdit {
    id: number;
    nama: string;
    jenis_olahraga: string;
    harga_per_jam: number;
    harga_jam_sibuk: number | null;
    deskripsi: string | null;
    cabang_id: number;
    status_aktif: boolean;
    fotoUrl: string | null;
    updateUrl: string;
}

interface Props {
    lapangan: LapanganEdit | null;
    jenisOlahraga: string[];
    daftarCabang: { id: number; nama: string }[];
    simpanUrl: string;
    kembaliUrl: string;
}

const kapital = (teks: string) => teks.charAt(0).toUpperCase() + teks.slice(1);

/** Add and edit court (same form). Sticky save bar on phones. */
export default function Form({ lapangan, jenisOlahraga, daftarCabang, simpanUrl, kembaliUrl }: Props) {
    const edit = lapangan !== null;
    const form = useForm({
        nama: lapangan?.nama ?? '',
        jenis_olahraga: lapangan?.jenis_olahraga ?? jenisOlahraga[0] ?? '',
        harga_per_jam: lapangan ? String(lapangan.harga_per_jam) : '',
        harga_jam_sibuk: lapangan?.harga_jam_sibuk != null ? String(lapangan.harga_jam_sibuk) : '',
        deskripsi: lapangan?.deskripsi ?? '',
        cabang_id: lapangan ? String(lapangan.cabang_id) : String(daftarCabang[0]?.id ?? ''),
        status_aktif: lapangan ? (lapangan.status_aktif ? '1' : '0') : '1',
        foto: null as File | null,
    });

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        if (edit) {
            // A file upload cannot go as PUT, so send POST with Laravel's method override.
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(lapangan.updateUrl, { forceFormData: true });
        } else {
            form.post(simpanUrl, { forceFormData: true });
        }
    };

    return (
        <AdminLayout judul={edit ? 'Edit Lapangan' : 'Tambah Lapangan'} deskripsi={edit ? lapangan.nama : 'Lengkapi data lapangan baru.'}>
            <Head title={edit ? 'Edit Lapangan' : 'Tambah Lapangan'} />

            <form onSubmit={simpan} className="pb-24 md:pb-0">
                <Card className="grid gap-4 p-4 md:grid-cols-2 md:p-6">
                    <Field id="nama" label="Nama lapangan" error={form.errors.nama} wajib className="md:col-span-2">
                        <Input id="nama" value={form.data.nama} onChange={(event) => form.setData('nama', event.target.value)} required />
                    </Field>
                    <Field id="jenis" label="Jenis olahraga" error={form.errors.jenis_olahraga} wajib>
                        <Select value={form.data.jenis_olahraga} onValueChange={(nilai) => form.setData('jenis_olahraga', nilai)}>
                            <SelectTrigger id="jenis">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {jenisOlahraga.map((jenis) => (
                                    <SelectItem key={jenis} value={jenis}>
                                        {kapital(jenis)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    {daftarCabang.length > 0 && (
                        <Field id="cabang" label="Cabang" error={form.errors.cabang_id} wajib>
                            <Select value={form.data.cabang_id} onValueChange={(nilai) => form.setData('cabang_id', nilai)}>
                                <SelectTrigger id="cabang">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {daftarCabang.map((cabang) => (
                                        <SelectItem key={cabang.id} value={String(cabang.id)}>
                                            {cabang.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                    )}
                    <Field id="harga" label="Harga normal per jam (Rp)" error={form.errors.harga_per_jam} wajib>
                        <Input
                            id="harga"
                            type="number"
                            inputMode="numeric"
                            min={0}
                            value={form.data.harga_per_jam}
                            onChange={(event) => form.setData('harga_per_jam', event.target.value)}
                            required
                        />
                    </Field>
                    <Field id="harga-sibuk" label="Harga jam sibuk (Rp)" hint="Opsional, untuk jam ramai." error={form.errors.harga_jam_sibuk}>
                        <Input
                            id="harga-sibuk"
                            type="number"
                            inputMode="numeric"
                            min={0}
                            value={form.data.harga_jam_sibuk}
                            onChange={(event) => form.setData('harga_jam_sibuk', event.target.value)}
                        />
                    </Field>
                    {edit && (
                        <Field id="status" label="Status" error={form.errors.status_aktif}>
                            <Select value={form.data.status_aktif} onValueChange={(nilai) => form.setData('status_aktif', nilai)}>
                                <SelectTrigger id="status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="1">Aktif</SelectItem>
                                    <SelectItem value="0">Nonaktif</SelectItem>
                                </SelectContent>
                            </Select>
                        </Field>
                    )}
                    <Field id="deskripsi" label="Deskripsi" error={form.errors.deskripsi} className="md:col-span-2">
                        <Textarea id="deskripsi" rows={4} value={form.data.deskripsi} onChange={(event) => form.setData('deskripsi', event.target.value)} />
                    </Field>
                    <Field id="foto" label="Foto lapangan" error={form.errors.foto} className="md:col-span-2">
                        <UnggahFoto id="foto" fotoSekarang={lapangan?.fotoUrl ?? null} file={form.data.foto} onPilih={(file) => form.setData('foto', file)} />
                    </Field>
                </Card>

                <div className="fixed inset-x-0 bottom-0 z-20 flex gap-2 border-t border-border bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur md:static md:mt-5 md:border-0 md:bg-transparent md:p-0">
                    <Button asChild variant="outline" className="flex-1 md:flex-none">
                        <Link href={kembaliUrl}>Batal</Link>
                    </Button>
                    <Button type="submit" className="flex-1 md:flex-none" disabled={form.processing}>
                        {edit ? 'Simpan Perubahan' : 'Simpan Lapangan'}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
