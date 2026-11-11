import { Head, Link, useForm } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import Field from '../../../components/form/Field';
import UnggahFoto from '../../../components/form/UnggahFoto';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Button } from '../../../components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input, Textarea } from '../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';
import { cn } from '../../../lib/utils';

interface ArtikelEdit {
    judul: string;
    kategori: string;
    ringkasan: string;
    konten: string;
    tanggal_terbit: string;
    status_aktif: boolean;
    slug: string;
    fotoUrl: string | null;
    publikUrl: string;
    updateUrl: string;
}

interface Props {
    artikel: ArtikelEdit | null;
    kategoriDipakai: string[];
    tanggalHariIni: string;
    simpanUrl: string;
    kembaliUrl: string;
}

const BATAS_RINGKASAN = 500;

/**
 * Write and edit an article (same form). The body is basic HTML, with a
 * preview tab so the author can check the formatting before saving.
 */
export default function Form({ artikel, kategoriDipakai, tanggalHariIni, simpanUrl, kembaliUrl }: Props) {
    const edit = artikel !== null;
    const [pratinjau, setPratinjau] = useState(false);
    const form = useForm({
        judul: artikel?.judul ?? '',
        kategori: artikel?.kategori ?? '',
        ringkasan: artikel?.ringkasan ?? '',
        konten: artikel?.konten ?? '',
        tanggal_terbit: artikel?.tanggal_terbit ?? tanggalHariIni,
        status_aktif: artikel ? (artikel.status_aktif ? '1' : '0') : '1',
        foto: null as File | null,
    });

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        if (edit) {
            form.transform((data) => ({ ...data, _method: 'put' }));
        }
        form.post(edit ? artikel.updateUrl : simpanUrl, { forceFormData: true });
    };

    return (
        <AdminLayout
            judul={edit ? 'Edit Artikel' : 'Tulis Artikel'}
            deskripsi={edit ? artikel.judul : 'Artikel baru untuk halaman /blog.'}
            aksi={
                edit && (
                    <Button asChild variant="outline" className="w-full sm:w-auto">
                        <a href={artikel.publikUrl} target="_blank" rel="noreferrer">
                            <ExternalLink />
                            Lihat di Blog
                        </a>
                    </Button>
                )
            }
        >
            <Head title={edit ? 'Edit Artikel' : 'Tulis Artikel'} />

            <form onSubmit={simpan} className="grid gap-5 pb-24 md:pb-0 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
                <Card className="grid grid-cols-1 gap-4 p-4 md:p-6">
                    <Field id="judul" label="Judul" error={form.errors.judul} wajib>
                        <Input id="judul" value={form.data.judul} onChange={(event) => form.setData('judul', event.target.value)} maxLength={200} required />
                    </Field>
                    <Field
                        id="ringkasan"
                        label="Ringkasan"
                        error={form.errors.ringkasan}
                        wajib
                        hint={`Tampil di daftar artikel dan hasil pencarian. ${form.data.ringkasan.length}/${BATAS_RINGKASAN} karakter.`}
                    >
                        <Textarea
                            id="ringkasan"
                            rows={3}
                            maxLength={BATAS_RINGKASAN}
                            value={form.data.ringkasan}
                            onChange={(event) => form.setData('ringkasan', event.target.value)}
                            placeholder="Ringkasan singkat isi artikel"
                            required
                        />
                    </Field>

                    <div className="space-y-1.5">
                        <div className="flex items-end justify-between gap-3">
                            <label htmlFor="konten" className="text-sm font-medium">
                                Konten <span className="text-destructive">*</span>
                            </label>
                            <div className="inline-flex rounded-lg border border-border bg-muted p-0.5 text-xs font-semibold" role="tablist" aria-label="Mode konten">
                                {[
                                    { nilai: false, label: 'Tulis' },
                                    { nilai: true, label: 'Pratinjau' },
                                ].map((mode) => (
                                    <button
                                        key={mode.label}
                                        type="button"
                                        role="tab"
                                        aria-selected={pratinjau === mode.nilai}
                                        onClick={() => setPratinjau(mode.nilai)}
                                        className={cn('min-h-9 rounded-md px-3', pratinjau === mode.nilai ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground')}
                                    >
                                        {mode.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                        {pratinjau ? (
                            <div
                                className="min-h-[22rem] rounded-lg border border-border bg-card px-4 py-3 text-sm leading-relaxed [&_a]:text-green [&_a]:underline [&_h2]:mb-2 [&_h2]:mt-5 [&_h2]:font-display [&_h2]:text-lg [&_h2]:font-semibold [&_h3]:mb-1.5 [&_h3]:mt-4 [&_h3]:font-semibold [&_li]:ml-5 [&_ol]:mb-3 [&_ol]:list-decimal [&_p]:mb-3 [&_ul]:mb-3 [&_ul]:list-disc"
                                // Superadmin-authored HTML, the same markup the public blog page renders.
                                dangerouslySetInnerHTML={{ __html: form.data.konten || '<p class="text-muted-foreground">Belum ada konten.</p>' }}
                            />
                        ) : (
                            <Textarea
                                id="konten"
                                rows={16}
                                value={form.data.konten}
                                onChange={(event) => form.setData('konten', event.target.value)}
                                placeholder="Boleh pakai tag HTML dasar, misal <p>, <h2>, <ul><li>, <strong>"
                                className="min-h-[22rem] font-mono text-sm"
                                required
                            />
                        )}
                        {form.errors.konten ? (
                            <p className="text-sm text-destructive" role="alert">
                                {form.errors.konten}
                            </p>
                        ) : (
                            <p className="text-xs text-muted-foreground">Mendukung tag HTML dasar: paragraf, heading, list, bold, link.</p>
                        )}
                    </div>
                </Card>

                <div className="space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Publikasi</CardTitle>
                            {edit && <CardDescription className="break-all">/blog/{artikel.slug}</CardDescription>}
                        </CardHeader>
                        <div className="grid grid-cols-1 gap-4 px-5 pb-5">
                            <Field id="kategori" label="Kategori" error={form.errors.kategori} wajib>
                                <Input
                                    id="kategori"
                                    list="daftar-kategori"
                                    value={form.data.kategori}
                                    onChange={(event) => form.setData('kategori', event.target.value)}
                                    placeholder="Misal Panduan Bisnis"
                                    required
                                />
                                <datalist id="daftar-kategori">
                                    {kategoriDipakai.map((kategori) => (
                                        <option key={kategori} value={kategori} />
                                    ))}
                                </datalist>
                            </Field>
                            <Field id="tanggal" label="Tanggal terbit" error={form.errors.tanggal_terbit} wajib>
                                <Input
                                    id="tanggal"
                                    type="date"
                                    value={form.data.tanggal_terbit}
                                    onChange={(event) => form.setData('tanggal_terbit', event.target.value)}
                                    required
                                />
                            </Field>
                            {edit && (
                                <Field id="status" label="Status" error={form.errors.status_aktif}>
                                    <Select value={form.data.status_aktif} onValueChange={(nilai) => form.setData('status_aktif', nilai)}>
                                        <SelectTrigger id="status">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">Terbit</SelectItem>
                                            <SelectItem value="0">Draft (disembunyikan)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </Field>
                            )}
                        </div>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Foto Unggulan</CardTitle>
                        </CardHeader>
                        <div className="px-5 pb-5">
                            <Field id="foto" label="Foto" error={form.errors.foto} className="[&>label]:sr-only">
                                <UnggahFoto
                                    id="foto"
                                    fotoSekarang={artikel?.fotoUrl ?? null}
                                    file={form.data.foto}
                                    onPilih={(file) => form.setData('foto', file)}
                                    labelTombol={edit ? 'Ganti foto' : 'Pilih foto'}
                                    bertumpuk
                                />
                            </Field>
                        </div>
                    </Card>

                    <div className="fixed inset-x-0 bottom-0 z-20 flex gap-2 border-t border-border bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur md:static md:border-0 md:bg-transparent md:p-0">
                        <Button asChild variant="outline">
                            <Link href={kembaliUrl}>Batal</Link>
                        </Button>
                        <Button type="submit" className="flex-1" disabled={form.processing}>
                            {edit ? 'Simpan Perubahan' : 'Terbitkan Artikel'}
                        </Button>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
