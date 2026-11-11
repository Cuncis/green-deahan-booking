import { Head, Link, router } from '@inertiajs/react';
import { ImageOff, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import KonfirmasiDialog from '../../../components/KonfirmasiDialog';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Badge } from '../../../components/ui/badge';
import { Button } from '../../../components/ui/button';
import { Card } from '../../../components/ui/card';
import { Label } from '../../../components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../components/ui/select';

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
    pilihanKategori: { nilai: string; label: string }[];
}

const SEMUA = 'semua';

/** Project portfolio shown on the public /galeri page. */
export default function Index({ item, filterKategori, pilihanKategori }: Props) {
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

            <Card className="mb-5 p-4">
                <div className="space-y-1.5 sm:w-64">
                    <Label htmlFor="filter-kategori">Kategori</Label>
                    <Select value={filterKategori ?? SEMUA} onValueChange={saring}>
                        <SelectTrigger id="filter-kategori">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={SEMUA}>Semua Kategori</SelectItem>
                            {pilihanKategori.map((kategori) => (
                                <SelectItem key={kategori.nilai} value={kategori.nilai}>
                                    {kategori.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </Card>

            {item.length === 0 ? (
                <Card className="px-5 py-8 text-center text-sm text-muted-foreground">{filterKategori ? 'Belum ada item di kategori ini.' : 'Belum ada item galeri.'}</Card>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {item.map((foto) => (
                        <Card key={foto.id} className="flex flex-col overflow-hidden">
                            <div className="aspect-[16/10] overflow-hidden bg-muted">
                                {foto.fotoUrl ? (
                                    <img src={foto.fotoUrl} alt={foto.judul} loading="lazy" className="size-full object-cover" />
                                ) : (
                                    <div className="flex size-full items-center justify-center text-muted-foreground">
                                        <ImageOff className="size-8" />
                                    </div>
                                )}
                            </div>
                            <div className="flex flex-1 flex-col p-4">
                                <div className="mb-1 flex items-start justify-between gap-2">
                                    <span className="font-display text-sm font-semibold">{foto.judul}</span>
                                    <Badge variant={foto.tampil ? 'confirmed' : 'cancelled'}>{foto.tampil ? 'Tampil' : 'Sembunyi'}</Badge>
                                </div>
                                <p className="mb-4 text-xs text-muted-foreground">
                                    {foto.kategori}, {foto.kota}
                                    {foto.besar && ', tampil besar'}
                                </p>
                                <div className="mt-auto grid grid-cols-2 gap-2">
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={foto.editUrl}>
                                            <Pencil />
                                            Edit
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        onClick={() => setAkanDihapus(foto)}
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
