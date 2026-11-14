import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, ImageOff, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import KonfirmasiDialog from '../../../../components/KonfirmasiDialog';
import AdminLayout from '../../../../components/layout/AdminLayout';
import { Badge } from '../../../../components/ui/badge';
import { Button } from '../../../../components/ui/button';
import { Card, CardHeader, CardTitle } from '../../../../components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../../components/ui/table';
import { rupiah } from '../../../../lib/format';

interface ProdukBaris {
    id: number;
    name: string;
    slug: string;
    kategori: string;
    imageUrl: string | null;
    hargaJual: number;
    satuan: string;
    badge: string | null;
    hargaModal: number;
    margin: number;
    supplier: string | null;
    linkSupplier: string | null;
    aktif: boolean;
    editUrl: string;
    hapusUrl: string;
    publikUrl: string;
}

function Foto({ src, alt }: { src: string | null; alt: string }) {
    return (
        <div className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted">
            {src ? <img src={src} alt={alt} className="size-full object-cover" loading="lazy" /> : <ImageOff className="size-5 text-muted-foreground" aria-hidden="true" />}
        </div>
    );
}

/** Shop products with private cost price and supplier link (superadmin only). */
export default function Index({ produk }: { produk: ProdukBaris[] }) {
    const [akanDihapus, setAkanDihapus] = useState<ProdukBaris | null>(null);
    const [menghapus, setMenghapus] = useState(false);

    const hapus = () =>
        akanDihapus &&
        router.delete(akanDihapus.hapusUrl, {
            preserveScroll: true,
            onStart: () => setMenghapus(true),
            onFinish: () => {
                setMenghapus(false);
                setAkanDihapus(null);
            },
        });

    const aksi = (item: ProdukBaris) => (
        <>
            <Button asChild variant="outline" size="sm">
                <Link href={item.editUrl}>
                    <Pencil />
                    Edit
                </Link>
            </Button>
            {item.linkSupplier && (
                <Button asChild variant="ghost" size="sm">
                    <a href={item.linkSupplier} target="_blank" rel="noopener noreferrer">
                        <ExternalLink />
                        Supplier
                    </a>
                </Button>
            )}
            <Button variant="ghost" size="sm" className="text-destructive hover:bg-destructive/10 hover:text-destructive" onClick={() => setAkanDihapus(item)}>
                <Trash2 />
                Hapus
            </Button>
        </>
    );

    return (
        <AdminLayout
            judul="Produk Toko"
            deskripsi="Harga modal dan link supplier hanya terlihat di sini, tidak pernah di halaman pelanggan."
            aksi={
                <Button asChild className="w-full sm:w-auto">
                    <Link href="/superadmin/toko/produk/tambah">
                        <Plus />
                        Tambah Produk
                    </Link>
                </Button>
            }
        >
            <Head title="Produk Toko" />
            <Card className="overflow-hidden">
                <CardHeader className="border-b border-border py-4">
                    <CardTitle className="text-xs uppercase tracking-wide text-green">{produk.length} Produk</CardTitle>
                </CardHeader>
                {produk.length === 0 ? (
                    <p className="px-5 py-6 text-center text-sm text-muted-foreground">Belum ada produk.</p>
                ) : (
                    <>
                        <div className="hidden lg:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Produk</TableHead>
                                        <TableHead className="text-right">Jual</TableHead>
                                        <TableHead className="text-right">Modal</TableHead>
                                        <TableHead className="text-right">Margin</TableHead>
                                        <TableHead>Supplier</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {produk.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <Foto src={item.imageUrl} alt={item.name} />
                                                    <div className="min-w-0">
                                                        <a href={item.publikUrl} target="_blank" rel="noreferrer" className="font-bold hover:text-green hover:underline">
                                                            {item.name}
                                                        </a>
                                                        <div className="text-xs text-muted-foreground">
                                                            {item.kategori}
                                                            {item.badge && `, badge ${item.badge}`}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {rupiah(item.hargaJual)}
                                                <div className="text-xs text-muted-foreground">per {item.satuan}</div>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums text-ink-mid">{rupiah(item.hargaModal)}</TableCell>
                                            <TableCell className="text-right tabular-nums font-semibold text-green">{rupiah(item.margin)}</TableCell>
                                            <TableCell className="text-ink-mid">{item.supplier ?? '-'}</TableCell>
                                            <TableCell>
                                                <Badge variant={item.aktif ? 'confirmed' : 'info'}>{item.aktif ? 'Dijual' : 'Disembunyikan'}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex gap-1">{aksi(item)}</div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <ul className="divide-y divide-cream lg:hidden">
                            {produk.map((item) => (
                                <li key={item.id} className="px-4 py-4">
                                    <div className="flex gap-3">
                                        <Foto src={item.imageUrl} alt={item.name} />
                                        <div className="min-w-0 flex-1">
                                            <div className="font-bold">{item.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {item.kategori}
                                                {item.supplier && `, ${item.supplier}`}
                                            </div>
                                            <div className="mt-1 text-sm">
                                                Jual <strong>{rupiah(item.hargaJual)}</strong>/{item.satuan}, modal {rupiah(item.hargaModal)},{' '}
                                                <span className="font-semibold text-green">untung {rupiah(item.margin)}</span>
                                            </div>
                                        </div>
                                        <Badge variant={item.aktif ? 'confirmed' : 'info'} className="self-start">
                                            {item.aktif ? 'Dijual' : 'Sembunyi'}
                                        </Badge>
                                    </div>
                                    <div className="mt-3 flex flex-wrap gap-2">{aksi(item)}</div>
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
                judul="Hapus produk?"
                deskripsi={`"${akanDihapus?.name ?? ''}" akan dihapus dari toko. Pesanan lama tetap menyimpan nama dan harganya.`}
                labelLanjut="Ya, Hapus"
                memproses={menghapus}
            />
        </AdminLayout>
    );
}
