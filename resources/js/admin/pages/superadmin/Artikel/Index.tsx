import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import KonfirmasiDialog from '../../../components/KonfirmasiDialog';
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

/** Blog articles shown on the public /blog page. */
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

    const tombolAksi = (item: ArtikelBaris) => (
        <>
            <Button asChild variant="outline" size="sm">
                <Link href={item.editUrl}>
                    <Pencil />
                    Edit
                </Link>
            </Button>
            <Button asChild variant="ghost" size="sm">
                <a href={item.publikUrl} target="_blank" rel="noreferrer">
                    <ExternalLink />
                    Lihat
                </a>
            </Button>
            <Button variant="ghost" size="sm" className="text-destructive hover:bg-destructive/10 hover:text-destructive" onClick={() => setAkanDihapus(item)}>
                <Trash2 />
                Hapus
            </Button>
        </>
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
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Judul</TableHead>
                                        <TableHead>Kategori</TableHead>
                                        <TableHead>Terbit</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {artikel.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="max-w-md">
                                                <Link href={item.editUrl} className="font-bold hover:text-green hover:underline">
                                                    {item.judul}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="text-ink-mid">{item.kategori}</TableCell>
                                            <TableCell className="whitespace-nowrap text-ink-mid">{item.terbit}</TableCell>
                                            <TableCell>
                                                <StatusArtikel aktif={item.aktif} />
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex gap-1">{tombolAksi(item)}</div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <ul className="divide-y divide-cream md:hidden">
                            {artikel.map((item) => (
                                <li key={item.id} className="px-4 py-4">
                                    <div className="flex gap-3">
                                        {item.fotoUrl && <img src={item.fotoUrl} alt="" loading="lazy" className="size-16 shrink-0 rounded-lg object-cover" />}
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
                                    <div className="mt-3 grid grid-cols-3 gap-2">{tombolAksi(item)}</div>
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
