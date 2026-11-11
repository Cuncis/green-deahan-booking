import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import Field from '../../components/form/Field';
import AdminLayout from '../../components/layout/AdminLayout';
import { Button } from '../../components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '../../components/ui/card';
import { Input, Textarea } from '../../components/ui/input';

interface Pengaturan {
    nama_situs: string;
    meta_deskripsi: string;
    og_image_url: string;
    ga4_measurement_id: string;
    gtm_id: string;
    gsc_verifikasi: string;
    indeks_mesin_pencari: boolean;
}

interface Props {
    pengaturan: Pengaturan;
    simpanUrl: string;
    mediaUrl: string;
}

const tautanLuar = (href: string, label: string) => (
    <a href={href} target="_blank" rel="noopener noreferrer" className="underline">
        {label}
    </a>
);

function Langkah({ judul, children }: { judul: string; children: ReactNode }) {
    return (
        <div>
            <div className="mb-1 text-xs font-bold">{judul}</div>
            <ol className="list-decimal space-y-1 ps-5 text-xs text-muted-foreground">{children}</ol>
        </div>
    );
}

/** GA4, Tag Manager, Search Console and default SEO for the corporate site only. */
export default function Seo({ pengaturan, simpanUrl, mediaUrl }: Props) {
    const form = useForm({ ...pengaturan });
    const panjangDeskripsi = form.data.meta_deskripsi.length;

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, indeks_mesin_pencari: data.indeks_mesin_pencari ? '1' : '0' }));
        form.put(simpanUrl, { preserveScroll: true });
    };

    return (
        <AdminLayout
            judul="SEO dan Analytics"
            deskripsi="Berlaku untuk situs korporat (beranda, tentang kami, galeri, blog, kontak, daftar). Halaman booking tenant tidak ikut dilacak."
        >
            <Head title="SEO dan Analytics" />

            <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                <form onSubmit={simpan} className="space-y-5 pb-24 md:pb-0">
                    <Card>
                        <CardHeader>
                            <CardTitle>SEO Dasar</CardTitle>
                            <CardDescription>Judul, deskripsi, dan gambar saat link situs dibagikan.</CardDescription>
                        </CardHeader>
                        <div className="grid grid-cols-1 gap-4 px-5 pb-5">
                            <Field id="nama-situs" label="Nama situs" error={form.errors.nama_situs} wajib>
                                <Input id="nama-situs" value={form.data.nama_situs} onChange={(event) => form.setData('nama_situs', event.target.value)} maxLength={100} required />
                            </Field>
                            <Field
                                id="meta-deskripsi"
                                label="Deskripsi default"
                                error={form.errors.meta_deskripsi}
                                wajib
                                hint={`${panjangDeskripsi} karakter, ideal 120 sampai 160. Halaman blog memakai ringkasan artikelnya sendiri.`}
                            >
                                <Textarea
                                    id="meta-deskripsi"
                                    rows={3}
                                    maxLength={300}
                                    value={form.data.meta_deskripsi}
                                    onChange={(event) => form.setData('meta_deskripsi', event.target.value)}
                                    required
                                />
                            </Field>
                            <Field id="og-image" label="Gambar share (Open Graph)" error={form.errors.og_image_url} hint="Ukuran ideal 1200 x 630. Salin URL dari halaman Media.">
                                <Input
                                    id="og-image"
                                    type="url"
                                    inputMode="url"
                                    value={form.data.og_image_url}
                                    onChange={(event) => form.setData('og_image_url', event.target.value)}
                                    placeholder="https://..."
                                />
                            </Field>
                            {form.data.og_image_url && /^https?:\/\//.test(form.data.og_image_url) && (
                                <img
                                    src={form.data.og_image_url}
                                    alt="Pratinjau gambar share"
                                    className="aspect-[1200/630] w-full max-w-sm rounded-lg border border-border object-cover"
                                />
                            )}
                            <label className="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-border px-3 text-sm">
                                <input
                                    type="checkbox"
                                    checked={form.data.indeks_mesin_pencari}
                                    onChange={(event) => form.setData('indeks_mesin_pencari', event.target.checked)}
                                    className="size-5 rounded border-input accent-[#3A6B4A]"
                                />
                                Izinkan mesin pencari mengindeks situs
                            </label>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Google Analytics dan Tag Manager</CardTitle>
                            <CardDescription>Pakai salah satu saja supaya kunjungan tidak terhitung dua kali.</CardDescription>
                        </CardHeader>
                        <div className="grid grid-cols-1 gap-4 px-5 pb-5 md:grid-cols-2">
                            <Field id="ga4" label="Measurement ID (GA4)" error={form.errors.ga4_measurement_id} hint="Berawalan G- atau GT-. Kosongkan untuk mematikan.">
                                <Input
                                    id="ga4"
                                    value={form.data.ga4_measurement_id}
                                    onChange={(event) => form.setData('ga4_measurement_id', event.target.value.toUpperCase().trim())}
                                    placeholder="G-XXXXXXXXXX"
                                    autoCapitalize="characters"
                                    spellCheck={false}
                                />
                            </Field>
                            <Field id="gtm" label="Container ID (Tag Manager)" error={form.errors.gtm_id} hint="Berawalan GTM-.">
                                <Input
                                    id="gtm"
                                    value={form.data.gtm_id}
                                    onChange={(event) => form.setData('gtm_id', event.target.value.toUpperCase().trim())}
                                    placeholder="GTM-XXXXXXX"
                                    autoCapitalize="characters"
                                    spellCheck={false}
                                />
                            </Field>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Google Search Console</CardTitle>
                            <CardDescription>Pilih metode verifikasi HTML tag, lalu salin nilai content dari tag meta-nya saja.</CardDescription>
                        </CardHeader>
                        <div className="px-5 pb-5">
                            <Field id="gsc" label="Kode verifikasi" error={form.errors.gsc_verifikasi}>
                                <Input
                                    id="gsc"
                                    value={form.data.gsc_verifikasi}
                                    onChange={(event) => form.setData('gsc_verifikasi', event.target.value.trim())}
                                    spellCheck={false}
                                    autoCapitalize="none"
                                />
                            </Field>
                        </div>
                    </Card>

                    <div className="fixed inset-x-0 bottom-0 z-20 border-t border-border bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur md:static md:border-0 md:bg-transparent md:p-0">
                        <Button type="submit" className="w-full md:w-auto" disabled={form.processing}>
                            Simpan Pengaturan
                        </Button>
                    </div>
                </form>

                <Card className="lg:sticky lg:top-20">
                    <details className="group">
                        <summary className="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-5 py-3 text-sm font-semibold">
                            Panduan langkah demi langkah
                            <ChevronDown className="size-4 transition-transform group-open:rotate-180" />
                        </summary>
                        <div className="space-y-4 border-t border-border px-5 py-4">
                            <Langkah judul="Google Analytics 4">
                                <li>Buka {tautanLuar('https://analytics.google.com', 'analytics.google.com')} dan masuk dengan akun Google.</li>
                                <li>Buat properti baru (Admin, Create, Property), isi nama dan zona waktu.</li>
                                <li>Pilih platform Web, masukkan domain situs korporat, beri nama stream.</li>
                                <li>
                                    Salin Measurement ID berawalan <code>G-</code>, tempel di kolom Measurement ID, lalu simpan.
                                </li>
                                <li>Cek di Reports, Realtime sambil membuka situs di tab lain.</li>
                            </Langkah>
                            <Langkah judul="Google Tag Manager (opsional)">
                                <li>Buka {tautanLuar('https://tagmanager.google.com', 'tagmanager.google.com')}, buat container bertipe Web.</li>
                                <li>
                                    Salin Container ID berawalan <code>GTM-</code>, tempel di kolomnya, lalu simpan.
                                </li>
                                <li>Di Tag Manager, buat tag GA4 dengan trigger All Pages, lalu Publish.</li>
                            </Langkah>
                            <Langkah judul="Google Search Console">
                                <li>Buka {tautanLuar('https://search.google.com/search-console', 'Search Console')}, klik Add property.</li>
                                <li>
                                    Pilih URL prefix, isi alamat situs, misalnya <code>https://greendeahan.com</code>.
                                </li>
                                <li>
                                    Pilih verifikasi HTML tag, salin isi <code>content</code> saja, tempel di kolom Kode verifikasi.
                                </li>
                                <li>Simpan, lalu kembali ke Search Console dan klik Verify.</li>
                            </Langkah>
                            <Langkah judul="SEO dasar">
                                <li>Isi deskripsi yang menjelaskan bisnis, sekitar 120 sampai 160 karakter.</li>
                                <li>
                                    Upload gambar share di{' '}
                                    <Link href={mediaUrl} className="underline">
                                        Media
                                    </Link>
                                    , salin URL-nya, tempel di kolom Gambar share.
                                </li>
                                <li>Matikan indeks mesin pencari hanya saat situs masih tahap pengembangan.</li>
                            </Langkah>
                            <p className="text-xs text-muted-foreground">Data GA4 biasanya lengkap setelah 24 sampai 48 jam. Search Console butuh beberapa hari.</p>
                        </div>
                    </details>
                </Card>
            </div>
        </AdminLayout>
    );
}
