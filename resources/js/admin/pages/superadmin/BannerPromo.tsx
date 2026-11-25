import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import Field from '../../components/form/Field';
import PilihGambar from '../../components/form/PilihGambar';
import type { MediaPilihan } from '../../components/form/PilihMedia';
import KonfirmasiDialog from '../../components/KonfirmasiDialog';
import AdminLayout from '../../components/layout/AdminLayout';
import { Button } from '../../components/ui/button';
import { Card } from '../../components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../components/ui/dialog';
import { Input } from '../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../components/ui/select';
import { cn } from '../../lib/utils';

interface Banner {
    id: number;
    judul: string;
    fotoUrl: string;
    tautan: string | null;
    label_tombol: string | null;
    tampilkan_tombol: boolean;
    mulai_pada: string | null;
    berakhir_pada: string | null;
    target: string;
    frekuensi: string;
    tunda_detik: number;
    status_aktif: boolean;
    status: 'tayang' | 'terjadwal' | 'berakhir' | 'nonaktif';
    sedangTampil: boolean;
}

const STATUS: Record<Banner['status'], { label: string; kelas: string }> = {
    tayang: { label: 'Tayang', kelas: 'bg-green-pale text-green' },
    terjadwal: { label: 'Terjadwal', kelas: 'bg-gold-pale text-ink' },
    berakhir: { label: 'Berakhir', kelas: 'bg-muted text-muted-foreground' },
    nonaktif: { label: 'Nonaktif', kelas: 'bg-muted text-muted-foreground' },
};
const LABEL_TARGET: Record<string, string> = {
    semua: 'Semua halaman',
    beranda: 'Beranda saja',
    toko: 'Halaman toko saja',
};
const LABEL_FREKUENSI: Record<string, string> = {
    sekali: 'Sekali per pengunjung',
    sesi: 'Sekali per kunjungan',
    selalu: 'Setiap halaman dibuka',
};

const KOSONG = {
    judul: '',
    tautan: '',
    label_tombol: '',
    tampilkan_tombol: '1',
    mulai_pada: '',
    berakhir_pada: '',
    target: 'semua',
    frekuensi: 'sekali',
    tunda_detik: '2',
    status_aktif: '1',
    foto: null as File | null,
    media_path: '',
};

const waktu = (nilai: string) =>
    new Date(nilai).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

/** Pop-up promo banner shown to first-time visitors of the corporate site. */
export default function BannerPromoPage({ banner }: { banner: Banner[] }) {
    const [diedit, setDiedit] = useState<Banner | 'baru' | null>(null);
    const [akanDihapus, setAkanDihapus] = useState<Banner | null>(null);
    const form = useForm(KOSONG);
    const [media, setMedia] = useState<MediaPilihan | null>(null);

    const buka = (target: Banner | 'baru') => {
        form.clearErrors();
        setMedia(null);
        form.setData(
            target === 'baru'
                ? KOSONG
                : {
                      judul: target.judul,
                      tautan: target.tautan ?? '',
                      label_tombol: target.label_tombol ?? '',
                      tampilkan_tombol: target.tampilkan_tombol ? '1' : '0',
                      mulai_pada: target.mulai_pada ?? '',
                      berakhir_pada: target.berakhir_pada ?? '',
                      target: target.target,
                      frekuensi: target.frekuensi,
                      tunda_detik: String(target.tunda_detik),
                      status_aktif: target.status_aktif ? '1' : '0',
                      foto: null,
                      media_path: '',
                  },
        );
        setDiedit(target);
    };

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        const baru = diedit === 'baru';
        // The image upload forces multipart form data, which sends booleans as text.
        form.transform((data) => ({
            ...data,
            ...(baru ? {} : { _method: 'put' }),
        }));
        form.post(baru ? '/superadmin/banner-promo' : `/superadmin/banner-promo/${(diedit as Banner).id}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => setDiedit(null),
        });
    };

    return (
        <AdminLayout
            judul="Banner Promo"
            deskripsi="Pop-up promo yang muncul saat pengunjung membuka situs greendeahan.com."
            aksi={
                <Button className="w-full sm:w-auto" onClick={() => buka('baru')}>
                    <Plus />
                    Tambah Banner
                </Button>
            }
        >
            <Head title="Banner Promo" />
            <p className="mb-4 text-sm text-muted-foreground">
                Hanya satu banner yang tampil ke pengunjung: banner tayang yang paling baru dibuat. Halaman keranjang, checkout, pesanan, dan pendaftaran tidak pernah menampilkan
                pop-up.
            </p>

            {banner.length === 0 ? (
                <Card className="px-5 py-8 text-center text-sm text-muted-foreground">Belum ada banner promo.</Card>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {banner.map((item) => (
                        <Card key={item.id} className="flex flex-col overflow-hidden">
                            <div className="relative aspect-[4/3] bg-muted">
                                <img src={item.fotoUrl} alt={item.judul} className="size-full object-contain" loading="lazy" />
                                <span className={cn('absolute left-3 top-3 rounded-full px-2.5 py-0.5 text-xs font-semibold shadow-sm', STATUS[item.status].kelas)}>
                                    {STATUS[item.status].label}
                                </span>
                            </div>
                            <div className="flex flex-1 flex-col p-4">
                                <span className="font-display text-base font-semibold">{item.judul}</span>
                                {item.sedangTampil && <span className="mt-0.5 text-xs font-semibold text-green">Sedang tampil ke pengunjung</span>}
                                {item.status === 'tayang' && !item.sedangTampil && <span className="mt-0.5 text-xs text-muted-foreground">Tertutup banner yang lebih baru</span>}
                                <dl className="my-3 space-y-0.5 text-xs text-muted-foreground">
                                    <div>
                                        {item.mulai_pada || item.berakhir_pada
                                            ? `${item.mulai_pada ? waktu(item.mulai_pada) : 'Sekarang'} sampai ${item.berakhir_pada ? waktu(item.berakhir_pada) : 'tanpa batas'}`
                                            : 'Tanpa batas waktu'}
                                    </div>
                                    <div>
                                        {LABEL_TARGET[item.target]}, {LABEL_FREKUENSI[item.frekuensi].toLowerCase()}
                                    </div>
                                    {item.tautan && <div className="truncate">Tautan: {item.tautan}</div>}
                                </dl>
                                <div className="mt-auto flex flex-wrap gap-2">
                                    <Button variant="ghost" size="sm" onClick={() => buka(item)}>
                                        <Pencil />
                                        Edit
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        onClick={() => setAkanDihapus(item)}
                                    >
                                        <Trash2 />
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        </Card>
                    ))}
                </div>
            )}

            <Dialog open={diedit !== null} onOpenChange={(terbuka) => !terbuka && setDiedit(null)}>
                <DialogContent className="max-h-[90dvh] overflow-y-auto">
                    <form onSubmit={simpan} className="grid gap-4">
                        <DialogHeader>
                            <DialogTitle>{diedit === 'baru' ? 'Tambah Banner Promo' : 'Edit Banner Promo'}</DialogTitle>
                            <DialogDescription>Banner yang diedit akan tampil lagi ke pengunjung yang sudah menutupnya.</DialogDescription>
                        </DialogHeader>
                        <Field
                            id="banner-foto"
                            label="Gambar banner"
                            error={form.errors.foto ?? form.errors.media_path}
                            hint="JPG, PNG, atau WebP, maksimal 4 MB. Rasio persegi atau 4:5 paling pas di HP."
                            wajib={diedit === 'baru'}
                        >
                            <PilihGambar
                                id="banner-foto"
                                gambarSekarang={diedit !== 'baru' && diedit ? diedit.fotoUrl : null}
                                file={form.data.foto}
                                media={media}
                                onFile={(file) => form.setData('foto', file)}
                                onMedia={(dipilih) => {
                                    setMedia(dipilih);
                                    form.setData('media_path', dipilih?.path ?? '');
                                }}
                            />
                        </Field>
                        <Field id="banner-judul" label="Judul" error={form.errors.judul} hint="Nama banner untuk admin, juga dibacakan pembaca layar sebagai teks gambar." wajib>
                            <Input id="banner-judul" value={form.data.judul} onChange={(event) => form.setData('judul', event.target.value)} maxLength={150} required />
                        </Field>
                        <Field
                            id="banner-tautan"
                            label="Tautan (opsional)"
                            error={form.errors.tautan}
                            hint="Halaman tujuan saat banner diklik, misal /toko atau https://wa.me/62..."
                        >
                            <Input
                                id="banner-tautan"
                                value={form.data.tautan}
                                onChange={(event) => form.setData('tautan', event.target.value)}
                                maxLength={500}
                                placeholder="/toko"
                                inputMode="url"
                            />
                        </Field>
                        {form.data.tautan !== '' && (
                            <>
                                <label className="flex min-h-10 items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={form.data.tampilkan_tombol === '1'}
                                        onChange={(event) => form.setData('tampilkan_tombol', event.target.checked ? '1' : '0')}
                                    />
                                    Tampilkan tombol di bawah gambar
                                </label>
                                {form.data.tampilkan_tombol === '1' ? (
                                    <Field id="banner-tombol" label="Teks tombol" error={form.errors.label_tombol} hint='Kosongkan untuk memakai "Lihat Promo".'>
                                        <Input
                                            id="banner-tombol"
                                            value={form.data.label_tombol}
                                            onChange={(event) => form.setData('label_tombol', event.target.value)}
                                            maxLength={40}
                                            placeholder="Lihat Promo"
                                        />
                                    </Field>
                                ) : (
                                    <p className="-mt-2 text-xs text-muted-foreground">Tanpa tombol, pengunjung tetap bisa mengklik gambar banner untuk membuka tautan.</p>
                                )}
                            </>
                        )}
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <Field id="banner-mulai" label="Mulai tayang" error={form.errors.mulai_pada} hint="Kosong berarti langsung.">
                                <Input id="banner-mulai" type="datetime-local" value={form.data.mulai_pada} onChange={(event) => form.setData('mulai_pada', event.target.value)} />
                            </Field>
                            <Field id="banner-akhir" label="Berakhir" error={form.errors.berakhir_pada} hint="Kosong berarti tanpa batas. Waktu WIB.">
                                <Input
                                    id="banner-akhir"
                                    type="datetime-local"
                                    value={form.data.berakhir_pada}
                                    onChange={(event) => form.setData('berakhir_pada', event.target.value)}
                                />
                            </Field>
                        </div>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <Field id="banner-target" label="Tampil di" error={form.errors.target}>
                                <Select value={form.data.target} onValueChange={(nilai) => form.setData('target', nilai)}>
                                    <SelectTrigger id="banner-target">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(LABEL_TARGET).map(([nilai, label]) => (
                                            <SelectItem key={nilai} value={nilai}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                            <Field id="banner-frekuensi" label="Seberapa sering" error={form.errors.frekuensi}>
                                <Select value={form.data.frekuensi} onValueChange={(nilai) => form.setData('frekuensi', nilai)}>
                                    <SelectTrigger id="banner-frekuensi">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(LABEL_FREKUENSI).map(([nilai, label]) => (
                                            <SelectItem key={nilai} value={nilai}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                        </div>
                        <Field id="banner-tunda" label="Jeda sebelum muncul (detik)" error={form.errors.tunda_detik} hint="0 sampai 30 detik.">
                            <Input
                                id="banner-tunda"
                                inputMode="numeric"
                                value={form.data.tunda_detik}
                                onChange={(event) => form.setData('tunda_detik', event.target.value.replace(/\D/g, '').slice(0, 2))}
                            />
                        </Field>
                        <label className="flex min-h-10 items-center gap-2 text-sm">
                            <input type="checkbox" checked={form.data.status_aktif === '1'} onChange={(event) => form.setData('status_aktif', event.target.checked ? '1' : '0')} />
                            Aktif
                        </label>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDiedit(null)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <KonfirmasiDialog
                terbuka={akanDihapus !== null}
                onTutup={() => setAkanDihapus(null)}
                onLanjut={() =>
                    akanDihapus &&
                    router.delete(`/superadmin/banner-promo/${akanDihapus.id}`, {
                        preserveScroll: true,
                        onFinish: () => setAkanDihapus(null),
                    })
                }
                judul="Hapus banner promo?"
                deskripsi={`Banner "${akanDihapus?.judul ?? ''}" dan gambarnya dihapus permanen.`}
                labelLanjut="Ya, Hapus"
            />
        </AdminLayout>
    );
}
