import { Head, Link, router } from '@inertiajs/react';
import { Goal, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import KonfirmasiDialog from '../../../components/KonfirmasiDialog';
import AdminLayout from '../../../components/layout/AdminLayout';
import { Badge } from '../../../components/ui/badge';
import { Button } from '../../../components/ui/button';
import { Card } from '../../../components/ui/card';
import { rupiah } from '../../../lib/format';

interface LapanganRingkas {
    id: number;
    nama: string;
    jenis: string;
    cabang: string | null;
    hargaPerJam: number;
    fotoUrl: string | null;
    aktif: boolean;
    editUrl: string;
    hapusUrl: string;
}

interface Props {
    lapangan: LapanganRingkas[];
    batasLapangan: number | null;
    jumlahLapangan: number;
    paketUpgrade: string[];
}

const KETERANGAN_PAKET: Record<string, string> = {
    pro: '3 lapangan, DP pembayaran, kode promo, booking berulang, rating & ulasan, laporan pendapatan.',
    premium: 'Lapangan tanpa batas, multi-cabang, membership, reminder otomatis, role staf, dan analitik lanjutan.',
};

const kapital = (teks: string) => teks.charAt(0).toUpperCase() + teks.slice(1);

export default function Index({ lapangan, batasLapangan, jumlahLapangan, paketUpgrade }: Props) {
    const [dihapus, setDihapus] = useState<LapanganRingkas | null>(null);
    const [memproses, setMemproses] = useState(false);
    const penuh = batasLapangan !== null && jumlahLapangan >= batasLapangan;

    return (
        <AdminLayout
            judul="Lapangan Saya"
            deskripsi={`${jumlahLapangan}${batasLapangan !== null ? ` dari ${batasLapangan}` : ''} lapangan dipakai.`}
            aksi={
                !penuh && (
                    <Button asChild className="w-full sm:w-auto">
                        <Link href="/admin/lapangan/tambah" prefetch>
                            <Plus />
                            Tambah Lapangan
                        </Link>
                    </Button>
                )
            }
        >
            <Head title="Lapangan Saya" />

            {penuh && (
                <Card className="mb-5 border-amber/30 bg-amber-pale p-4">
                    <p className="mb-3 text-sm text-amber">Paketmu dibatasi {batasLapangan} lapangan. Upgrade paket untuk menambah lebih banyak.</p>
                    <div className="grid gap-2 sm:grid-cols-2">
                        {paketUpgrade.map((paket) => (
                            <button
                                key={paket}
                                type="button"
                                onClick={() => router.post('/admin/lapangan/minta-upgrade', { paket_tujuan: paket }, { preserveScroll: true })}
                                className="rounded-xl border-2 border-amber/40 bg-card px-4 py-3 text-left transition-colors hover:border-amber"
                            >
                                <span className="block text-sm font-semibold">Upgrade ke {kapital(paket)}</span>
                                <span className="block text-xs text-muted-foreground">{KETERANGAN_PAKET[paket]}</span>
                            </button>
                        ))}
                    </div>
                </Card>
            )}

            {lapangan.length === 0 ? (
                <Card className="p-8 text-center text-sm text-ink-mid">Belum ada lapangan, tambahkan lapangan pertamamu.</Card>
            ) : (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {lapangan.map((item) => (
                        <Card key={item.id} className="overflow-hidden">
                            <div className="flex h-36 items-center justify-center overflow-hidden bg-muted">
                                {item.fotoUrl ? (
                                    <img src={item.fotoUrl} alt={item.nama} loading="lazy" className="size-full object-cover" />
                                ) : (
                                    <Goal className="size-9 text-muted-foreground" aria-hidden="true" />
                                )}
                            </div>
                            <div className="p-4">
                                <div className="mb-1 flex items-start justify-between gap-2">
                                    <span className="font-display font-semibold">{item.nama}</span>
                                    <Badge variant={item.aktif ? 'confirmed' : 'cancelled'}>{item.aktif ? 'Aktif' : 'Nonaktif'}</Badge>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    {kapital(item.jenis)}
                                    {item.cabang ? `, ${item.cabang}` : ''}
                                </p>
                                <p className="mb-3 mt-1 text-sm text-ink-mid">{rupiah(item.hargaPerJam)}/jam</p>
                                <div className="flex gap-2">
                                    <Button asChild size="sm" variant="outline" className="flex-1">
                                        <Link href={item.editUrl} prefetch>
                                            <Pencil />
                                            Edit
                                        </Link>
                                    </Button>
                                    <Button size="sm" variant="destructive-soft" className="flex-1" onClick={() => setDihapus(item)}>
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
                terbuka={dihapus !== null}
                onTutup={() => setDihapus(null)}
                judul="Hapus Lapangan"
                deskripsi={`Lapangan "${dihapus?.nama}" akan dihapus permanen, beserta jadwal slotnya yang belum pernah dibooking. Lapangan yang punya riwayat booking tidak bisa dihapus, nonaktifkan saja.`}
                labelLanjut="Ya, Hapus"
                memproses={memproses}
                onLanjut={() =>
                    dihapus &&
                    router.delete(dihapus.hapusUrl, {
                        preserveScroll: true,
                        onStart: () => setMemproses(true),
                        onFinish: () => {
                            setMemproses(false);
                            setDihapus(null);
                        },
                    })
                }
            />
        </AdminLayout>
    );
}
