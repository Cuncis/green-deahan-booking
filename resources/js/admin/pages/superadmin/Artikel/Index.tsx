import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, ImageOff, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import KonfirmasiDialog from '../../../components/KonfirmasiDialog';
import AksiKartu from '../../../components/AksiKartu';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Badge } from '../../../components/ui/badge';
import { Button } from '../../../components/ui/button';
import { Card, CardHeader, CardTitle } from '../../../components/ui/card';
import { Input } from '../../../components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';

interface ArtikelBaris {
    id: number;
    judul: string;
    kategori: string;
    terbit: string;
    aktif: boolean;
    fotoUrl: string | null;
    publikUrl: string;
    editUrl: string;
    hapusUrl: string;
}

interface Props {
    artikel: ArtikelBaris[];
    cari: string | null;
}

function StatusArtikel({ aktif }: { aktif: boolean }) {
    return <Badge variant={aktif ? 'confirmed' : 'info'}>{aktif ? 'Terbit' : 'Draft'}</Badge>;
}

/** Featured image thumbnail, with a placeholder when the article has none. */
function Foto({ src, besar = false }: { src: string | null; besar?: boolean }) {
    const ukuran = besar ? 'size-16' : 'h-12 w-16';
    return (
        <div className={`${ukuran} flex shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted`}>
            {src ? <img src={src} alt="" loading="lazy" className="size-full object-cover" /> : <ImageOff className="size-5 text-muted-foreground" aria-hidden="true" />}
        </div>
    );
}

/** Blog articles shown on the public /blog page: table on wide screens, list below. */
export default function Index({ artikel, cari }: Props) {
    const [kataKunci, setKataKunci] = useState(cari ?? '');
    const [akanDihapus, setAkanDihapus] = useState<ArtikelBaris | null>(null);
    const [menghapus, setMenghapus] = useState(false);
    const pertama = useRef(true);

    // Search as you type, after a short pause.
    useEffect(() => {
        if (pertama.current) {
            pertama.current = false;
            return;
        }
        const jeda = window.setTimeout(
            () => router.get('/superadmin/artikel', kataKunci ? { cari: kataKunci } : {}, { preserveState: true, preserveScroll: true, replace: true, only: ['artikel', 'cari'] }),
            350,
        );

        return () => window.clearTimeout(jeda);
    }, [kataKunci]);

    const hapus = () => {
        if (!akanDihapus) {
            return;
        }
        router.delete(akanDihapus.hapusUrl, {
            preserveScroll: true,
            onStart: () => setMenghapus(true),
            onFinish: () => {
                setMenghapus(false);
                setAkanDihapus(null);
            },
        });
    };

    const tombolAksi = (item: ArtikelBaris, className?: string) => (
        <AksiKartu
            className={className}
            kiri={
                <Button asChild variant="ghost" size="sm">
                    <a href={item.publikUrl} target="_blank" rel="noreferrer">
                        <ExternalLink />
                        Lihat
                    </a>
                </Button>
            }
            editHref={item.editUrl}
            onHapus={() => setAkanDihapus(item)}
        />
    );

    return (
        <AdminLayout
            judul="Blog"
            deskripsi="Kelola artikel yang tampil di halaman /blog."
            aksi={
                <Button asChild className="w-full sm:w-auto">
                    <Link href="/superadmin/artikel/tulis">
                        <Plus />
                        Tulis Artikel
                    </Link>
                </Button>
            }
        >
            <Head title="Blog" />

            <Card className="mb-5 p-4">
                <label htmlFor="cari-artikel" className="sr-only">
                    Cari artikel
                </label>
                <div className="relative sm:max-w-sm">
                    <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="cari-artikel"
                        type="search"
                        value={kataKunci}
                        onChange={(event) => setKataKunci(event.target.value)}
                        placeholder="Cari judul artikel"
                        className="pl-9"
                    />
                </div>
            </Card>

            <Card className="overflow-hidden">
                <CardHeader className="border-b border-border py-4">
                    <CardTitle className="text-xs uppercase tracking-wide text-green">{artikel.length} Artikel</CardTitle>
                </CardHeader>

                {artikel.length === 0 ? (
                    <p className="px-5 py-6 text-center text-sm text-muted-foreground">{cari ? `Tidak ada artikel dengan judul "${cari}".` : 'Belum ada artikel.'}</p>
                ) : (
                    <>
                        <div className="hidden xl:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Artikel</TableHead>
                                        <TableHead className="w-28">Status</TableHead>
                                        <TableHead className="w-36 text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {artikel.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="py-3.5">
                                                <div className="flex items-center gap-3.5">
                                                    <Foto src={item.fotoUrl} />
                                                    <div className="min-w-0">
                                                        <Link href={item.editUrl} className="line-clamp-2 font-bold leading-snug hover:text-green hover:underline" title={item.judul}>
                                                            {item.judul}
                                                        </Link>
                                                        <div className="mt-1 text-xs text-muted-foreground">
                                                            {item.kategori} · {item.terbit}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="py-3.5">
                                                <StatusArtikel aktif={item.aktif} />
                                            </TableCell>
                                            <TableCell className="py-3.5">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    <Button asChild variant="outline" size="icon" className="size-9 md:size-9">
                                                        <a href={item.publikUrl} target="_blank" rel="noreferrer" title="Lihat di blog" aria-label={`Lihat ${item.judul} di blog`}>
                                                            <ExternalLink />
                                                        </a>
                                                    </Button>
                                                    <Button asChild variant="outline" size="icon" className="size-9 md:size-9">
                                                        <Link href={item.editUrl} title="Edit artikel" aria-label={`Edit ${item.judul}`}>
                                                            <Pencil />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        className="size-9 text-destructive hover:bg-destructive/10 hover:text-destructive md:size-9"
                                                        onClick={() => setAkanDihapus(item)}
                                                        title="Hapus artikel"
                                                        aria-label={`Hapus ${item.judul}`}
                                                    >
                                                        <Trash2 />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <ul className="divide-y divide-cream xl:hidden">
                            {artikel.map((item) => (
                                <li key={item.id} className="px-4 py-4">
                                    <div className="flex gap-3">
                                        <Foto src={item.fotoUrl} besar />
                                        <div className="min-w-0 flex-1">
                                            <Link href={item.editUrl} className="line-clamp-2 font-bold">
                                                {item.judul}
                                            </Link>
                                            <div className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                                <StatusArtikel aktif={item.aktif} />
                                                <span>
                                                    {item.kategori}, {item.terbit}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    {tombolAksi(item, 'mt-3')}
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </Card>

            <KonfirmasiDialog
                terbuka={akanDihapus !== null}
                onTutup={() => setAkanDihapus(null)}
                onLanjut={hapus}
                judul="Hapus artikel?"
                deskripsi={`"${akanDihapus?.judul ?? ''}" akan dihapus permanen dari blog.`}
                labelLanjut="Ya, Hapus"
                memproses={menghapus}
            />
        </AdminLayout>
    );
}
