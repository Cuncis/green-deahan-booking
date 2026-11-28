import { Head, Link, router } from '@inertiajs/react';
import { ExternalLink, ImageOff, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import KonfirmasiDialog from '../../../../components/KonfirmasiDialog';
import AksiKartu from '../../../../components/AksiKartu';
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

/** Compact icon actions for the wide table, so the product name keeps the room. */
function AksiIkon({ item, onHapus }: { item: ProdukBaris; onHapus: () => void }) {
    const kelas = 'size-9 md:size-9';
    return (
        <div className="flex items-center justify-end gap-1.5">
            {item.linkSupplier ? (
                <Button asChild variant="outline" size="icon" className={kelas}>
                    <a href={item.linkSupplier} target="_blank" rel="noopener noreferrer" title="Buka link supplier" aria-label={`Buka link supplier ${item.name}`}>
                        <ExternalLink />
                    </a>
                </Button>
            ) : (
                <span className={`${kelas} inline-flex items-center justify-center text-muted-foreground/40`} title="Link supplier belum diisi">
                    <ExternalLink className="size-4" aria-hidden="true" />
                </span>
            )}
            <Button asChild variant="outline" size="icon" className={kelas}>
                <Link href={item.editUrl} title="Edit produk" aria-label={`Edit ${item.name}`}>
                    <Pencil />
                </Link>
            </Button>
            <Button
                type="button"
                variant="outline"
                size="icon"
                className={`${kelas} text-destructive hover:bg-destructive/10 hover:text-destructive`}
                onClick={onHapus}
                title="Hapus produk"
                aria-label={`Hapus ${item.name}`}
            >
                <Trash2 />
            </Button>
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

    const aksi = (item: ProdukBaris, className?: string) => (
        <AksiKartu
            className={className}
            kiri={
                item.linkSupplier ? (
                    <Button asChild variant="ghost" size="sm">
                        <a href={item.linkSupplier} target="_blank" rel="noopener noreferrer">
                            <ExternalLink />
                            Supplier
                        </a>
                    </Button>
                ) : undefined
            }
            editHref={item.editUrl}
            onHapus={() => setAkanDihapus(item)}
        />
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
                        <div className="hidden xl:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Produk</TableHead>
                                        <TableHead className="text-right">Harga</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="w-36 text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {produk.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="py-3.5">
                                                <div className="flex items-center gap-3.5">
                                                    <Foto src={item.imageUrl} alt={item.name} />
                                                    <div className="min-w-0">
                                                        <a
                                                            href={item.publikUrl}
                                                            target="_blank"
                                                            rel="noreferrer"
                                                            className="line-clamp-2 font-bold leading-snug hover:text-green hover:underline"
                                                            title={item.name}
                                                        >
                                                            {item.name}
                                                        </a>
                                                        <div className="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-muted-foreground">
                                                            <span>{item.kategori}</span>
                                                            <span aria-hidden="true">·</span>
                                                            <span>{item.supplier ?? 'Tanpa supplier'}</span>
                                                            {item.badge && <Badge variant="info">{item.badge}</Badge>}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap py-3.5 text-right tabular-nums">
                                                <div className="font-semibold">
                                                    {rupiah(item.hargaJual)}
                                                    <span className="text-xs font-normal text-muted-foreground">/{item.satuan}</span>
                                                </div>
                                                <div className="text-xs text-muted-foreground">modal {rupiah(item.hargaModal)}</div>
                                                <div className="text-xs font-semibold text-green">untung {rupiah(item.margin)}</div>
                                            </TableCell>
                                            <TableCell className="py-3.5">
                                                <Badge variant={item.aktif ? 'confirmed' : 'info'}>{item.aktif ? 'Dijual' : 'Disembunyikan'}</Badge>
                                            </TableCell>
                                            <TableCell className="py-3.5">
                                                <AksiIkon item={item} onHapus={() => setAkanDihapus(item)} />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <ul className="divide-y divide-cream xl:hidden">
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
                                    {aksi(item, 'mt-3')}
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
