import { Deferred, Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Copy, ExternalLink, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import Field from '../../../components/form/Field';
import KonfirmasiDialog from '../../../components/KonfirmasiDialog';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Badge, varianPaket } from '../../../components/ui/badge';
import { Button } from '../../../components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input } from '../../../components/ui/input';
import { Skeleton } from '../../../components/ui/skeleton';
import { toast } from 'sonner';
import { AksiTenant, StatusTenant, type TenantAksi } from './Index';

interface TenantDetail extends TenantAksi {
    domain: string;
    paket: string;
    mulai: string | null;
    berakhir: string | null;
    whatsapp: string | null;
    email: string | null;
    customDomain: string | null;
    customDomainDiminta: string | null;
    simpanDomainUrl: string;
    hapusDomainUrl: string;
}

type StatusDomain = 'aktif' | 'ssl_pending' | 'belum_dikonfigurasi';

interface Props {
    detail: TenantDetail;
    statusCustomDomain?: StatusDomain;
    perintahServer: string[];
    kembaliUrl: string;
}

const LABEL_STATUS: Record<StatusDomain, { label: string; varian: 'confirmed' | 'pending' | 'info' }> = {
    aktif: { label: 'Aktif', varian: 'confirmed' },
    ssl_pending: { label: 'SSL Pending', varian: 'pending' },
    belum_dikonfigurasi: { label: 'Belum dikonfigurasi', varian: 'info' },
};

function Baris({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-4 border-b border-dashed border-border py-2.5 text-sm last:border-0">
            <span className="shrink-0 text-muted-foreground">{label}</span>
            <span className="min-w-0 break-words text-right font-semibold">{children}</span>
        </div>
    );
}

function PerintahServer({ perintah }: { perintah: string }) {
    return (
        <div className="relative">
            <pre className="overflow-x-auto whitespace-pre-wrap break-all rounded-lg bg-ink p-3 pr-12 text-xs text-cream">{perintah}</pre>
            <button
                type="button"
                aria-label="Salin perintah"
                onClick={() => navigator.clipboard?.writeText(perintah).then(() => toast.success('Perintah disalin'))}
                className="absolute right-1 top-1 inline-flex size-10 items-center justify-center rounded-md text-cream/70 hover:bg-white/10 hover:text-cream"
            >
                <Copy className="size-4" />
            </button>
        </div>
    );
}

export default function Show({ detail: tenant, statusCustomDomain, perintahServer, kembaliUrl }: Props) {
    const form = useForm({
        custom_domain: tenant.customDomain ?? tenant.customDomainDiminta ?? '',
    });
    const [konfirmasiHapus, setKonfirmasiHapus] = useState(false);
    const [menghapus, setMenghapus] = useState(false);
    const bisaCustomDomain = tenant.paket !== 'basic';

    const simpanDomain = (event: FormEvent) => {
        event.preventDefault();
        form.post(tenant.simpanDomainUrl, { preserveScroll: true });
    };

    const hapusDomain = () =>
        router.delete(tenant.hapusDomainUrl, {
            preserveScroll: true,
            onStart: () => setMenghapus(true),
            onFinish: () => {
                setMenghapus(false);
                setKonfirmasiHapus(false);
            },
            onSuccess: () => form.setData('custom_domain', ''),
        });

    return (
        <AdminLayout
            judul={tenant.namaBisnis}
            deskripsi={tenant.domain}
            aksi={
                <div className="flex items-center gap-2">
                    <Badge variant={varianPaket(tenant.paket)}>{tenant.paket}</Badge>
                    <StatusTenant aktif={tenant.aktif} />
                </div>
            }
        >
            <Head title={tenant.namaBisnis} />

            <Link href={kembaliUrl} className="-mt-2 mb-4 inline-flex min-h-10 items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft className="size-4" />
                Semua Tenant
            </Link>

            <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)] lg:items-start">
                <Card>
                    <CardHeader>
                        <CardTitle>Info Tenant</CardTitle>
                    </CardHeader>
                    <div className="px-5 pb-2">
                        <Baris label="Domain default">
                            <a href={`https://${tenant.domain}`} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 hover:underline">
                                {tenant.domain}
                                <ExternalLink className="size-3.5" />
                            </a>
                        </Baris>
                        <Baris label="Masa aktif">
                            {tenant.mulai ?? '-'} sampai {tenant.berakhir ?? '-'}
                        </Baris>
                        <Baris label="WhatsApp PIC">{tenant.whatsapp ?? '-'}</Baris>
                        <Baris label="Email PIC">{tenant.email ?? '-'}</Baris>
                    </div>
                    <div className="border-t border-border p-5">
                        <AksiTenant tenant={tenant} className="grid gap-2 sm:grid-cols-2" />
                        {!tenant.email && <p className="mt-2 text-xs text-muted-foreground">Undangan owner butuh email PIC.</p>}
                    </div>
                </Card>

                <div className="space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Custom Domain</CardTitle>
                            <CardDescription>Domain milik tenant sendiri, untuk paket Pro dan Premium.</CardDescription>
                        </CardHeader>
                        <div className="space-y-4 px-5 pb-5">
                            {tenant.customDomainDiminta && !tenant.customDomain && (
                                <div className="rounded-lg border border-gold/30 bg-gold-pale px-4 py-3 text-sm">
                                    Pelanggan meminta custom domain <strong>{tenant.customDomainDiminta}</strong> saat mendaftar (+Rp250.000/tahun). Verifikasi kepemilikan
                                    domainnya dulu sebelum disimpan.
                                </div>
                            )}

                            {!bisaCustomDomain ? (
                                <div className="rounded-lg border border-gold/30 bg-gold-pale px-4 py-3 text-sm">
                                    Custom domain hanya tersedia untuk paket Pro dan Premium. Upgrade paket tenant ini dulu untuk bisa memakai domain sendiri.
                                </div>
                            ) : (
                                <>
                                    <div className="flex items-center justify-between gap-3 text-sm">
                                        <span className="text-muted-foreground">Status verifikasi</span>
                                        <Deferred data="statusCustomDomain" fallback={<Skeleton className="h-6 w-28 rounded-full" />}>
                                            <Badge variant={LABEL_STATUS[statusCustomDomain ?? 'belum_dikonfigurasi'].varian}>
                                                {LABEL_STATUS[statusCustomDomain ?? 'belum_dikonfigurasi'].label}
                                            </Badge>
                                        </Deferred>
                                    </div>

                                    <form onSubmit={simpanDomain} className="flex flex-col gap-2 sm:flex-row sm:items-start">
                                        <Field id="custom-domain" label="Domain" error={form.errors.custom_domain} className="flex-1">
                                            <Input
                                                id="custom-domain"
                                                inputMode="url"
                                                autoCapitalize="none"
                                                autoCorrect="off"
                                                spellCheck={false}
                                                value={form.data.custom_domain}
                                                onChange={(event) => form.setData('custom_domain', event.target.value)}
                                                placeholder="namadomain.com"
                                                required
                                            />
                                        </Field>
                                        <Button type="submit" disabled={form.processing} className="sm:mt-[1.625rem]">
                                            Simpan Domain
                                        </Button>
                                    </form>

                                    {tenant.customDomain && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            onClick={() => setKonfirmasiHapus(true)}
                                        >
                                            <Trash2 />
                                            Hapus custom domain
                                        </Button>
                                    )}
                                </>
                            )}
                        </div>
                    </Card>

                    {perintahServer.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Perintah Server</CardTitle>
                                <CardDescription>Jalankan di server supaya {tenant.customDomain} aktif dengan SSL.</CardDescription>
                            </CardHeader>
                            <div className="space-y-2 px-5 pb-5">
                                {perintahServer.map((perintah) => (
                                    <PerintahServer key={perintah} perintah={perintah} />
                                ))}
                            </div>
                        </Card>
                    )}
                </div>
            </div>

            <KonfirmasiDialog
                terbuka={konfirmasiHapus}
                onTutup={() => setKonfirmasiHapus(false)}
                onLanjut={hapusDomain}
                judul="Hapus custom domain?"
                deskripsi={`Tenant kembali hanya bisa diakses lewat ${tenant.domain}.`}
                labelLanjut="Hapus"
                memproses={menghapus}
            />
        </AdminLayout>
    );
}
