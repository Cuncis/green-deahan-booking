import { Head, router, useForm } from '@inertiajs/react';
import { PlugZap, RotateCcw, Zap } from 'lucide-react';
import type { FormEvent } from 'react';
import Field from '../../../components/form/Field';
import AdminLayout from '../../../components/layout/AdminLayout';
import PemakaianOngkir, { type DataOngkir } from '../../../components/toko/PemakaianOngkir';
import { Button } from '../../../components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input } from '../../../components/ui/input';
import { angka } from '../../../lib/format';

interface Props extends DataOngkir {
    pengaturan: { batas: number; sisaAman: number; batasEnv: number; sisaAmanEnv: number };
    kurir: string;
}

/** Daily request limit for RajaOngkir (free plan: 100 per day), today's usage, and the Biteship fallback cost. */
export default function PengaturanOngkir({ rajaongkir, biteship, pengaturan, kurir }: Props) {
    const form = useForm({ batas: String(pengaturan.batas), sisa_aman: String(pengaturan.sisaAman) });
    const angkaSaja = (nama: 'batas' | 'sisa_aman') => ({
        value: form.data[nama],
        inputMode: 'numeric' as const,
        onChange: (event: React.ChangeEvent<HTMLInputElement>) => form.setData(nama, event.target.value.replace(/\D/g, '')),
    });

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        form.put('/superadmin/toko/ongkir', { preserveScroll: true });
    };

    const kirim = (url: string) => router.post(url, {}, { preserveScroll: true });

    return (
        <AdminLayout judul="Pengaturan Ongkir" deskripsi="Batas permintaan harian RajaOngkir dan pemakaian hari ini. Setelah batas habis, ongkir otomatis memakai Biteship.">
            <Head title="Pengaturan Ongkir" />

            <Card className="mb-5 overflow-hidden">
                <CardHeader className="border-b border-border py-4">
                    <CardTitle className="text-xs uppercase tracking-wide text-green">Pemakaian hari ini</CardTitle>
                </CardHeader>
                <PemakaianOngkir data={{ rajaongkir, biteship }} />
                <div className="flex flex-wrap gap-2 border-t border-border p-4">
                    <Button type="button" variant="outline" size="sm" disabled={!rajaongkir.aktif} onClick={() => kirim('/superadmin/toko/ongkir/uji')}>
                        <PlugZap />
                        Uji koneksi (pakai 1 permintaan)
                    </Button>
                    <Button type="button" variant="outline" size="sm" disabled={rajaongkir.alasanHabis === null} onClick={() => kirim('/superadmin/toko/ongkir/coba-lagi')}>
                        <Zap />
                        Coba RajaOngkir lagi
                    </Button>
                    <Button type="button" variant="ghost" size="sm" onClick={() => kirim('/superadmin/toko/ongkir/setel-ulang')}>
                        <RotateCcw />
                        Setel ulang hitungan hari ini
                    </Button>
                </div>
            </Card>

            <form onSubmit={simpan} className="max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Batas harian</CardTitle>
                        <CardDescription>
                            Tiap pencarian lokasi dan hitung tarif ke RajaOngkir dihitung satu permintaan. Hasil yang sudah tersimpan di cache tidak memakai batas. Nilai awal dari
                            .env: {angka(pengaturan.batasEnv)} per hari, sisa aman {angka(pengaturan.sisaAmanEnv)}.
                        </CardDescription>
                    </CardHeader>
                    <div className="grid gap-4 px-6 pb-6 sm:grid-cols-2">
                        <Field id="ongkir-batas" label="Batas permintaan per hari" error={form.errors.batas} hint="Samakan dengan jatah paket RajaOngkir Anda (gratis: 100)." wajib>
                            <Input id="ongkir-batas" maxLength={6} required {...angkaSaja('batas')} />
                        </Field>
                        <Field
                            id="ongkir-sisa"
                            label="Sisa aman"
                            error={form.errors.sisa_aman}
                            hint="Permintaan terakhir yang sengaja tidak dipakai, supaya hitungan yang meleset tidak membuat RajaOngkir menolak lebih dulu."
                            wajib
                        >
                            <Input id="ongkir-sisa" maxLength={4} required {...angkaSaja('sisa_aman')} />
                        </Field>
                        <p className="text-xs text-muted-foreground sm:col-span-2">
                            Kurir yang ditawarkan: <span className="font-mono">{kurir}</span>. Diubah lewat RAJAONGKIR_COURIERS di .env.
                        </p>
                        <div className="sm:col-span-2">
                            <Button type="submit" className="w-full sm:w-auto" disabled={form.processing}>
                                Simpan
                            </Button>
                        </div>
                    </div>
                </Card>
            </form>
        </AdminLayout>
    );
}
