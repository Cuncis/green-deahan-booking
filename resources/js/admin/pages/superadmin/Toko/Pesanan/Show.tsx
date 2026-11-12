import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Copy, ExternalLink } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import Field from '../../../../components/form/Field';
import KonfirmasiDialog from '../../../../components/KonfirmasiDialog';
import AdminLayout from '../../../../components/layout/AdminLayout';
import { Badge } from '../../../../components/ui/badge';
import { Button } from '../../../../components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '../../../../components/ui/card';
import { Input, Textarea } from '../../../../components/ui/input';
import { rupiah } from '../../../../lib/format';
import { varianStatusPesanan } from './Index';

interface Pesanan {
    id: number;
    kode: string;
    status: string;
    statusLabel: string;
    pelanggan: { nama: string; hp: string; email: string; alamat: string; kota: string; provinsi: string; kodePos: string; alamatLengkap: string };
    items: { id: number; nama: string; qty: number; harga: number; modal: number; linkSupplier: string | null }[];
    subtotal: number;
    ongkir: number;
    total: number;
    totalModal: number;
    untung: number;
    dibuat: string;
    dibayar: string | null;
    refSupplier: string | null;
    kurir: string | null;
    resi: string | null;
    catatan: string | null;
    mayarId: string | null;
    langkahBerikutnya: { nilai: string; label: string }[];
    statusUrl: string;
    catatanUrl: string;
    publikUrl: string;
}

const salin = (teks: string, pesan: string) => navigator.clipboard?.writeText(teks).then(() => toast.success(pesan));

function Baris({ label, nilai, tebal }: { label: string; nilai: string; tebal?: boolean }) {
    return (
        <div className={`flex justify-between gap-3 py-1.5 text-sm ${tebal ? 'font-bold' : ''}`}>
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right tabular-nums">{nilai}</span>
        </div>
    );
}

/** Order detail: customer, items with supplier links, status flow, profit, admin notes. */
export default function Show({ pesanan, kembaliUrl }: { pesanan: Pesanan; kembaliUrl: string }) {
    const lanjut = pesanan.langkahBerikutnya.find((langkah) => langkah.nilai !== 'cancelled');
    const bisaBatal = pesanan.langkahBerikutnya.some((langkah) => langkah.nilai === 'cancelled');
    const [konfirmasiBatal, setKonfirmasiBatal] = useState(false);

    const status = useForm({ status: lanjut?.nilai ?? '', supplier_order_ref: pesanan.refSupplier ?? '', courier: pesanan.kurir ?? '', tracking_number: pesanan.resi ?? '' });
    const catatan = useForm({ admin_notes: pesanan.catatan ?? '' });

    const ubahStatus = (event?: FormEvent, tujuan?: string) => {
        event?.preventDefault();
        status.transform((data) => ({ ...data, status: tujuan ?? data.status }));
        status.post(pesanan.statusUrl, { preserveScroll: true, onFinish: () => setKonfirmasiBatal(false) });
    };

    return (
        <AdminLayout judul={`Pesanan ${pesanan.kode}`} deskripsi={`Dipesan ${pesanan.dibuat}${pesanan.dibayar ? `, dibayar ${pesanan.dibayar}` : ''}`} aksi={<Badge variant={varianStatusPesanan(pesanan.status)}>{pesanan.statusLabel}</Badge>}>
            <Head title={`Pesanan ${pesanan.kode}`} />
            <Link href={kembaliUrl} className="-mt-2 mb-4 inline-flex min-h-10 items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft className="size-4" />
                Pesanan Toko
            </Link>

            <div className="grid gap-5 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] lg:items-start">
                <div className="space-y-5">
                    <Card>
                        <CardHeader className="flex-row items-start justify-between gap-3">
                            <div>
                                <CardTitle>Kirim ke</CardTitle>
                                <CardDescription>Salin alamat untuk diisi di pesanan supplier.</CardDescription>
                            </div>
                            <Button variant="outline" size="sm" onClick={() => salin(pesanan.pelanggan.alamatLengkap, 'Alamat disalin')}>
                                <Copy />
                                Salin alamat
                            </Button>
                        </CardHeader>
                        <div className="whitespace-pre-line px-5 pb-5 text-sm leading-relaxed">
                            <strong>{pesanan.pelanggan.nama}</strong>
                            {`\n${pesanan.pelanggan.hp}\n${pesanan.pelanggan.alamat}\n${pesanan.pelanggan.kota}, ${pesanan.pelanggan.provinsi} ${pesanan.pelanggan.kodePos}`}
                            <div className="mt-2 text-muted-foreground">{pesanan.pelanggan.email}</div>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Barang</CardTitle>
                        </CardHeader>
                        <ul className="divide-y divide-cream border-t border-border">
                            {pesanan.items.map((item) => (
                                <li key={item.id} className="flex flex-wrap items-center justify-between gap-3 px-5 py-3.5">
                                    <div>
                                        <div className="font-semibold">
                                            {item.qty} x {item.nama}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            Jual {rupiah(item.harga)}, modal {rupiah(item.modal)} per barang
                                        </div>
                                    </div>
                                    {item.linkSupplier ? (
                                        <Button asChild variant="outline" size="sm">
                                            <a href={item.linkSupplier} target="_blank" rel="noopener noreferrer">
                                                <ExternalLink />
                                                Buka link supplier
                                            </a>
                                        </Button>
                                    ) : (
                                        <span className="text-xs text-muted-foreground">Link supplier belum diisi</span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Catatan admin</CardTitle>
                        </CardHeader>
                        <form onSubmit={(event) => (event.preventDefault(), catatan.put(pesanan.catatanUrl, { preserveScroll: true }))} className="grid gap-3 px-5 pb-5">
                            <Textarea rows={3} value={catatan.data.admin_notes} onChange={(event) => catatan.setData('admin_notes', event.target.value)} aria-label="Catatan admin" />
                            <Button type="submit" variant="outline" className="justify-self-start" disabled={catatan.processing}>
                                Simpan catatan
                            </Button>
                        </form>
                    </Card>
                </div>

                <div className="space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Proses Pesanan</CardTitle>
                            <CardDescription>
                                {pesanan.refSupplier && <>Ref supplier: {pesanan.refSupplier}. </>}
                                {pesanan.resi && (
                                    <>
                                        Resi {pesanan.kurir} {pesanan.resi}.
                                    </>
                                )}
                            </CardDescription>
                        </CardHeader>
                        <div className="px-5 pb-5">
                            {lanjut ? (
                                <form onSubmit={ubahStatus} className="grid gap-3">
                                    {lanjut.nilai === 'ordered_to_supplier' && (
                                        <Field id="ref" label="Nomor pesanan di supplier" error={status.errors.supplier_order_ref} wajib>
                                            <Input id="ref" value={status.data.supplier_order_ref} onChange={(event) => status.setData('supplier_order_ref', event.target.value)} />
                                        </Field>
                                    )}
                                    {lanjut.nilai === 'shipped' && (
                                        <div className="grid grid-cols-2 gap-3">
                                            <Field id="kurir" label="Kurir" error={status.errors.courier} wajib>
                                                <Input id="kurir" value={status.data.courier} onChange={(event) => status.setData('courier', event.target.value)} placeholder="JNE, J&T, SiCepat" />
                                            </Field>
                                            <Field id="resi" label="Nomor resi" error={status.errors.tracking_number} wajib>
                                                <Input id="resi" value={status.data.tracking_number} onChange={(event) => status.setData('tracking_number', event.target.value)} />
                                            </Field>
                                        </div>
                                    )}
                                    {status.errors.status && <p className="text-sm text-destructive">{status.errors.status}</p>}
                                    <Button type="submit" disabled={status.processing}>
                                        Tandai {lanjut.label}
                                    </Button>
                                    {lanjut.nilai === 'shipped' && <p className="text-xs text-muted-foreground">Pelanggan otomatis menerima email berisi nomor resi.</p>}
                                </form>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    {pesanan.status === 'pending_payment' ? 'Menunggu pembayaran dari pelanggan.' : 'Tidak ada langkah lanjutan untuk pesanan ini.'}
                                </p>
                            )}
                            {bisaBatal && (
                                <Button variant="ghost" size="sm" className="mt-3 text-destructive hover:bg-destructive/10 hover:text-destructive" onClick={() => setKonfirmasiBatal(true)}>
                                    Batalkan pesanan
                                </Button>
                            )}
                        </div>
                    </Card>

                    <Card className="p-5">
                        <CardTitle className="mb-2">Untung</CardTitle>
                        <Baris label="Total dibayar" nilai={rupiah(pesanan.total)} />
                        <Baris label="Ongkir" nilai={`-${rupiah(pesanan.ongkir)}`} />
                        <Baris label="Modal barang" nilai={`-${rupiah(pesanan.totalModal)}`} />
                        <div className="mt-1 border-t border-border pt-1">
                            <Baris label="Untung" nilai={rupiah(pesanan.untung)} tebal />
                        </div>
                        <div className="mt-3 flex flex-wrap gap-2 text-xs">
                            <a href={pesanan.publikUrl} target="_blank" rel="noreferrer" className="inline-flex min-h-10 items-center gap-1 text-muted-foreground underline">
                                Halaman pelanggan
                                <ExternalLink className="size-3" />
                            </a>
                            {pesanan.mayarId && (
                                <button type="button" className="inline-flex min-h-10 items-center gap-1 text-muted-foreground underline" onClick={() => salin(pesanan.mayarId!, 'ID Mayar disalin')}>
                                    ID Mayar
                                    <Copy className="size-3" />
                                </button>
                            )}
                        </div>
                    </Card>
                </div>
            </div>

            <KonfirmasiDialog
                terbuka={konfirmasiBatal}
                onTutup={() => setKonfirmasiBatal(false)}
                onLanjut={() => ubahStatus(undefined, 'cancelled')}
                judul="Batalkan pesanan?"
                deskripsi="Pengembalian dana (refund) ke pelanggan dilakukan manual di luar sistem ini."
                labelLanjut="Ya, Batalkan"
                memproses={status.processing}
            />
        </AdminLayout>
    );
}
