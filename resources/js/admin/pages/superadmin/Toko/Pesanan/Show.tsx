import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Copy, ExternalLink, Plus, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import Field from '../../../../components/form/Field';
import KonfirmasiDialog from '../../../../components/KonfirmasiDialog';
import AdminLayout from '../../../../components/layout/AdminLayout';
import { Badge } from '../../../../components/ui/badge';
import { Button } from '../../../../components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '../../../../components/ui/card';
import { Input, Textarea } from '../../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../../components/ui/select';
import { rupiah } from '../../../../lib/format';
import { varianStatusPesanan } from './Index';

interface TemplatePesan {
    kunci: string;
    label: string;
    subjek: string;
    isi: string;
}

interface RiwayatPesan {
    id: number;
    saluran: 'email' | 'whatsapp';
    template: string;
    subjek: string | null;
    isi: string;
    oleh: string | null;
    waktu: string;
}

/** Template that fits the order's current status, so the common case needs no choosing. */
const TEMPLATE_STATUS: Record<string, string> = { pending_payment: 'menunggu', paid: 'diterima', ordered_to_supplier: 'diproses', shipped: 'dikirim', completed: 'selesai', cancelled: 'dibatalkan' };

/**
 * Manual status update to the customer: pick a template filled from the order,
 * edit it, then send by email (sent by the system) or WhatsApp (opens wa.me,
 * the admin presses send there). Every send is logged below.
 */
function KabariPelanggan({ pesanan }: { pesanan: Pesanan }) {
    const awal = pesanan.templatePesan.find((item) => item.kunci === (TEMPLATE_STATUS[pesanan.status] ?? 'bebas')) ?? pesanan.templatePesan[0];
    const form = useForm({ channel: 'email', template: awal.kunci, subject: awal.subjek, body: awal.isi });

    const pilihTemplate = (kunci: string) => {
        const dipilih = pesanan.templatePesan.find((item) => item.kunci === kunci);
        if (dipilih) form.setData({ ...form.data, template: kunci, subject: dipilih.subjek, body: dipilih.isi });
    };

    const kirimEmail = () => {
        form.transform((data) => ({ ...data, channel: 'email' }));
        form.post(pesanan.pesanUrl, { preserveScroll: true });
    };

    const bukaWhatsApp = () => {
        if (!pesanan.waNomor) return;
        window.open(`https://wa.me/${pesanan.waNomor}?text=${encodeURIComponent(form.data.body)}`, '_blank', 'noopener');
        form.transform((data) => ({ ...data, channel: 'whatsapp', subject: '' }));
        form.post(pesanan.pesanUrl, { preserveScroll: true });
    };

    const kosong = form.data.body.trim() === '';

    return (
        <Card>
            <CardHeader>
                <CardTitle>Kabari pelanggan</CardTitle>
                <CardDescription>Pilih template, edit kalau perlu, lalu kirim lewat email atau WhatsApp.</CardDescription>
            </CardHeader>
            {/* grid-cols-1 = minmax(0, 1fr): long unbroken text (status links) must not widen the fields. */}
            <div className="grid grid-cols-1 gap-3 px-5 pb-5">
                <Field id="template-pesan" label="Template">
                    <Select value={form.data.template} onValueChange={pilihTemplate}>
                        <SelectTrigger id="template-pesan">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {pesanan.templatePesan.map((item) => (
                                <SelectItem key={item.kunci} value={item.kunci}>
                                    {item.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>
                <Field id="subjek-pesan" label="Subjek (email)" error={form.errors.subject}>
                    <Input id="subjek-pesan" value={form.data.subject} maxLength={150} onChange={(event) => form.setData('subject', event.target.value)} />
                </Field>
                <Field id="isi-pesan" label="Isi pesan" error={form.errors.body}>
                    <Textarea id="isi-pesan" rows={10} value={form.data.body} maxLength={2000} onChange={(event) => form.setData('body', event.target.value)} />
                </Field>
                <div className="flex flex-wrap gap-2">
                    <Button type="button" disabled={form.processing || kosong} onClick={kirimEmail}>
                        Kirim email
                    </Button>
                    <Button type="button" variant="outline" disabled={form.processing || kosong || !pesanan.waNomor} onClick={bukaWhatsApp}>
                        Buka WhatsApp
                    </Button>
                </div>
                <p className="text-xs text-muted-foreground">
                    Email ke {pesanan.pelanggan.email}.{' '}
                    {pesanan.waNomor ? 'WhatsApp terbuka dengan pesan siap kirim, tekan kirim di aplikasinya.' : 'Nomor HP pelanggan tidak valid untuk WhatsApp.'}
                </p>

                {pesanan.riwayatPesan.length > 0 && (
                    <div className="border-t border-border pt-3">
                        <p className="mb-2 text-sm font-medium">Riwayat kabar</p>
                        <ul className="grid grid-cols-1 gap-2 text-sm">
                            {pesanan.riwayatPesan.map((item) => (
                                <li key={item.id} className="min-w-0 rounded-md bg-muted/50 px-3 py-2">
                                    <details>
                                        <summary className="cursor-pointer">
                                            <strong>{item.saluran === 'email' ? 'Email' : 'WhatsApp'}</strong>, {item.template}
                                            <span className="block text-xs text-muted-foreground">
                                                {item.waktu}
                                                {item.oleh && `, oleh ${item.oleh}`}
                                            </span>
                                        </summary>
                                        <p className="mt-2 whitespace-pre-line text-xs text-muted-foreground [overflow-wrap:anywhere]">{item.isi}</p>
                                    </details>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </Card>
    );
}

interface Paket {
    id: number;
    kurir: string;
    resi: string;
    dikirimDari: string | null;
    hapusUrl: string;
}

interface IsianPaket {
    courier: string;
    tracking_number: string;
    shipped_from: string;
}

const PAKET_KOSONG: IsianPaket = { courier: '', tracking_number: '', shipped_from: '' };

/** One parcel row: courier, tracking number and the branch that shipped it. */
function BarisPaket({
    nomor,
    nilai,
    galat,
    onUbah,
    onHapus,
}: {
    nomor: number;
    nilai: IsianPaket;
    galat: Record<string, string>;
    onUbah: (kolom: keyof IsianPaket, nilai: string) => void;
    onHapus?: () => void;
}) {
    return (
        <fieldset className="grid gap-3 rounded-md border border-border p-3">
            <legend className="flex items-center gap-2 px-1 text-xs font-semibold text-muted-foreground">
                Paket {nomor}
                {onHapus && (
                    <button type="button" className="inline-flex min-h-8 items-center gap-1 font-normal text-destructive underline" onClick={onHapus}>
                        <X className="size-3" />
                        Hapus
                    </button>
                )}
            </legend>
            <div className="grid grid-cols-2 gap-3">
                <Field id={`kurir-${nomor}`} label="Kurir" error={galat.courier} wajib>
                    <Input id={`kurir-${nomor}`} value={nilai.courier} onChange={(event) => onUbah('courier', event.target.value)} placeholder="JNE, J&T, SiCepat" />
                </Field>
                <Field id={`resi-${nomor}`} label="Nomor resi" error={galat.tracking_number} wajib>
                    <Input id={`resi-${nomor}`} value={nilai.tracking_number} onChange={(event) => onUbah('tracking_number', event.target.value)} />
                </Field>
            </div>
            <Field id={`dari-${nomor}`} label="Dikirim dari (cabang/kota)" error={galat.shipped_from}>
                <Input id={`dari-${nomor}`} value={nilai.shipped_from} onChange={(event) => onUbah('shipped_from', event.target.value)} placeholder="RANK Sports Bekasi" />
            </Field>
        </fieldset>
    );
}

/** Adds one more parcel to an order that is already marked shipped. */
function TambahResi({ url }: { url: string }) {
    const form = useForm<IsianPaket>({ ...PAKET_KOSONG });

    const kirim = (event: FormEvent) => {
        event.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <form onSubmit={kirim} className="mt-4 grid gap-3 border-t border-border pt-4">
            <p className="text-sm font-medium">Tambah paket lain</p>
            <BarisPaket nomor={0} nilai={form.data} galat={form.errors} onUbah={(kolom, nilai) => form.setData(kolom, nilai)} />
            <Button type="submit" variant="outline" disabled={form.processing}>
                Simpan paket dan kabari pelanggan
            </Button>
        </form>
    );
}

interface Pesanan {
    id: number;
    kode: string;
    status: string;
    statusLabel: string;
    pelanggan: {
        nama: string;
        hp: string;
        email: string;
        alamat: string;
        kelurahan: string | null;
        kecamatan: string | null;
        kota: string;
        provinsi: string;
        kodePos: string;
        alamatLengkap: string;
    };
    items: { id: number; nama: string; varian: string | null; qty: number; harga: number; modal: number; linkSupplier: string | null }[];
    subtotal: number;
    diskon: number;
    kodePromo: string | null;
    ongkir: number;
    layanan: string | null;
    ongkirDarurat: boolean;
    total: number;
    totalModal: number;
    untung: number;
    dibuat: string;
    dibayar: string | null;
    refSupplier: string | null;
    pengiriman: Paket[];
    resiUrl: string;
    catatan: string | null;
    alasanBatal: string | null;
    templatePesan: TemplatePesan[];
    waNomor: string | null;
    pesanUrl: string;
    riwayatPesan: RiwayatPesan[];
    statusRefund: 'diproses' | 'selesai' | null;
    refundUrl: string;
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

    const status = useForm({ status: lanjut?.nilai ?? '', supplier_order_ref: pesanan.refSupplier ?? '', cancel_reason: '', pengiriman: [{ ...PAKET_KOSONG }] as IsianPaket[] });
    const galatStatus: Record<string, string> = status.errors;
    const galatPaket = (indeks: number) => ({
        courier: galatStatus[`pengiriman.${indeks}.courier`],
        tracking_number: galatStatus[`pengiriman.${indeks}.tracking_number`],
        shipped_from: galatStatus[`pengiriman.${indeks}.shipped_from`],
    });
    const ubahPaket = (indeks: number, kolom: keyof IsianPaket, nilai: string) =>
        status.setData(
            'pengiriman',
            status.data.pengiriman.map((paket, urutan) => (urutan === indeks ? { ...paket, [kolom]: nilai } : paket)),
        );
    const bisaTambahResi = pesanan.status === 'shipped';
    const hapusResi = (url: string) => router.delete(url, { preserveScroll: true });
    const catatan = useForm({ admin_notes: pesanan.catatan ?? '' });

    const ubahStatus = (event?: FormEvent, tujuan?: string) => {
        event?.preventDefault();
        // Send only the fields the target status needs, so hidden empty inputs can never block it.
        status.transform((data) => {
            const target = tujuan ?? data.status;
            return {
                status: target,
                ...(target === 'ordered_to_supplier' ? { supplier_order_ref: data.supplier_order_ref } : {}),
                ...(target === 'cancelled' ? { cancel_reason: data.cancel_reason } : {}),
                ...(target === 'shipped' ? { pengiriman: data.pengiriman } : {}),
            };
        });
        status.post(pesanan.statusUrl, { preserveScroll: true, onSuccess: () => setKonfirmasiBatal(false) });
    };

    return (
        <AdminLayout
            judul={`Pesanan ${pesanan.kode}`}
            deskripsi={`Dipesan ${pesanan.dibuat}${pesanan.dibayar ? `, dibayar ${pesanan.dibayar}` : ''}`}
            aksi={<Badge variant={varianStatusPesanan(pesanan.status)}>{pesanan.statusLabel}</Badge>}
        >
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
                            {`\n${pesanan.pelanggan.hp}\n${pesanan.pelanggan.alamat}`}
                            {pesanan.pelanggan.kelurahan ? `\nKel. ${pesanan.pelanggan.kelurahan}` : ''}
                            {pesanan.pelanggan.kecamatan ? `\nKec. ${pesanan.pelanggan.kecamatan}` : ''}
                            {`\n${pesanan.pelanggan.kota}, ${pesanan.pelanggan.provinsi} ${pesanan.pelanggan.kodePos}`}
                            <div className="mt-2 text-muted-foreground">{pesanan.pelanggan.email}</div>
                            {pesanan.layanan && <div className="mt-1 text-muted-foreground">Kurir pilihan pelanggan: {pesanan.layanan}</div>}
                            {pesanan.ongkirDarurat && (
                                <div className="mt-2 rounded-md bg-muted/50 px-3 py-2 text-xs text-muted-foreground">
                                    Ongkir tetap dipakai karena tarif Biteship gagal diambil saat checkout. Cek ongkir sebenarnya di Shopee, dan pilih kurir sendiri saat memesan ke
                                    supplier.
                                </div>
                            )}
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
                                            {item.varian && ` (${item.varian})`}
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
                            <Textarea
                                rows={3}
                                value={catatan.data.admin_notes}
                                onChange={(event) => catatan.setData('admin_notes', event.target.value)}
                                aria-label="Catatan admin"
                            />
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
                            <CardDescription>{pesanan.refSupplier && <>Ref supplier: {pesanan.refSupplier}. </>}</CardDescription>
                        </CardHeader>
                        <div className="px-5 pb-5">
                            {pesanan.pengiriman.length > 0 && (
                                <ul className="mb-4 grid gap-2 text-sm">
                                    {pesanan.pengiriman.map((paket, indeks) => (
                                        <li key={paket.id} className="flex items-start justify-between gap-3 rounded-md bg-muted/50 px-3 py-2">
                                            <span>
                                                <strong>Paket {indeks + 1}:</strong> {paket.kurir} {paket.resi}
                                                {paket.dikirimDari && <span className="block text-xs text-muted-foreground">Dari {paket.dikirimDari}</span>}
                                            </span>
                                            {bisaTambahResi && pesanan.pengiriman.length > 1 && (
                                                <button
                                                    type="button"
                                                    className="inline-flex min-h-10 items-center text-xs text-destructive underline"
                                                    onClick={() => hapusResi(paket.hapusUrl)}
                                                >
                                                    Hapus
                                                </button>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                            {lanjut ? (
                                <form onSubmit={ubahStatus} className="grid gap-3">
                                    {lanjut.nilai === 'ordered_to_supplier' && (
                                        <Field id="ref" label="Nomor pesanan di supplier" error={status.errors.supplier_order_ref} wajib>
                                            <Input id="ref" value={status.data.supplier_order_ref} onChange={(event) => status.setData('supplier_order_ref', event.target.value)} />
                                        </Field>
                                    )}
                                    {lanjut.nilai === 'shipped' && (
                                        <>
                                            {galatStatus.pengiriman && <p className="text-sm text-destructive">{galatStatus.pengiriman}</p>}
                                            {status.data.pengiriman.map((paket, indeks) => (
                                                <BarisPaket
                                                    key={indeks}
                                                    nomor={indeks + 1}
                                                    nilai={paket}
                                                    galat={galatPaket(indeks)}
                                                    onUbah={(kolom, nilai) => ubahPaket(indeks, kolom, nilai)}
                                                    onHapus={
                                                        status.data.pengiriman.length > 1
                                                            ? () =>
                                                                  status.setData(
                                                                      'pengiriman',
                                                                      status.data.pengiriman.filter((_, urutan) => urutan !== indeks),
                                                                  )
                                                            : undefined
                                                    }
                                                />
                                            ))}
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                className="justify-self-start"
                                                disabled={status.data.pengiriman.length >= 10}
                                                onClick={() => status.setData('pengiriman', [...status.data.pengiriman, { ...PAKET_KOSONG }])}
                                            >
                                                <Plus />
                                                Tambah paket
                                            </Button>
                                        </>
                                    )}
                                    {status.errors.status && <p className="text-sm text-destructive">{status.errors.status}</p>}
                                    <Button type="submit" disabled={status.processing}>
                                        Tandai {lanjut.label}
                                    </Button>
                                    {lanjut.nilai === 'shipped' && (
                                        <p className="text-xs text-muted-foreground">
                                            Pelanggan otomatis menerima email berisi semua nomor resi. Pesanan dari beberapa supplier dikirim dalam beberapa paket, satu resi per
                                            paket.
                                        </p>
                                    )}
                                </form>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    {pesanan.status === 'pending_payment' ? 'Menunggu pembayaran dari pelanggan.' : 'Tidak ada langkah lanjutan untuk pesanan ini.'}
                                </p>
                            )}
                            {pesanan.status === 'cancelled' && (
                                <div className="grid gap-2 text-sm">
                                    {pesanan.alasanBatal && (
                                        <p>
                                            <strong>Alasan batal:</strong> {pesanan.alasanBatal}
                                        </p>
                                    )}
                                    {pesanan.statusRefund === 'diproses' && (
                                        <>
                                            <p className="text-muted-foreground">
                                                Pesanan sudah dibayar: kembalikan {rupiah(pesanan.total)} ke pelanggan lewat dashboard Mayar, lalu tandai selesai di sini.
                                            </p>
                                            <Button variant="outline" className="justify-self-start" onClick={() => router.post(pesanan.refundUrl, {}, { preserveScroll: true })}>
                                                Tandai refund selesai
                                            </Button>
                                        </>
                                    )}
                                    {pesanan.statusRefund === 'selesai' && <p className="text-muted-foreground">Refund sudah ditandai selesai.</p>}
                                </div>
                            )}
                            {bisaTambahResi && <TambahResi url={pesanan.resiUrl} />}
                            {bisaBatal && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="mt-3 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    onClick={() => setKonfirmasiBatal(true)}
                                >
                                    Batalkan pesanan
                                </Button>
                            )}
                        </div>
                    </Card>

                    <KabariPelanggan key={`${pesanan.status}-${pesanan.pengiriman.length}-${pesanan.alasanBatal ?? ''}`} pesanan={pesanan} />

                    <Card className="p-5">
                        <CardTitle className="mb-2">Untung</CardTitle>
                        <Baris label="Total dibayar" nilai={rupiah(pesanan.total)} />
                        {pesanan.diskon > 0 && <p className="pb-1 text-xs text-muted-foreground">Sudah dipotong promo {pesanan.kodePromo}: -{rupiah(pesanan.diskon)} dari harga barang.</p>}
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
                                <button
                                    type="button"
                                    className="inline-flex min-h-10 items-center gap-1 text-muted-foreground underline"
                                    onClick={() => salin(pesanan.mayarId!, 'ID Mayar disalin')}
                                >
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
                deskripsi="Kalau pesanan sudah dibayar, pelanggan diberi tahu lewat email berisi alasan ini. Pengembalian dana (refund) tetap Anda proses manual di Mayar, lalu tandai selesai di halaman ini."
                labelLanjut="Ya, Batalkan"
                memproses={status.processing}
            >
                <Field id="alasan-batal" label="Alasan pembatalan (dibaca pelanggan)" error={status.errors.cancel_reason} wajib>
                    <Input
                        id="alasan-batal"
                        value={status.data.cancel_reason}
                        maxLength={255}
                        onChange={(event) => status.setData('cancel_reason', event.target.value)}
                        placeholder="Stok supplier habis"
                    />
                </Field>
            </KonfirmasiDialog>
        </AdminLayout>
    );
}
