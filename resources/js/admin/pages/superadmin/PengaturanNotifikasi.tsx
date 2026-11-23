import { Head, Link, router, useForm } from '@inertiajs/react';
import { Send } from 'lucide-react';
import type { FormEvent } from 'react';
import Field from '../../components/form/Field';
import AdminLayout from '../../components/layout/AdminLayout';
import { Button } from '../../components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '../../components/ui/card';
import { Textarea } from '../../components/ui/input';

interface Props {
    pengaturan: { userIds: number[]; emailTambahan: string };
    pilihanUser: { id: number; name: string; email: string }[];
    mailer: string;
    emailAktif: boolean;
    peringatanAktif: boolean;
    jedaMenit: number;
    simpanUrl: string;
    ujiUrl: string;
    logErrorUrl: string;
}

/** Who gets the "Error baru di aplikasi" email. The bell always goes to every superadmin. */
export default function PengaturanNotifikasi({ pengaturan, pilihanUser, mailer, emailAktif, peringatanAktif, jedaMenit, simpanUrl, ujiUrl, logErrorUrl }: Props) {
    const form = useForm({ user_ids: pengaturan.userIds, email_tambahan: pengaturan.emailTambahan });
    const galat: Record<string, string> = form.errors;
    const galatEmail = galat.email_tambahan ?? Object.entries(galat).find(([kunci]) => kunci.startsWith('email_tambahan.'))?.[1];
    const galatUser = galat.user_ids ?? Object.entries(galat).find(([kunci]) => kunci.startsWith('user_ids.'))?.[1];

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        form.put(simpanUrl, { preserveScroll: true });
    };

    const ubahPilihan = (id: number, dipilih: boolean) => form.setData('user_ids', dipilih ? [...form.data.user_ids, id] : form.data.user_ids.filter((item) => item !== id));

    return (
        <AdminLayout judul="Email & Notifikasi" deskripsi="Atur siapa yang diberi tahu saat aplikasi mencatat error.">
            <Head title="Email & Notifikasi" />

            <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
                <form onSubmit={simpan} className="space-y-5 pb-24 md:pb-0">
                    <Card>
                        <CardHeader>
                            <CardTitle>Error baru di aplikasi</CardTitle>
                            <CardDescription>
                                Setiap superadmin otomatis mendapat notifikasi lonceng. Email hanya dikirim ke penerima di bawah. Error yang sama dikabarkan paling banyak sekali
                                per {jedaMenit >= 60 && jedaMenit % 60 === 0 ? `${jedaMenit / 60} jam` : `${jedaMenit} menit`}.
                            </CardDescription>
                        </CardHeader>
                        <div className="grid gap-5 px-5 pb-5">
                            <fieldset className="grid gap-2">
                                <legend className="mb-1 text-sm font-medium">Superadmin yang menerima email</legend>
                                {pilihanUser.map((user) => (
                                    <label key={user.id} className="flex min-h-11 items-center gap-3 rounded-lg border border-border px-3 text-sm">
                                        <input type="checkbox" checked={form.data.user_ids.includes(user.id)} onChange={(event) => ubahPilihan(user.id, event.target.checked)} />
                                        <span className="min-w-0">
                                            <span className="block truncate font-semibold">{user.name}</span>
                                            <span className="block truncate text-xs text-muted-foreground">{user.email}</span>
                                        </span>
                                    </label>
                                ))}
                                {galatUser && <p className="text-sm text-destructive">{galatUser}</p>}
                            </fieldset>

                            <Field
                                id="email-tambahan"
                                label="Email tambahan"
                                error={galatEmail}
                                hint="Opsional, untuk orang di luar daftar superadmin. Pisahkan dengan baris baru atau koma, maksimal 10 alamat."
                            >
                                <Textarea
                                    id="email-tambahan"
                                    rows={4}
                                    value={form.data.email_tambahan}
                                    placeholder="teknisi@contoh.com"
                                    onChange={(event) => form.setData('email_tambahan', event.target.value)}
                                />
                            </Field>
                        </div>
                    </Card>

                    <div className="fixed inset-x-0 bottom-0 z-20 flex gap-2 border-t border-border bg-background/95 p-3 backdrop-blur md:static md:border-0 md:bg-transparent md:p-0">
                        <Button type="submit" disabled={form.processing}>
                            Simpan
                        </Button>
                        <Button type="button" variant="outline" onClick={() => router.post(ujiUrl, {}, { preserveScroll: true })}>
                            <Send />
                            Kirim email uji
                        </Button>
                    </div>
                </form>

                <div className="space-y-5 pb-24 md:pb-0">
                    <Card className="p-5 text-sm">
                        <CardTitle className="mb-2">Status</CardTitle>
                        <ul className="space-y-2 text-muted-foreground">
                            <li>
                                Peringatan error: <strong className="text-foreground">{peringatanAktif ? 'aktif' : 'dimatikan'}</strong>
                            </li>
                            <li>
                                Pengiriman email: <strong className="text-foreground">{emailAktif ? `aktif (${mailer})` : `belum aktif (${mailer})`}</strong>
                            </li>
                        </ul>
                        {!emailAktif && (
                            <p className="mt-3 rounded-lg bg-amber-pale px-3 py-2 text-xs text-amber">
                                Email belum benar-benar terkirim karena MAIL_MAILER masih {mailer}. Atur SMTP (misalnya Resend) di .env, lalu coba Kirim email uji.
                            </p>
                        )}
                        <Link href={logErrorUrl} className="mt-3 inline-block text-xs underline">
                            Buka Log Error
                        </Link>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
