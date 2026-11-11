import { Head, router, useForm } from '@inertiajs/react';
import { Check, Copy, Search, Trash2, Upload, X } from 'lucide-react';
import { useEffect, useRef, useState, type DragEvent } from 'react';
import { toast } from 'sonner';
import AdminLayout from '../../components/layout/AdminLayout';
import Pagination, { type Paginated } from '../../components/Pagination';
import { Badge } from '../../components/ui/badge';
import { Button } from '../../components/ui/button';
import { Card } from '../../components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../components/ui/dialog';
import { Input } from '../../components/ui/input';
import { Label } from '../../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../components/ui/select';
import { angka } from '../../lib/format';
import { cn } from '../../lib/utils';

interface Media {
    path: string;
    nama: string;
    folder: string;
    url: string;
    ukuran: number;
    diubah: string;
    dipakai: boolean;
}

interface Props {
    media: Paginated<Media>;
    daftarFolder: string[];
    filter: { folder: string | null; q: string | null };
    totalMedia: number;
    totalUkuran: number;
}

const SEMUA = 'semua';
const MAKS_FILE = 20;

/** 1536000 -> "1,5 MB" */
const ukuranFile = (byte: number): string => {
    if (byte >= 1024 * 1024) {
        return `${(byte / 1024 / 1024).toLocaleString('id-ID', { maximumFractionDigits: 1 })} MB`;
    }

    return `${Math.max(1, Math.round(byte / 1024)).toLocaleString('id-ID')} KB`;
};

/** Upload box: tap to pick or drag files in, then upload them in one go. */
function UnggahMedia() {
    const masukan = useRef<HTMLInputElement>(null);
    const [menyeret, setMenyeret] = useState(false);
    const form = useForm({ files: [] as File[] });
    const galat = Object.entries(form.errors as Record<string, string>)[0]?.[1];

    const pilih = (daftar: FileList | null) => {
        const gambar = Array.from(daftar ?? []).filter((file) => file.type.startsWith('image/'));
        form.setData('files', [...form.data.files, ...gambar].slice(0, MAKS_FILE));
        form.clearErrors();
    };

    const jatuhkan = (event: DragEvent) => {
        event.preventDefault();
        setMenyeret(false);
        pilih(event.dataTransfer.files);
    };

    const unggah = () =>
        form.post('/superadmin/media', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                if (masukan.current) {
                    masukan.current.value = '';
                }
            },
        });

    return (
        <Card className="mb-5 p-4">
            <div
                onDragOver={(event) => {
                    event.preventDefault();
                    setMenyeret(true);
                }}
                onDragLeave={() => setMenyeret(false)}
                onDrop={jatuhkan}
                className={cn(
                    'flex flex-col items-center gap-2 rounded-xl border-2 border-dashed px-4 py-6 text-center transition-colors',
                    menyeret ? 'border-primary bg-green-pale' : 'border-border',
                )}
            >
                <Upload className="size-6 text-muted-foreground" aria-hidden="true" />
                <p className="text-sm">
                    <span className="hidden sm:inline">Seret gambar ke sini, atau </span>
                    <button type="button" onClick={() => masukan.current?.click()} className="font-semibold text-green underline underline-offset-2">
                        pilih dari perangkat
                    </button>
                </p>
                <p className="text-xs text-muted-foreground">JPG, PNG, WebP, maksimal 4 MB per file, {MAKS_FILE} file sekali upload.</p>
                <input ref={masukan} type="file" accept="image/*" multiple className="sr-only" onChange={(event) => pilih(event.target.files)} aria-label="Pilih gambar" />
            </div>

            {form.data.files.length > 0 && (
                <div className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <ul className="flex flex-wrap gap-1.5">
                        {form.data.files.map((file, index) => (
                            <li key={`${file.name}-${index}`} className="inline-flex max-w-full items-center gap-1 rounded-full bg-muted py-1 pl-3 pr-1 text-xs">
                                <span className="truncate">{file.name}</span>
                                <button
                                    type="button"
                                    aria-label={`Batal pilih ${file.name}`}
                                    onClick={() =>
                                        form.setData(
                                            'files',
                                            form.data.files.filter((_, posisi) => posisi !== index),
                                        )
                                    }
                                    className="inline-flex size-6 items-center justify-center rounded-full hover:bg-background"
                                >
                                    <X className="size-3.5" />
                                </button>
                            </li>
                        ))}
                    </ul>
                    <Button onClick={unggah} disabled={form.processing} className="shrink-0">
                        <Upload />
                        Upload {form.data.files.length} File
                    </Button>
                </div>
            )}
            {galat && (
                <p className="mt-2 text-sm text-destructive" role="alert">
                    {galat}
                </p>
            )}
        </Card>
    );
}

/** WordPress-style media library: every uploaded image across the platform. */
export default function MediaPage({ media, daftarFolder, filter, totalMedia, totalUkuran }: Props) {
    const [kataKunci, setKataKunci] = useState(filter.q ?? '');
    const [dipilih, setDipilih] = useState<Media | null>(null);
    const [tersalin, setTersalin] = useState(false);
    const [menghapus, setMenghapus] = useState(false);
    const pertama = useRef(true);

    const saring = (perubahan: Partial<Props['filter']>) => {
        const gabungan = { ...filter, q: kataKunci || null, ...perubahan };
        const query = Object.fromEntries(Object.entries(gabungan).filter(([, nilai]) => nilai && nilai !== SEMUA));
        router.get('/superadmin/media', query, { preserveState: true, preserveScroll: true, replace: true, only: ['media', 'filter'] });
    };

    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        const jeda = window.setTimeout(() => saring({ q: kataKunci || null }), 350);

        return () => window.clearTimeout(jeda);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [kataKunci]);

    const salin = (url: string) =>
        navigator.clipboard?.writeText(url).then(() => {
            setTersalin(true);
            toast.success('URL disalin');
            window.setTimeout(() => setTersalin(false), 2000);
        });

    const hapus = (item: Media) =>
        router.delete('/superadmin/media', {
            data: { path: item.path },
            preserveScroll: true,
            onStart: () => setMenghapus(true),
            onFinish: () => setMenghapus(false),
            onSuccess: () => setDipilih(null),
        });

    return (
        <AdminLayout judul="Media" deskripsi={`${angka(totalMedia)} gambar, total ${ukuranFile(totalUkuran)}. Semua foto yang diupload di platform.`}>
            <Head title="Media" />

            <UnggahMedia />

            <Card className="mb-5 p-4">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_14rem]">
                    <div className="space-y-1.5">
                        <Label htmlFor="cari-media">Cari</Label>
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="cari-media"
                                type="search"
                                value={kataKunci}
                                onChange={(event) => setKataKunci(event.target.value)}
                                placeholder="Nama file"
                                className="pl-9"
                            />
                        </div>
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="filter-folder">Folder</Label>
                        <Select value={filter.folder ?? SEMUA} onValueChange={(folder) => saring({ folder })}>
                            <SelectTrigger id="filter-folder">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={SEMUA}>Semua Folder</SelectItem>
                                {daftarFolder.map((folder) => (
                                    <SelectItem key={folder} value={folder}>
                                        {folder}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </Card>

            {media.data.length === 0 ? (
                <Card className="px-5 py-8 text-center text-sm text-muted-foreground">
                    {filter.q || filter.folder ? 'Tidak ada gambar yang cocok.' : 'Belum ada gambar yang diupload.'}
                </Card>
            ) : (
                <Card className="overflow-hidden">
                    <ul className="grid grid-cols-2 gap-3 p-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                        {media.data.map((item) => (
                            <li key={item.path} className="overflow-hidden rounded-lg border border-border bg-card">
                                <button type="button" onClick={() => setDipilih(item)} className="group block w-full text-left">
                                    <div className="aspect-square overflow-hidden bg-muted">
                                        <img src={item.url} alt={item.nama} loading="lazy" className="size-full object-cover transition-transform group-hover:scale-105" />
                                    </div>
                                    <div className="p-2.5">
                                        <div className="truncate text-xs font-semibold">{item.nama}</div>
                                        <div className="mt-1 flex items-center justify-between gap-1 text-xs text-muted-foreground">
                                            <span className="truncate">{item.folder}</span>
                                            {item.dipakai && <span className="size-2 shrink-0 rounded-full bg-primary" title="Dipakai" aria-label="Dipakai" />}
                                        </div>
                                    </div>
                                </button>
                            </li>
                        ))}
                    </ul>
                    <Pagination halaman={media} />
                </Card>
            )}

            <Dialog open={dipilih !== null} onOpenChange={(buka) => !buka && setDipilih(null)}>
                <DialogContent className="sm:max-w-lg">
                    {dipilih && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="break-all pr-6">{dipilih.nama}</DialogTitle>
                                <DialogDescription>
                                    {dipilih.folder}, {ukuranFile(dipilih.ukuran)}, {dipilih.diubah}
                                </DialogDescription>
                            </DialogHeader>
                            <div className="overflow-hidden rounded-lg border border-border bg-muted">
                                <img src={dipilih.url} alt={dipilih.nama} className="mx-auto max-h-72 object-contain" />
                            </div>
                            <div className="flex items-center gap-2">
                                <Input readOnly value={dipilih.url} onFocus={(event) => event.currentTarget.select()} className="text-xs" aria-label="URL gambar" />
                                <Button variant="outline" onClick={() => salin(dipilih.url)} className="shrink-0">
                                    {tersalin ? <Check /> : <Copy />}
                                    {tersalin ? 'Tersalin' : 'Salin URL'}
                                </Button>
                            </div>
                            <div className="flex items-center gap-2 text-sm">
                                <Badge variant={dipilih.dipakai ? 'confirmed' : 'info'}>{dipilih.dipakai ? 'Dipakai' : 'Tidak dipakai'}</Badge>
                                <span className="text-xs text-muted-foreground">
                                    {dipilih.dipakai ? 'Masih dipakai di galeri, blog, lapangan, atau logo tenant, jadi tidak bisa dihapus.' : 'Aman dihapus.'}
                                </span>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setDipilih(null)}>
                                    Tutup
                                </Button>
                                <Button variant="destructive" disabled={dipilih.dipakai || menghapus} onClick={() => hapus(dipilih)}>
                                    <Trash2 />
                                    Hapus Permanen
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
