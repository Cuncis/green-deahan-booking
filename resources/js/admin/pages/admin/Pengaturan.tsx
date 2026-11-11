import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Field from '../../components/form/Field';
import UnggahFoto from '../../components/form/UnggahFoto';
import AdminLayout from '../../components/layout/AdminLayout';
import { Button } from '../../components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '../../components/ui/card';
import { Input } from '../../components/ui/input';

interface Cabang {
    id: number;
    nama_cabang: string;
    alamat: string | null;
    kota: string | null;
    jam_buka: string;
    jam_tutup: string;
}

interface Props {
    profil: {
        nama_bisnis: string;
        whatsapp_admin: string | null;
        email_admin: string | null;
        warna_utama: string;
        logoUrl: string | null;
    };
    multiCabang: boolean;
    cabang: Cabang[];
    simpanUrl: string;
}

type DataCabang = Record<
    string,
    {
        nama_cabang: string;
        alamat: string;
        kota: string;
        jam_buka: string;
        jam_tutup: string;
    }
>;

/**
 * Business profile, branding and opening hours. Multi-branch tenants edit
 * every branch; others edit the opening hours of their single branch.
 */
export default function Pengaturan({ profil, multiCabang, cabang, simpanUrl }: Props) {
    const cabangAwal: DataCabang = Object.fromEntries(
        cabang.map((item) => [
            String(item.id),
            {
                nama_cabang: item.nama_cabang,
                alamat: item.alamat ?? '',
                kota: item.kota ?? '',
                jam_buka: item.jam_buka,
                jam_tutup: item.jam_tutup,
            },
        ]),
    );

    const form = useForm({
        nama_bisnis: profil.nama_bisnis,
        whatsapp_admin: profil.whatsapp_admin ?? '',
        email_admin: profil.email_admin ?? '',
        warna_utama: profil.warna_utama,
        logo: null as File | null,
        jam_buka: cabang[0]?.jam_buka ?? '',
        jam_tutup: cabang[0]?.jam_tutup ?? '',
        cabang: cabangAwal,
    });
    const errors = form.errors as Record<string, string | undefined>;

    const ubahCabang = (id: string, kolom: keyof DataCabang[string], nilai: string) =>
        form.setData('cabang', {
            ...form.data.cabang,
            [id]: { ...form.data.cabang[id], [kolom]: nilai },
        });

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        // The logo upload needs multipart, so send POST with Laravel's PUT override.
        form.transform((data) => {
            const { cabang: dataCabang, jam_buka, jam_tutup, ...profilBaru } = data;

            return multiCabang ? { ...profilBaru, cabang: dataCabang, _method: 'put' } : { ...profilBaru, jam_buka, jam_tutup, _method: 'put' };
        });
        form.post(simpanUrl, { forceFormData: true, preserveScroll: true });
    };

    return (
        <AdminLayout judul="Pengaturan" deskripsi="Profil bisnis, tampilan halaman booking, dan jam operasional.">
            <Head title="Pengaturan" />

            <form onSubmit={simpan} className="space-y-5 pb-24 md:pb-0">
                <Card>
                    <CardHeader>
                        <CardTitle>Profil Bisnis</CardTitle>
                        <CardDescription>Ditampilkan ke customer di halaman booking.</CardDescription>
                    </CardHeader>
                    <div className="grid gap-4 px-5 pb-5 md:grid-cols-2">
                        <Field id="nama-bisnis" label="Nama bisnis" error={errors.nama_bisnis} wajib className="md:col-span-2">
                            <Input id="nama-bisnis" value={form.data.nama_bisnis} onChange={(event) => form.setData('nama_bisnis', event.target.value)} required />
                        </Field>
                        <Field id="wa-admin" label="WhatsApp admin" error={errors.whatsapp_admin} wajib hint="Nomor yang dihubungi customer.">
                            <Input
                                id="wa-admin"
                                type="tel"
                                inputMode="tel"
                                value={form.data.whatsapp_admin}
                                onChange={(event) => form.setData('whatsapp_admin', event.target.value)}
                                required
                            />
                        </Field>
                        <Field id="email-admin" label="Email admin" error={errors.email_admin}>
                            <Input
                                id="email-admin"
                                type="email"
                                inputMode="email"
                                value={form.data.email_admin}
                                onChange={(event) => form.setData('email_admin', event.target.value)}
                            />
                        </Field>
                    </div>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Tampilan</CardTitle>
                        <CardDescription>Logo dan warna utama halaman booking.</CardDescription>
                    </CardHeader>
                    <div className="grid grid-cols-1 gap-4 px-5 pb-5">
                        <Field id="logo" label="Logo" error={errors.logo}>
                            <UnggahFoto id="logo" fotoSekarang={profil.logoUrl} file={form.data.logo} onPilih={(file) => form.setData('logo', file)} labelTombol="Pilih logo" />
                        </Field>
                        <Field id="warna" label="Warna utama" error={errors.warna_utama}>
                            <div className="flex items-center gap-3">
                                <input
                                    id="warna"
                                    type="color"
                                    value={form.data.warna_utama}
                                    onChange={(event) => form.setData('warna_utama', event.target.value)}
                                    className="h-11 w-16 cursor-pointer rounded-lg border border-input bg-card p-1"
                                />
                                <span className="font-mono text-sm uppercase text-ink-mid">{form.data.warna_utama}</span>
                            </div>
                        </Field>
                    </div>
                </Card>

                {multiCabang ? (
                    cabang.map((item) => {
                        const id = String(item.id);
                        const data = form.data.cabang[id];
                        const galat = (kolom: string) => errors[`cabang.${id}.${kolom}`];

                        return (
                            <Card key={item.id}>
                                <CardHeader>
                                    <CardTitle>{item.nama_cabang}</CardTitle>
                                    <CardDescription>Data dan jam operasional cabang.</CardDescription>
                                </CardHeader>
                                <div className="grid gap-4 px-5 pb-5 md:grid-cols-2">
                                    <Field id={`cabang-${id}-nama`} label="Nama cabang" error={galat('nama_cabang')} wajib>
                                        <Input id={`cabang-${id}-nama`} value={data.nama_cabang} onChange={(event) => ubahCabang(id, 'nama_cabang', event.target.value)} required />
                                    </Field>
                                    <Field id={`cabang-${id}-kota`} label="Kota" error={galat('kota')} wajib>
                                        <Input id={`cabang-${id}-kota`} value={data.kota} onChange={(event) => ubahCabang(id, 'kota', event.target.value)} required />
                                    </Field>
                                    <Field id={`cabang-${id}-alamat`} label="Alamat" error={galat('alamat')} wajib className="md:col-span-2">
                                        <Input id={`cabang-${id}-alamat`} value={data.alamat} onChange={(event) => ubahCabang(id, 'alamat', event.target.value)} required />
                                    </Field>
                                    <Field id={`cabang-${id}-buka`} label="Jam buka" error={galat('jam_buka')} wajib>
                                        <Input
                                            id={`cabang-${id}-buka`}
                                            type="time"
                                            value={data.jam_buka}
                                            onChange={(event) => ubahCabang(id, 'jam_buka', event.target.value)}
                                            required
                                        />
                                    </Field>
                                    <Field id={`cabang-${id}-tutup`} label="Jam tutup" error={galat('jam_tutup')} wajib>
                                        <Input
                                            id={`cabang-${id}-tutup`}
                                            type="time"
                                            value={data.jam_tutup}
                                            onChange={(event) => ubahCabang(id, 'jam_tutup', event.target.value)}
                                            required
                                        />
                                    </Field>
                                </div>
                            </Card>
                        );
                    })
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Jam Operasional</CardTitle>
                            <CardDescription>Dipakai untuk membuat slot jadwal.</CardDescription>
                        </CardHeader>
                        <div className="grid grid-cols-2 gap-4 px-5 pb-5">
                            <Field id="jam-buka" label="Jam buka" error={errors.jam_buka} wajib>
                                <Input id="jam-buka" type="time" value={form.data.jam_buka} onChange={(event) => form.setData('jam_buka', event.target.value)} required />
                            </Field>
                            <Field id="jam-tutup" label="Jam tutup" error={errors.jam_tutup} wajib>
                                <Input id="jam-tutup" type="time" value={form.data.jam_tutup} onChange={(event) => form.setData('jam_tutup', event.target.value)} required />
                            </Field>
                        </div>
                    </Card>
                )}

                <div className="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur md:static md:border-0 md:bg-transparent md:p-0">
                    <Button type="submit" className="w-full md:w-auto" disabled={form.processing}>
                        Simpan Pengaturan
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
