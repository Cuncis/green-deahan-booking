import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import Field from '../../../../components/form/Field';
import UnggahFotoBanyak, { type FotoAda } from '../../../../components/form/UnggahFotoBanyak';
import AdminLayout from '../../../../components/layout/AdminLayout';
import { Button } from '../../../../components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '../../../../components/ui/card';
import { Input, Textarea } from '../../../../components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../../../components/ui/select';
import { rupiah } from '../../../../lib/format';

interface Spek {
    nama: string;
    nilai: string;
}

const MAKS_SPEK = 20;
const MAKS_VARIAN = 30;

/** One variant row. Prices stay strings in the form; empty = same as the product's price. */
interface Varian {
    id: number | null;
    name: string;
    sell_price: string;
    cost_price: string;
    is_available: boolean;
}

interface VarianTersimpan {
    id: number;
    name: string;
    sell_price: number | null;
    cost_price: number | null;
    is_available: boolean;
}

interface ProdukEdit {
    name: string;
    slug: string;
    category: string;
    unit: string;
    badge: string | null;
    description: string | null;
    sell_price: number;
    compare_price: number | null;
    cost_price: number;
    supplier_id: number | null;
    supplier_product_url: string | null;
    weight_gram: number;
    origin_postal_code: string | null;
    specs: Spek[];
    variant_label: string | null;
    variants: VarianTersimpan[];
    is_active: boolean;
    imageUrl: string | null;
    fotos: FotoAda[];
    updateUrl: string;
}

/** Isian awal hasil membaca HTML halaman produk Shopee (lihat ProdukTokoController@impor). */
interface DataImpor {
    nama: string | null;
    deskripsi: string | null;
    kategori: string | null;
    linkSupplier: string | null;
    spesifikasi: Spek[];
    varian: { label: string | null; opsi: { nama: string; tersedia: boolean }[] };
    dikirimDari: string | null;
    namaSupplier: string | null;
    gambarUrl: string | null;
    gambarUrls: string[];
    hargaShopee: number | null;
    hargaRetail: number | null;
    supplierId: number | null;
}

interface Props {
    produk: ProdukEdit | null;
    impor?: DataImpor | null;
    imporUrl: string;
    pilihanKategori: { nilai: string; label: string }[];
    pilihanSatuan: string[];
    pilihanSupplier: { id: number; name: string }[];
    simpanUrl: string;
    kembaliUrl: string;
    maksFoto: number;
}

const TANPA_SUPPLIER = 'tanpa';

/** "Jonas Knee Pad / Knee Busa - Black" becomes "jonas-knee-pad-knee-busa-black" (letters, digits, dashes). */
function buatSlug(nama: string): string {
    return nama
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 170)
        .replace(/-+$/g, '');
}

/** Selling price from cost plus a markup percentage, rounded up to the next Rp100. */
function hargaDariMarkup(modal: number, persen: number): number {
    return Math.ceil((modal * (1 + persen / 100)) / 100) * 100;
}

/** Markup percentage of a selling price over its cost, one decimal ("" when either is missing). */
function markupDari(jual: number, modal: number): string {
    return modal > 0 && jual > 0 ? String(Math.round(((jual - modal) / modal) * 1000) / 10) : '';
}

/**
 * Perkecil HTML sebelum dikirim: buang ulasan (ada nama pembeli), produk
 * lain, voucher, SVG, dan script. DOMParser tidak menjalankan script dan
 * tidak memuat gambar, dan ulasan tidak pernah sampai ke server.
 */
function ringkasHtml(html: string): string {
    try {
        const dokumen = new DOMParser().parseFromString(html, 'text/html');
        dokumen
            .querySelectorAll(
                'svg, script, style, noscript, video, [data-nosnippet], .product-ratings, .shopee-product-comment-list, [role="group"][aria-label^="Product card"], .product-shop-vouchers, .page-product__hot-sales',
            )
            .forEach((elemen) => elemen.remove());
        dokumen.querySelectorAll('[style]').forEach((elemen) => elemen.removeAttribute('style'));

        return dokumen.documentElement.outerHTML;
    } catch {
        return html;
    }
}

/**
 * Add and edit a shop product (same form). Inertia reuses the mounted page
 * after the import redirect, and useForm only reads its initial values once,
 * so each import result remounts the form with a fresh key.
 */
export default function Form(props: Props) {
    return <FormProduk key={props.impor ? `${props.impor.nama}|${props.impor.linkSupplier}` : 'baru'} {...props} />;
}

function FormProduk({ produk, impor = null, imporUrl, pilihanKategori, pilihanSatuan, pilihanSupplier, simpanUrl, kembaliUrl, maksFoto }: Props) {
    const edit = produk !== null;
    const imporForm = useForm({ html: '' });
    const form = useForm({
        name: produk?.name ?? impor?.nama ?? '',
        slug: produk?.slug ?? (impor?.nama ? buatSlug(impor.nama) : ''),
        category: produk?.category ?? impor?.kategori ?? pilihanKategori[0]?.nilai ?? '',
        unit: produk?.unit ?? 'pcs',
        badge: produk?.badge ?? '',
        description: produk?.description ?? impor?.deskripsi ?? '',
        sell_price: produk ? String(produk.sell_price) : impor?.hargaRetail ? String(impor.hargaRetail) : '',
        compare_price: produk?.compare_price ? String(produk.compare_price) : '',
        cost_price: produk ? String(produk.cost_price) : impor?.hargaShopee ? String(impor.hargaShopee) : '',
        supplier_id: produk?.supplier_id ? String(produk.supplier_id) : impor?.supplierId ? String(impor.supplierId) : TANPA_SUPPLIER,
        supplier_product_url: produk?.supplier_product_url ?? impor?.linkSupplier ?? '',
        weight_gram: produk ? String(produk.weight_gram) : '0',
        origin_postal_code: produk?.origin_postal_code ?? '',
        specs: (produk?.specs ?? impor?.spesifikasi ?? []) as Spek[],
        variant_label: produk?.variant_label ?? impor?.varian?.label ?? '',
        variants: (produk
            ? produk.variants.map((varian) => ({
                  id: varian.id,
                  name: varian.name,
                  sell_price: varian.sell_price === null ? '' : String(varian.sell_price),
                  cost_price: varian.cost_price === null ? '' : String(varian.cost_price),
                  is_available: varian.is_available,
              }))
            : (impor?.varian?.opsi ?? []).map((opsi) => ({ id: null, name: opsi.nama, sell_price: '', cost_price: '', is_available: opsi.tersedia }))) as Varian[],
        is_active: produk ? (produk.is_active ? '1' : '0') : impor ? '0' : '1',
        fotoAda: produk?.fotos ?? ([] as FotoAda[]),
        gambar_urls: impor?.gambarUrls ?? ([] as string[]),
        foto_baru: [] as File[],
    });

    const jual = Number(form.data.sell_price) || 0;
    const modal = Number(form.data.cost_price) || 0;

    // Markup is only a helper: it is never sent, the server stores the selling price.
    // markupAktif keeps the selling price following cost changes after the markup was typed.
    const [markup, setMarkup] = useState(() => markupDari(jual, modal));
    const [markupAktif, setMarkupAktif] = useState(false);

    const ubahMarkup = (nilai: string) => {
        setMarkup(nilai);
        setMarkupAktif(nilai !== '');
        const persen = Number(nilai);
        if (nilai !== '' && Number.isFinite(persen) && persen >= 0 && modal > 0) {
            form.setData('sell_price', String(hargaDariMarkup(modal, persen)));
        }
    };

    const ubahModal = (nilai: string) => {
        const modalBaru = Number(nilai) || 0;
        const persen = Number(markup);
        if (markupAktif && markup !== '' && Number.isFinite(persen) && modalBaru > 0) {
            form.setData((data) => ({ ...data, cost_price: nilai, sell_price: String(hargaDariMarkup(modalBaru, persen)) }));
        } else {
            form.setData('cost_price', nilai);
            setMarkup(markupDari(jual, modalBaru));
        }
    };

    const ubahJual = (nilai: string) => {
        form.setData('sell_price', nilai);
        setMarkupAktif(false);
        setMarkup(markupDari(Number(nilai) || 0, modal));
    };

    const baca = (event: FormEvent) => {
        event.preventDefault();
        imporForm.transform((data) => ({ html: ringkasHtml(data.html) }));
        imporForm.post(imporUrl, {
            preserveScroll: true,
            onSuccess: () => imporForm.reset(),
        });
    };

    const galat: Record<string, string> = form.errors;
    const galatFoto = galat.foto ?? Object.entries(galat).find(([kunci]) => kunci.startsWith('foto_baru') || kunci.startsWith('gambar_urls'))?.[1];

    const simpan = (event: FormEvent) => {
        event.preventDefault();
        form.transform(({ fotoAda, ...data }) => ({
            ...data,
            supplier_id: data.supplier_id === TANPA_SUPPLIER ? '' : data.supplier_id,
            // Blank rows are dropped by the server; an empty list clears the saved specs.
            specs: data.specs.filter((spek) => spek.nama.trim() !== '' || spek.nilai.trim() !== ''),
            // The server keeps a variant's id (carts stay valid), drops blank names and turns blank prices into "same as product".
            variants: data.variants.map((varian) => ({ ...varian, id: varian.id ?? '', is_available: varian.is_available ? '1' : '0' })),
            // Sent even when empty, so removing every saved photo is understood by the server.
            foto_diatur: '1',
            foto_ada: fotoAda.map((foto) => foto.id),
            ...(edit ? { _method: 'put' } : {}),
        }));
        form.post(edit ? produk.updateUrl : simpanUrl, { forceFormData: true });
    };

    return (
        <AdminLayout judul={edit ? 'Edit Produk' : 'Tambah Produk'} deskripsi={edit ? produk.name : 'Produk baru untuk halaman /toko.'}>
            <Head title={edit ? 'Edit Produk' : 'Tambah Produk'} />
            {!edit && (
                <Card className="mb-5 p-4 md:p-6">
                    <CardHeader className="p-0 pb-3">
                        <CardTitle>Impor dari HTML Shopee</CardTitle>
                        <CardDescription>
                            Tempel HTML halaman produk Shopee (buka produknya, klik kanan, Inspect, salin elemen html). Nama, deskripsi, foto, link supplier, dan harga terisi
                            otomatis sebagai saran.
                        </CardDescription>
                    </CardHeader>
                    <form onSubmit={baca} className="space-y-3">
                        <Field id="html" label="HTML halaman produk" error={imporForm.errors.html}>
                            <Textarea
                                id="html"
                                rows={4}
                                value={imporForm.data.html}
                                onChange={(event) => imporForm.setData('html', event.target.value)}
                                placeholder="<html>...</html>"
                                spellCheck={false}
                            />
                        </Field>
                        <Button type="submit" variant="outline" disabled={imporForm.processing || imporForm.data.html.trim() === ''}>
                            Baca HTML
                        </Button>
                    </form>
                </Card>
            )}
            {impor && (
                <div className="mb-5 rounded-xl border border-border bg-muted px-4 py-3 text-sm" role="status">
                    <p className="font-semibold">Hasil impor, silakan dicek dulu.</p>
                    <p className="mt-1 text-muted-foreground">
                        Terisi:{' '}
                        {[
                            impor.nama && 'nama',
                            impor.deskripsi && 'deskripsi',
                            impor.kategori && 'kategori',
                            impor.gambarUrls.length > 0 && 'foto',
                            impor.linkSupplier && 'link supplier',
                            impor.supplierId && 'supplier',
                            impor.hargaRetail && 'harga jual',
                            impor.hargaShopee && 'harga modal',
                        ]
                            .filter(Boolean)
                            .join(', ') || 'tidak ada'}
                        . Harga hanya saran dan bisa diubah, berat belum diisi. Produk dibuat Disembunyikan sampai Anda mengubahnya.
                        {!impor.supplierId && (impor.namaSupplier || impor.dikirimDari)
                            ? ` Supplier belum terpilih otomatis${impor.dikirimDari ? ` (dikirim dari ${impor.dikirimDari})` : ''}, pilih manual di bagian Supplier.`
                            : ''}
                    </p>
                </div>
            )}
            <form onSubmit={simpan} className="grid gap-5 pb-24 md:pb-0 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
                <Card className="grid grid-cols-1 gap-4 p-4 md:grid-cols-2 md:p-6">
                    <Field id="name" label="Nama produk" error={form.errors.name} wajib className="md:col-span-2">
                        <Input id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} required />
                    </Field>
                    <Field id="category" label="Kategori" error={form.errors.category} wajib>
                        <Select value={form.data.category} onValueChange={(nilai) => form.setData('category', nilai)}>
                            <SelectTrigger id="category">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {pilihanKategori.map((item) => (
                                    <SelectItem key={item.nilai} value={item.nilai}>
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field id="unit" label="Satuan jual" error={form.errors.unit} wajib hint="Harga jual dihitung per satuan ini.">
                        <Select value={form.data.unit} onValueChange={(nilai) => form.setData('unit', nilai)}>
                            <SelectTrigger id="unit">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {pilihanSatuan.map((satuan) => (
                                    <SelectItem key={satuan} value={satuan}>
                                        per {satuan}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field id="badge" label="Badge" error={form.errors.badge} hint="Opsional, misal Terlaris, Baru, Promo.">
                        <Input id="badge" value={form.data.badge} maxLength={30} onChange={(event) => form.setData('badge', event.target.value)} />
                    </Field>
                    <Field id="weight" label="Berat (gram)" error={form.errors.weight_gram}>
                        <Input
                            id="weight"
                            type="number"
                            inputMode="numeric"
                            min={0}
                            value={form.data.weight_gram}
                            onChange={(event) => form.setData('weight_gram', event.target.value)}
                        />
                    </Field>
                    <Field
                        id="origin"
                        label="Kode pos asal pengiriman"
                        error={form.errors.origin_postal_code}
                        hint={`Opsional. Isi kode pos kota 'Dikirim dari' di Shopee${impor?.dikirimDari ? ` (halaman ini: ${impor.dikirimDari})` : ''}. Kosong = pakai kode pos supplier, lalu kode pos bawaan toko. Dipakai untuk menghitung ongkir perkiraan.`}
                    >
                        <Input
                            id="origin"
                            inputMode="numeric"
                            maxLength={5}
                            value={form.data.origin_postal_code}
                            onChange={(event) => form.setData('origin_postal_code', event.target.value.replace(/\D/g, ''))}
                        />
                    </Field>
                    <div className="grid gap-3 md:col-span-2">
                        <div>
                            <p className="text-sm font-medium">Spesifikasi</p>
                            <p className="text-xs text-muted-foreground">
                                Opsional. Tampil di halaman produk, misalnya Merek: Ortuseight, Bahan: Nilon, Asal produk: Indonesia. Diisi otomatis saat impor dari Shopee.
                            </p>
                        </div>
                        {form.data.specs.map((spek, indeks) => (
                            <div key={indeks} className="grid grid-cols-[1fr_1.5fr_auto] items-start gap-2">
                                <Field id={`spek-nama-${indeks}`} label={indeks === 0 ? 'Nama' : ''} error={galat[`specs.${indeks}.nama`]}>
                                    <Input
                                        id={`spek-nama-${indeks}`}
                                        value={spek.nama}
                                        maxLength={60}
                                        aria-label={`Nama spesifikasi ${indeks + 1}`}
                                        onChange={(event) =>
                                            form.setData(
                                                'specs',
                                                form.data.specs.map((item, urutan) => (urutan === indeks ? { ...item, nama: event.target.value } : item)),
                                            )
                                        }
                                    />
                                </Field>
                                <Field id={`spek-nilai-${indeks}`} label={indeks === 0 ? 'Isi' : ''} error={galat[`specs.${indeks}.nilai`]}>
                                    <Input
                                        id={`spek-nilai-${indeks}`}
                                        value={spek.nilai}
                                        maxLength={150}
                                        aria-label={`Isi spesifikasi ${indeks + 1}`}
                                        onChange={(event) =>
                                            form.setData(
                                                'specs',
                                                form.data.specs.map((item, urutan) => (urutan === indeks ? { ...item, nilai: event.target.value } : item)),
                                            )
                                        }
                                    />
                                </Field>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className={indeks === 0 ? 'mt-6' : undefined}
                                    aria-label={`Hapus spesifikasi ${indeks + 1}`}
                                    onClick={() =>
                                        form.setData(
                                            'specs',
                                            form.data.specs.filter((_, urutan) => urutan !== indeks),
                                        )
                                    }
                                >
                                    <X />
                                </Button>
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="justify-self-start"
                            disabled={form.data.specs.length >= MAKS_SPEK}
                            onClick={() => form.setData('specs', [...form.data.specs, { nama: '', nilai: '' }])}
                        >
                            <Plus />
                            Tambah spesifikasi
                        </Button>
                    </div>
                    <div className="grid gap-3 md:col-span-2">
                        <div>
                            <p className="text-sm font-medium">Varian (warna, ukuran)</p>
                            <p className="text-xs text-muted-foreground">
                                Opsional. Pembeli wajib memilih satu varian sebelum membeli. Harga kosong berarti sama dengan harga produk. Hilangkan centang "Tersedia" kalau
                                varian habis di supplier. Diisi otomatis saat impor dari Shopee.
                            </p>
                        </div>
                        {form.data.variants.length > 0 && (
                            <Field id="variant-label" label="Judul pilihan" error={form.errors.variant_label} hint="Misalnya Warna, Ukuran, atau Warna / Ukuran.">
                                <Input id="variant-label" value={form.data.variant_label} maxLength={30} onChange={(event) => form.setData('variant_label', event.target.value)} />
                            </Field>
                        )}
                        {galat.variants && <p className="text-sm text-destructive">{galat.variants}</p>}
                        {form.data.variants.map((varian, indeks) => {
                            const ubahVarian = (perubahan: Partial<Varian>) =>
                                form.setData(
                                    'variants',
                                    form.data.variants.map((item, urutan) => (urutan === indeks ? { ...item, ...perubahan } : item)),
                                );

                            return (
                                <div
                                    key={varian.id ?? `baru-${indeks}`}
                                    className="grid gap-2 rounded-md border border-border p-3 md:grid-cols-[2fr_1fr_1fr_auto_auto] md:items-start"
                                >
                                    <Field id={`varian-nama-${indeks}`} label="Nama varian" error={galat[`variants.${indeks}.name`]}>
                                        <Input id={`varian-nama-${indeks}`} value={varian.name} maxLength={80} onChange={(event) => ubahVarian({ name: event.target.value })} />
                                    </Field>
                                    <Field id={`varian-jual-${indeks}`} label="Harga jual" error={galat[`variants.${indeks}.sell_price`]}>
                                        <Input
                                            id={`varian-jual-${indeks}`}
                                            type="number"
                                            inputMode="numeric"
                                            min={0}
                                            value={varian.sell_price}
                                            placeholder="sama"
                                            onChange={(event) => ubahVarian({ sell_price: event.target.value })}
                                        />
                                    </Field>
                                    <Field id={`varian-modal-${indeks}`} label="Harga modal" error={galat[`variants.${indeks}.cost_price`]}>
                                        <Input
                                            id={`varian-modal-${indeks}`}
                                            type="number"
                                            inputMode="numeric"
                                            min={0}
                                            value={varian.cost_price}
                                            placeholder="sama"
                                            onChange={(event) => ubahVarian({ cost_price: event.target.value })}
                                        />
                                    </Field>
                                    <label className="flex min-h-10 items-center gap-2 text-sm md:mt-6">
                                        <input type="checkbox" checked={varian.is_available} onChange={(event) => ubahVarian({ is_available: event.target.checked })} />
                                        Tersedia
                                    </label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="md:mt-6"
                                        aria-label={`Hapus varian ${indeks + 1}`}
                                        onClick={() =>
                                            form.setData(
                                                'variants',
                                                form.data.variants.filter((_, urutan) => urutan !== indeks),
                                            )
                                        }
                                    >
                                        <X />
                                    </Button>
                                </div>
                            );
                        })}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="justify-self-start"
                            disabled={form.data.variants.length >= MAKS_VARIAN}
                            onClick={() => form.setData('variants', [...form.data.variants, { id: null, name: '', sell_price: '', cost_price: '', is_available: true }])}
                        >
                            <Plus />
                            Tambah varian
                        </Button>
                    </div>
                    <Field id="description" label="Deskripsi" error={form.errors.description} className="md:col-span-2">
                        <Textarea id="description" rows={4} value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} />
                    </Field>
                    <Field
                        id="slug"
                        label="Alamat halaman (slug)"
                        error={form.errors.slug}
                        hint="Kosongkan untuk dibuat otomatis dari nama. Kalau sudah dipakai produk lain, ditambah angka di belakangnya."
                        className="md:col-span-2"
                    >
                        <div className="flex gap-2">
                            <Input
                                id="slug"
                                value={form.data.slug}
                                onChange={(event) => form.setData('slug', event.target.value)}
                                placeholder="bola-padel-isi-3"
                                autoCapitalize="none"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                className="shrink-0"
                                disabled={form.data.name.trim() === ''}
                                onClick={() => form.setData('slug', buatSlug(form.data.name))}
                            >
                                Buat dari nama
                            </Button>
                        </div>
                    </Field>
                    <Field
                        id="foto"
                        label="Foto"
                        error={galatFoto}
                        hint={
                            form.data.gambar_urls.length > 0
                                ? 'Foto dari Shopee diunduh ke toko saat disimpan. Anda bisa menghapus atau menambah foto sendiri.'
                                : form.data.fotoAda.length + form.data.foto_baru.length === 0
                                  ? 'Tanpa foto, halaman toko memakai gambar placeholder.'
                                  : undefined
                        }
                        className="md:col-span-2"
                    >
                        <UnggahFotoBanyak
                            id="foto"
                            fotoAda={form.data.fotoAda}
                            onFotoAda={(foto) => form.setData('fotoAda', foto)}
                            alamatImpor={form.data.gambar_urls}
                            onAlamatImpor={(alamat) => form.setData('gambar_urls', alamat)}
                            fotoBaru={form.data.foto_baru}
                            onFotoBaru={(file) => form.setData('foto_baru', file)}
                            maks={maksFoto}
                        />
                    </Field>
                </Card>

                <div className="space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Harga</CardTitle>
                            <CardDescription>Harga modal tidak pernah tampil ke pelanggan.</CardDescription>
                        </CardHeader>
                        <div className="grid grid-cols-1 gap-4 px-5 pb-5">
                            <Field
                                id="sell"
                                label="Harga jual (Rp)"
                                error={form.errors.sell_price}
                                wajib
                                hint={impor?.hargaRetail ? `Diisi dari harga retail penjual, ${rupiah(impor.hargaRetail)}. Ubah sesuai harga toko Anda.` : undefined}
                            >
                                <Input
                                    id="sell"
                                    type="number"
                                    inputMode="numeric"
                                    min={0}
                                    value={form.data.sell_price}
                                    onChange={(event) => ubahJual(event.target.value)}
                                    required
                                />
                            </Field>
                            <Field
                                id="compare"
                                label="Harga coret (Rp)"
                                error={form.errors.compare_price}
                                hint="Opsional. Harga lama yang dicoret, harus lebih mahal dari harga jual."
                            >
                                <Input
                                    id="compare"
                                    type="number"
                                    inputMode="numeric"
                                    min={0}
                                    value={form.data.compare_price}
                                    onChange={(event) => form.setData('compare_price', event.target.value)}
                                />
                            </Field>
                            <Field
                                id="cost"
                                label="Harga modal (Rp)"
                                error={form.errors.cost_price}
                                wajib
                                hint={
                                    impor?.hargaShopee
                                        ? `Diisi dari harga tampil di Shopee, ${rupiah(impor.hargaShopee)} (bisa sudah dipotong voucher). Cek harga dasar dari supplier.`
                                        : undefined
                                }
                            >
                                <Input
                                    id="cost"
                                    type="number"
                                    inputMode="numeric"
                                    min={0}
                                    value={form.data.cost_price}
                                    onChange={(event) => ubahModal(event.target.value)}
                                    required
                                />
                            </Field>
                            <Field
                                id="markup"
                                label="Markup dari modal (%)"
                                hint="Isi persen, harga jual dihitung dari harga modal dan dibulatkan ke atas Rp100. Harga jual tetap bisa diubah."
                            >
                                <Input
                                    id="markup"
                                    type="number"
                                    inputMode="decimal"
                                    min={0}
                                    step="0.1"
                                    value={markup}
                                    onChange={(event) => ubahMarkup(event.target.value)}
                                    placeholder="25"
                                />
                            </Field>
                            <div className="rounded-lg bg-muted px-3 py-2 text-sm">
                                Untung per barang: <strong className={jual - modal < 0 ? 'text-destructive' : 'text-green'}>{rupiah(jual - modal)}</strong>
                                {modal > 0 && jual > 0 && <span className="text-muted-foreground"> ({markupDari(jual, modal)}% dari modal)</span>}
                            </div>
                        </div>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Supplier</CardTitle>
                        </CardHeader>
                        <div className="grid grid-cols-1 gap-4 px-5 pb-5">
                            <Field id="supplier" label="Supplier" error={form.errors.supplier_id}>
                                <Select value={form.data.supplier_id} onValueChange={(nilai) => form.setData('supplier_id', nilai)}>
                                    <SelectTrigger id="supplier">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TANPA_SUPPLIER}>Belum ada</SelectItem>
                                        {pilihanSupplier.map((item) => (
                                            <SelectItem key={item.id} value={String(item.id)}>
                                                {item.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                            <Field id="supplier-url" label="Link produk di supplier" error={form.errors.supplier_product_url}>
                                <Input
                                    id="supplier-url"
                                    type="url"
                                    inputMode="url"
                                    value={form.data.supplier_product_url}
                                    onChange={(event) => form.setData('supplier_product_url', event.target.value)}
                                    placeholder="https://shopee.co.id/..."
                                />
                            </Field>
                            <Field id="aktif" label="Status" error={form.errors.is_active}>
                                <Select value={form.data.is_active} onValueChange={(nilai) => form.setData('is_active', nilai)}>
                                    <SelectTrigger id="aktif">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Dijual</SelectItem>
                                        <SelectItem value="0">Disembunyikan</SelectItem>
                                    </SelectContent>
                                </Select>
                            </Field>
                        </div>
                    </Card>
                    <div className="fixed inset-x-0 bottom-0 z-20 flex gap-2 border-t border-border bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur md:static md:border-0 md:bg-transparent md:p-0">
                        <Button asChild variant="outline">
                            <Link href={kembaliUrl}>Batal</Link>
                        </Button>
                        <Button type="submit" className="flex-1" disabled={form.processing}>
                            {edit ? 'Simpan Perubahan' : 'Tambah Produk'}
                        </Button>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
