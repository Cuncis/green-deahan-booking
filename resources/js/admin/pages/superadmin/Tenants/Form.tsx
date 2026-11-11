import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Field from '../../../components/form/Field';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Button } from '../../../components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input } from '../../../components/ui/input';
import { cn } from '../../../lib/utils';

interface PilihanPaket {
    nilai: string;
    nama: string;
    harga: string;
    deskripsi: string;
}

interface Props {
    pilihanPaket: PilihanPaket[];
    simpanUrl: string;
    kembaliUrl: string;
}

/** New tenant: business info + PIC. On success the server opens the tenant detail page with the invitation link. */
export default function Form({ pilihanPaket, simpanUrl, kembaliUrl }: Props) {
    const form = useForm({
        nama_bisnis: '',
        subdomain: '',
        paket: 'pro',
        kota: '',
        nama_pic: '',
        email_pic: '',
        whatsapp_pic: '',
    });

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        form.post(simpanUrl, { preserveScroll: true });
    };

    return (
        <AdminLayout judul="Tambah Tenant Baru" deskripsi="Isi data bisnis dan PIC klien, lalu buat link undangan owner.">
            <Head title="Tambah Tenant" />

            <form onSubmit={simpan} className="max-w-3xl space-y-5 pb-24 md:pb-0">
                <Card>
                    <CardHeader>
                        <CardTitle>Info Bisnis</CardTitle>
                        <CardDescription>Nama dan alamat halaman booking tenant.</CardDescription>
                    </CardHeader>
                    <div className="grid grid-cols-1 gap-4 px-5 pb-5">
                        <Field id="nama-bisnis" label="Nama bisnis" error={form.errors.nama_bisnis} wajib>
                            <Input
                                id="nama-bisnis"
                                value={form.data.nama_bisnis}
                                onChange={(event) => form.setData('nama_bisnis', event.target.value)}
                                placeholder="Misal Arena Sport Center"
                                required
                            />
                        </Field>

                        <Field
                            id="subdomain"
                            label="Subdomain"
                            error={form.errors.subdomain}
                            wajib
                            hint="Huruf kecil, angka, dan tanda hubung saja. Otomatis dirapikan saat mengetik."
                        >
                            <div className="flex overflow-hidden rounded-lg border border-input bg-card focus-within:border-primary focus-within:ring-1 focus-within:ring-primary">
                                <input
                                    id="subdomain"
                                    value={form.data.subdomain}
                                    onChange={(event) => form.setData('subdomain', event.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
                                    placeholder="arena-sport"
                                    autoCapitalize="none"
                                    autoCorrect="off"
                                    spellCheck={false}
                                    required
                                    className="h-11 min-w-0 flex-1 bg-transparent px-3 text-base outline-none placeholder:text-muted-foreground md:text-sm"
                                />
                                <span className="flex items-center whitespace-nowrap border-l border-input bg-muted px-3 text-sm text-muted-foreground">.greendeahan.com</span>
                            </div>
                        </Field>

                        <fieldset className="space-y-1.5">
                            <legend className="mb-1.5 text-sm font-medium">
                                Paket <span className="text-destructive">*</span>
                            </legend>
                            <div className="grid gap-3 sm:grid-cols-3" role="radiogroup">
                                {pilihanPaket.map((paket) => {
                                    const dipilih = form.data.paket === paket.nilai;

                                    return (
                                        <label
                                            key={paket.nilai}
                                            className={cn(
                                                'cursor-pointer rounded-xl border-2 p-4 transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring',
                                                dipilih ? 'border-primary bg-green-pale' : 'border-border bg-card hover:border-sand',
                                            )}
                                        >
                                            <input
                                                type="radio"
                                                name="paket"
                                                value={paket.nilai}
                                                checked={dipilih}
                                                onChange={() => form.setData('paket', paket.nilai)}
                                                className="sr-only"
                                            />
                                            <div className="font-display font-semibold">{paket.nama}</div>
                                            <div className="mb-1 text-lg font-bold text-green">
                                                {paket.harga}
                                                <span className="text-xs font-normal text-muted-foreground">/bulan</span>
                                            </div>
                                            <p className="text-xs text-muted-foreground">{paket.deskripsi}</p>
                                        </label>
                                    );
                                })}
                            </div>
                            {form.errors.paket && <p className="text-sm text-destructive">{form.errors.paket}</p>}
                        </fieldset>

                        <Field id="kota" label="Kota" error={form.errors.kota} wajib hint="Dipakai untuk cabang default (Cabang Utama) tenant ini.">
                            <Input id="kota" value={form.data.kota} onChange={(event) => form.setData('kota', event.target.value)} placeholder="Misal Jakarta" required />
                        </Field>
                    </div>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Info PIC</CardTitle>
                        <CardDescription>Pemilik bisnis yang akan jadi owner akun tenant.</CardDescription>
                    </CardHeader>
                    <div className="grid gap-4 px-5 pb-5 md:grid-cols-2">
                        <Field id="nama-pic" label="Nama PIC" error={form.errors.nama_pic} wajib className="md:col-span-2">
                            <Input
                                id="nama-pic"
                                value={form.data.nama_pic}
                                onChange={(event) => form.setData('nama_pic', event.target.value)}
                                placeholder="Nama pemilik bisnis"
                                required
                            />
                        </Field>
                        <Field id="email-pic" label="Email PIC" error={form.errors.email_pic} wajib hint="Link undangan owner dikirim ke email ini.">
                            <Input
                                id="email-pic"
                                type="email"
                                inputMode="email"
                                autoCapitalize="none"
                                value={form.data.email_pic}
                                onChange={(event) => form.setData('email_pic', event.target.value)}
                                placeholder="pemilik@bisnis.com"
                                required
                            />
                        </Field>
                        <Field id="wa-pic" label="WhatsApp PIC" error={form.errors.whatsapp_pic} wajib>
                            <Input
                                id="wa-pic"
                                type="tel"
                                inputMode="tel"
                                value={form.data.whatsapp_pic}
                                onChange={(event) => form.setData('whatsapp_pic', event.target.value)}
                                placeholder="08xxxxxxxxxx"
                                required
                            />
                        </Field>
                    </div>
                </Card>

                <div className="fixed inset-x-0 bottom-0 z-20 flex gap-2 border-t border-border bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur md:static md:border-0 md:bg-transparent md:p-0">
                    <Button asChild variant="outline" className="md:order-2">
                        <Link href={kembaliUrl}>Batal</Link>
                    </Button>
                    <Button type="submit" className="flex-1 md:flex-none" disabled={form.processing}>
                        Buat Tenant dan Link Undangan
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
