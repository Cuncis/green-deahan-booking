import { Head, Link, router } from '@inertiajs/react';
import ChipFilter from '../../../components/ChipFilter';
import { ImageOff, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import KonfirmasiDialog from '../../../components/KonfirmasiDialog';
import AksiKartu from '../../../components/AksiKartu';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Badge } from '../../../components/ui/badge';
import { Button } from '../../../components/ui/button';
import { Card, CardHeader, CardTitle } from '../../../components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table';

interface ItemGaleri {
    id: number;
    judul: string;
    kategori: string;
    kota: string;
    fotoUrl: string | null;
    tampil: boolean;
    besar: boolean;
    editUrl: string;
    hapusUrl: string;
}

interface Props {
    item: ItemGaleri[];
    filterKategori: string | null;
    pilihanKategori: { nilai: string; label: string; jumlah: number }[];
    totalItem: number;
}

const SEMUA = 'semua';

function StatusGaleri({ tampil }: { tampil: boolean }) {
    return (
        <Badge variant={tampil ? 'confirmed' : 'cancelled'} className="shrink-0">
            {tampil ? 'Tampil' : 'Sembunyi'}
        </Badge>
    );
}

/** Project photo thumbnail, with a placeholder when there is none. */
function Foto({ foto }: { foto: ItemGaleri }) {
    return (
        <div className="flex h-14 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted">
            {foto.fotoUrl ? <img src={foto.fotoUrl} alt="" loading="lazy" className="size-full object-cover" /> : <ImageOff className="size-5 text-muted-foreground" aria-hidden="true" />}
        </div>
    );
}

/** Project portfolio shown on the public /galeri page: table on wide screens, list below. */
export default function Index({ item, filterKategori, pilihanKategori, totalItem }: Props) {
    const [akanDihapus, setAkanDihapus] = useState<ItemGaleri | null>(null);
    const [menghapus, setMenghapus] = useState(false);

    const saring = (kategori: string) => router.get('/superadmin/galeri', kategori === SEMUA ? {} : { kategori }, { preserveState: true, preserveScroll: true, replace: true });

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

    return (
        <AdminLayout
            judul="Galeri Proyek"
            deskripsi="Kelola portofolio yang tampil di halaman /galeri."
            aksi={
                <Button asChild className="w-full sm:w-auto">
                    <Link href="/superadmin/galeri/tambah">
                        <Plus />
                        Tambah Item
                    </Link>
                </Button>
            }
        >
            <Head title="Galeri" />

            <ChipFilter
                pilihan={[{ nilai: SEMUA, label: 'Semua', jumlah: totalItem }, ...pilihanKategori]}
                aktif={filterKategori ?? SEMUA}
                onPilih={saring}
                label="Filter kategori galeri"
            />

            {item.length === 0 ? (
                <Card className="px-5 py-8 text-center text-sm text-muted-foreground">{filterKategori ? 'Belum ada item di kategori ini.' : 'Belum ada item galeri.'}</Card>
            ) : (
                <Card className="overflow-hidden">
                    <CardHeader className="border-b border-border py-4">
                        <CardTitle className="text-xs uppercase tracking-wide text-green">{item.length} Item</CardTitle>
                    </CardHeader>
                    <div className="hidden xl:block">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Proyek</TableHead>
                                    <TableHead className="w-28">Status</TableHead>
                                    <TableHead className="w-28 text-right">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {item.map((foto) => (
                                    <TableRow key={foto.id}>
                                        <TableCell className="py-3">
                                            <div className="flex items-center gap-4">
                                                <Foto foto={foto} />
                                                <div className="min-w-0">
                                                    <Link href={foto.editUrl} className="line-clamp-2 font-bold leading-snug hover:text-green hover:underline" title={foto.judul}>
                                                        {foto.judul}
                                                    </Link>
                                                    <div className="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-muted-foreground">
                                                        <span>{foto.kategori}</span>
                                                        <span aria-hidden="true">·</span>
                                                        <span>{foto.kota}</span>
                                                        {foto.besar && <Badge variant="info">Tampil besar</Badge>}
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="py-3">
                                            <StatusGaleri tampil={foto.tampil} />
                                        </TableCell>
                                        <TableCell className="py-3">
                                            <div className="flex items-center justify-end gap-1.5">
                                                <Button asChild variant="outline" size="icon" className="size-9 md:size-9">
                                                    <Link href={foto.editUrl} title="Edit item" aria-label={`Edit ${foto.judul}`}>
                                                        <Pencil />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    className="size-9 text-destructive hover:bg-destructive/10 hover:text-destructive md:size-9"
                                                    onClick={() => setAkanDihapus(foto)}
                                                    title="Hapus item"
                                                    aria-label={`Hapus ${foto.judul}`}
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
                        {item.map((foto) => (
                            <li key={foto.id} className="px-4 py-4">
                                <div className="flex gap-3">
                                    <Foto foto={foto} />
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-start justify-between gap-2">
                                            <Link href={foto.editUrl} className="line-clamp-2 font-bold leading-snug">
                                                {foto.judul}
                                            </Link>
                                            <StatusGaleri tampil={foto.tampil} />
                                        </div>
                                        <div className="mt-1 text-xs text-muted-foreground">
                                            {foto.kategori} · {foto.kota}
                                        </div>
                                        {foto.besar && (
                                            <Badge variant="info" className="mt-1.5">
                                                Tampil besar
                                            </Badge>
                                        )}
                                    </div>
                                </div>
                                <AksiKartu className="mt-3" editHref={foto.editUrl} onHapus={() => setAkanDihapus(foto)} />
                            </li>
                        ))}
                    </ul>
                </Card>
            )}

            <KonfirmasiDialog
                terbuka={akanDihapus !== null}
                onTutup={() => setAkanDihapus(null)}
                onLanjut={hapus}
                judul="Hapus item galeri?"
                deskripsi={`"${akanDihapus?.judul ?? ''}" dan fotonya akan dihapus permanen.`}
                labelLanjut="Ya, Hapus"
                memproses={menghapus}
            />
        </AdminLayout>
    );
}
