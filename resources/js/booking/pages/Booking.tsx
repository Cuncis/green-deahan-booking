import { Head } from '@inertiajs/react';
import { Clock, Crown, LoaderCircle, MapPin, MessageCircle, Tag, Trophy } from 'lucide-react';
import { useCallback, useEffect, useRef, useState, type FormEvent } from 'react';
import Field from '../../admin/components/form/Field';
import { Button } from '../../admin/components/ui/button';
import { Input } from '../../admin/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../admin/components/ui/select';
import { cn } from '../../admin/lib/utils';
import { bukaChatWhatsApp } from '../../admin/lib/whatsapp';
import HitungMundur from '../components/HitungMundur';
import PilihJadwal from '../components/PilihJadwal';
import StatusPembayaran from '../components/StatusPembayaran';
import { api, dibatalkan, GalatApi } from '../lib/api';
import { hanyaAngka, rupiah, variabelAksen } from '../lib/aksen';
import type { Bisnis, Cabang, Fitur, HasilBooking, Hold, InfoMember, Lapangan, PilihanTanggal, Ringkasan, Slot, TipePembayaran } from '../lib/types';

interface Props {
    bisnis: Bisnis;
    fitur: Fitur;
    cabang: Cabang[];
    lapangan: Lapangan[];
    lapanganAwal: number | null;
    tanggalPilihan: PilihanTanggal[];
    kodeBookingKembali: string | null;
    menitHold: number;
    persenDp: number;
}

interface StatusDialog {
    kode: string;
    total: number | null;
    galatPembayaran: string | null;
}

type GalatForm = Partial<Record<'nama' | 'whatsapp' | 'kode_promo' | 'umum', string>>;

const JEDA_KETIK_MS = 600;

const kapital = (teks: string) => teks.charAt(0).toUpperCase() + teks.slice(1);

function BarisRingkasan({ label, nilai, className }: { label: string; nilai: string; className?: string }) {
    return (
        <div className={cn('flex justify-between gap-3 py-1.5 text-sm', className)}>
            <span className="text-muted-foreground">{label}</span>
            <span className="text-right font-semibold">{nilai}</span>
        </div>
    );
}

/**
 * Customer booking page on the tenant's domain. Flow: court, date, hour
 * (held 10 minutes with a secret token), name and WhatsApp, then payment.
 * Every price comes from POST /api/booking/harga, the same calculation the
 * server uses when it creates the booking.
 */
export default function Booking({ bisnis, fitur, cabang, lapangan, lapanganAwal, tanggalPilihan, kodeBookingKembali, menitHold, persenDp }: Props) {
    const [lapanganId, setLapanganId] = useState<number | null>(lapanganAwal);
    const lapanganAktif = lapangan.find((item) => item.id === lapanganId) ?? null;
    const [cabangId, setCabangId] = useState<number | null>(lapanganAktif?.cabangId ?? null);
    const [tanggal, setTanggal] = useState(tanggalPilihan[0]?.tanggal ?? '');

    const [slot, setSlot] = useState<Slot[] | null>(null);
    const [galatMuat, setGalatMuat] = useState<string | null>(null);
    const [muatUlang, setMuatUlang] = useState(0);
    const [hold, setHold] = useState<Hold | null>(null);
    const [slotDiproses, setSlotDiproses] = useState<number | null>(null);
    const [pesanSlot, setPesanSlot] = useState<string | null>(null);

    const [nama, setNama] = useState('');
    const [whatsapp, setWhatsapp] = useState('');
    const [teleponStabil, setTeleponStabil] = useState('');
    const [kodePromo, setKodePromo] = useState('');
    const [kodeDipakai, setKodeDipakai] = useState('');
    const [tipe, setTipe] = useState<TipePembayaran>(fitur.tipePembayaran.includes('dp') ? 'dp' : 'lunas');
    const [reminder, setReminder] = useState(true);
    const [ringkasan, setRingkasan] = useState<Ringkasan | null>(null);
    const [member, setMember] = useState<InfoMember | null>(null);

    const [mengirim, setMengirim] = useState(false);
    const [galat, setGalat] = useState<GalatForm>({});
    const [dialog, setDialog] = useState<StatusDialog | null>(kodeBookingKembali ? { kode: kodeBookingKembali, total: null, galatPembayaran: null } : null);
    const formRef = useRef<HTMLFormElement>(null);
    const holdRef = useRef<Hold | null>(null);
    holdRef.current = hold;

    const lapanganDiCabang = fitur.multiCabang && cabangId ? lapangan.filter((item) => item.cabangId === cabangId) : lapangan;

    // Customer came back from the payment page: show the status, tidy the URL.
    useEffect(() => {
        if (!kodeBookingKembali) {
            return;
        }
        const url = new URL(window.location.href);
        url.searchParams.delete('kode_booking');
        window.history.replaceState(window.history.state, '', url.pathname + url.search);
    }, [kodeBookingKembali]);

    const lepasHold = useCallback((target: Hold | null) => {
        if (target) {
            api(`/api/booking/slot/${target.slot_id}/hold`, { method: 'DELETE', body: { hold_token: target.hold_token } }).catch(() => {});
        }
    }, []);

    // Load the hours for the chosen court and date.
    useEffect(() => {
        if (!lapanganId || !tanggal) {
            return;
        }
        const pembatal = new AbortController();
        setSlot(null);
        setGalatMuat(null);

        api<{ data: Slot[] }>(`/api/booking/lapangan/${lapanganId}/slot?tanggal=${tanggal}`, { signal: pembatal.signal })
            .then((hasil) => setSlot(hasil.data))
            .catch((galatApi) => {
                if (!dibatalkan(galatApi)) {
                    setGalatMuat(galatApi instanceof GalatApi ? galatApi.message : 'Jadwal gagal dimuat.');
                }
            });

        return () => pembatal.abort();
    }, [lapanganId, tanggal, muatUlang]);

    // Phone number settles before it is used for member pricing.
    useEffect(() => {
        const jeda = window.setTimeout(() => setTeleponStabil(hanyaAngka(whatsapp).length >= 9 ? hanyaAngka(whatsapp) : ''), JEDA_KETIK_MS);

        return () => window.clearTimeout(jeda);
    }, [whatsapp]);

    // Member banner (before a slot is chosen the summary cannot show it yet).
    useEffect(() => {
        if (!fitur.membership || !teleponStabil || !lapanganId) {
            setMember(null);
            return;
        }
        const pembatal = new AbortController();
        api<{ data: InfoMember | null }>('/api/booking/cek-membership', { method: 'POST', body: { no_telepon: teleponStabil, lapangan_id: lapanganId }, signal: pembatal.signal })
            .then((hasil) => setMember(hasil.data))
            .catch(() => {});

        return () => pembatal.abort();
    }, [fitur.membership, teleponStabil, lapanganId]);

    // Price summary for the held slot.
    useEffect(() => {
        if (!hold) {
            setRingkasan(null);
            return;
        }
        const pembatal = new AbortController();
        api<{ data: Ringkasan }>('/api/booking/harga', {
            method: 'POST',
            body: { slot_id: hold.slot_id, kode_promo: kodeDipakai || null, tipe_pembayaran: tipe, no_telepon: teleponStabil || null },
            signal: pembatal.signal,
        })
            .then((hasil) => {
                setRingkasan(hasil.data);
                const status = hasil.data.status_promo;
                setGalat((lama) => ({
                    ...lama,
                    kode_promo: status === 'tidak_valid' ? 'Kode promo tidak valid atau sudah kadaluarsa.' : status === 'habis' ? 'Kuota kode promo ini sudah habis.' : undefined,
                }));
            })
            .catch(() => {});

        return () => pembatal.abort();
    }, [hold, kodeDipakai, tipe, teleponStabil]);

    const resetPilihan = () => {
        lepasHold(holdRef.current);
        setHold(null);
        setPesanSlot(null);
    };

    const pilihLapangan = (id: number) => {
        if (id === lapanganId) {
            return;
        }
        resetPilihan();
        setLapanganId(id);
        setKodeDipakai('');
        setKodePromo('');
    };

    const pilihCabang = (id: number) => {
        setCabangId(id);
        const pertama = lapangan.find((item) => item.cabangId === id);
        if (pertama) {
            pilihLapangan(pertama.id);
        }
    };

    const pilihTanggal = (baru: string) => {
        if (baru === tanggal) {
            return;
        }
        resetPilihan();
        setTanggal(baru);
    };

    const pilihSlot = async (target: Slot) => {
        setSlotDiproses(target.id);
        setPesanSlot(null);
        const sebelumnya = holdRef.current;

        try {
            const hasil = await api<{ data: Hold }>(`/api/booking/slot/${target.id}/hold`, {
                method: 'POST',
                body: { slot_sebelumnya: sebelumnya?.slot_id ?? null, token_sebelumnya: sebelumnya?.hold_token ?? null },
            });
            setHold(hasil.data);
            setSlot(
                (lama) =>
                    lama?.map((item) => {
                        if (item.id === target.id) {
                            return { ...item, tersedia: false, status: 'terisi' };
                        }
                        if (item.id === sebelumnya?.slot_id) {
                            return { ...item, tersedia: true, status: 'tersedia' };
                        }
                        return item;
                    }) ?? lama,
            );
            setGalat((lama) => ({ ...lama, umum: undefined }));
        } catch (galatApi) {
            setPesanSlot(galatApi instanceof GalatApi ? galatApi.message : 'Slot gagal dipilih, coba lagi.');
            if (galatApi instanceof GalatApi && galatApi.status === 409) {
                setMuatUlang((n) => n + 1);
            }
        } finally {
            setSlotDiproses(null);
        }
    };

    const holdHabis = () => {
        setHold(null);
        setPesanSlot(`Waktu tahan ${menitHold} menit sudah habis, silakan pilih jam lagi.`);
        setMuatUlang((n) => n + 1);
    };

    const pakaiPromo = () => {
        const kode = kodePromo.trim().toUpperCase();
        setKodePromo(kode);
        setKodeDipakai(kode);
        if (!kode) {
            setGalat((lama) => ({ ...lama, kode_promo: undefined }));
        }
    };

    const kirim = async (event?: FormEvent) => {
        event?.preventDefault();
        const galatBaru: GalatForm = {};

        if (!hold) {
            galatBaru.umum = 'Pilih jam main terlebih dahulu.';
        }
        if (nama.trim().length < 2) {
            galatBaru.nama = 'Isi nama kamu.';
        }
        const angka = hanyaAngka(whatsapp).length;
        if (angka < 9 || angka > 15) {
            galatBaru.whatsapp = 'Isi nomor WhatsApp yang aktif, misal 0812xxxxxxx.';
        }

        if (Object.keys(galatBaru).length > 0) {
            setGalat((lama) => ({ ...lama, ...galatBaru }));
            const pertama = galatBaru.nama ? 'nama' : galatBaru.whatsapp ? 'whatsapp' : null;
            if (pertama) {
                document.getElementById(pertama)?.focus();
            } else {
                document.getElementById('pilih-jadwal')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
        }

        setMengirim(true);
        setGalat({});
        let pindahHalaman = false;

        try {
            const hasil = await api<HasilBooking>('/api/booking', {
                method: 'POST',
                body: {
                    slot_id: hold!.slot_id,
                    hold_token: hold!.hold_token,
                    nama: nama.trim(),
                    whatsapp,
                    kode_promo: ringkasan?.status_promo === 'valid' ? kodeDipakai : null,
                    tipe_pembayaran: tipe,
                    reminder_aktif: fitur.reminder && reminder,
                },
            });

            const tujuan = hasil.pembayaran?.redirect_url;
            if (tujuan) {
                pindahHalaman = true;
                window.location.href = tujuan;
                return;
            }

            setHold(null);
            setMuatUlang((n) => n + 1);
            setDialog({
                kode: hasil.data.kode_booking,
                total: hasil.data.total_bayar,
                galatPembayaran: hasil.pembayaran_error ?? 'Booking berhasil dibuat, tapi halaman pembayaran gagal dibuka. Hubungi admin untuk bantuan.',
            });
        } catch (galatApi) {
            if (galatApi instanceof GalatApi && galatApi.status === 422) {
                const kolom = galatApi.galatKolom;
                setGalat({
                    nama: kolom.nama?.[0],
                    whatsapp: kolom.whatsapp?.[0],
                    umum: kolom.tipe_pembayaran?.[0] ?? (kolom.slot_id || kolom.hold_token ? galatApi.message : undefined),
                });
            } else if (galatApi instanceof GalatApi && galatApi.status === 409) {
                setHold(null);
                setPesanSlot(galatApi.message);
                setMuatUlang((n) => n + 1);
                document.getElementById('pilih-jadwal')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                setGalat({ umum: galatApi instanceof GalatApi ? galatApi.message : 'Booking gagal dibuat, coba lagi.' });
            }
        } finally {
            if (!pindahHalaman) {
                setMengirim(false);
            }
        }
    };

    const total = ringkasan?.total_bayar ?? hold?.harga ?? 0;
    const labelTombol = mengirim ? 'Memproses...' : hold ? `Booking Sekarang, ${rupiah(total)}` : 'Pilih jam terlebih dahulu';
    const tanggalLabel = tanggalPilihan.find((item) => item.tanggal === (hold?.tanggal ?? tanggal))?.label ?? '';
    const linkWaBisnis = bisnis.whatsapp ? `https://wa.me/${bisnis.whatsapp}` : null;

    return (
        <div style={variabelAksen(bisnis.warnaUtama)} className="min-h-screen bg-background">
            <Head title={`Booking Lapangan, ${bisnis.nama}`} />

            <header className="sticky top-0 z-30 border-b border-border bg-background/95 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-5xl items-center justify-between gap-3 px-4">
                    <div className="flex min-w-0 items-center gap-2.5">
                        {bisnis.logoUrl && <img src={bisnis.logoUrl} alt="" className="size-9 shrink-0 rounded-lg object-cover" />}
                        <span className="truncate font-display text-base font-semibold text-[var(--aksen)]">{bisnis.nama}</span>
                    </div>
                    {linkWaBisnis && (
                        <Button variant="outline" size="sm" className="shrink-0" onClick={() => bukaChatWhatsApp(linkWaBisnis)}>
                            <MessageCircle />
                            Hubungi Kami
                        </Button>
                    )}
                </div>
            </header>

            {!lapanganAktif ? (
                <main className="mx-auto max-w-xl px-4 py-16 text-center text-ink-mid">Belum ada lapangan tersedia untuk booking saat ini.</main>
            ) : (
                <main className="mx-auto max-w-5xl px-4 pb-32 pt-5 lg:pb-12">
                    {fitur.multiCabang && cabang.length > 1 && (
                        <div className="mb-3 max-w-xs">
                            <label htmlFor="pilih-cabang" className="sr-only">
                                Pilih cabang
                            </label>
                            <Select value={String(cabangId ?? '')} onValueChange={(nilai) => pilihCabang(Number(nilai))}>
                                <SelectTrigger id="pilih-cabang">
                                    <SelectValue placeholder="Pilih cabang" />
                                </SelectTrigger>
                                <SelectContent>
                                    {cabang.map((item) => (
                                        <SelectItem key={item.id} value={String(item.id)}>
                                            {item.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    {lapanganDiCabang.length > 1 && (
                        <div className="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0" role="radiogroup" aria-label="Pilih lapangan">
                            {lapanganDiCabang.map((item) => {
                                const aktif = item.id === lapanganId;

                                return (
                                    <button
                                        key={item.id}
                                        type="button"
                                        role="radio"
                                        aria-checked={aktif}
                                        onClick={() => pilihLapangan(item.id)}
                                        className={cn(
                                            'min-h-11 flex-none whitespace-nowrap rounded-xl border px-4 text-sm font-semibold transition-colors',
                                            aktif
                                                ? 'border-[var(--aksen)] bg-[var(--aksen-pucat)] text-[var(--aksen)]'
                                                : 'border-border bg-card text-ink-mid hover:border-[var(--aksen)]',
                                        )}
                                    >
                                        {item.nama}
                                    </button>
                                );
                            })}
                        </div>
                    )}

                    <section className="mb-5 flex gap-4 rounded-2xl border border-border bg-card p-4 sm:p-5">
                        <div className="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[var(--aksen-pucat)] sm:size-28">
                            {lapanganAktif.fotoUrl ? (
                                <img src={lapanganAktif.fotoUrl} alt={lapanganAktif.nama} className="size-full object-cover" />
                            ) : (
                                <Trophy className="size-9 text-[var(--aksen)] opacity-60" aria-hidden="true" />
                            )}
                        </div>
                        <div className="min-w-0">
                            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{kapital(lapanganAktif.jenis)}</div>
                            <h1 className="font-display text-xl font-semibold leading-snug sm:text-2xl">{lapanganAktif.nama}</h1>
                            <div className="mt-1 space-y-0.5 text-sm text-ink-mid">
                                {lapanganAktif.alamat && (
                                    <p className="flex items-start gap-1.5">
                                        <MapPin className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                        {lapanganAktif.alamat}
                                    </p>
                                )}
                                {lapanganAktif.jamBuka && (
                                    <p className="flex items-center gap-1.5">
                                        <Clock className="size-4 shrink-0" aria-hidden="true" />
                                        Buka {lapanganAktif.jamBuka} sampai {lapanganAktif.jamTutup} WIB
                                    </p>
                                )}
                            </div>
                            <div className="mt-1.5 font-bold text-[var(--aksen)]">
                                {rupiah(lapanganAktif.hargaPerJam)} <span className="text-xs font-normal text-muted-foreground">per jam</span>
                            </div>
                            {member && (
                                <div className="mt-1 inline-flex items-center gap-1.5 text-xs font-semibold text-gold">
                                    <Crown className="size-3.5" aria-hidden="true" />
                                    Member {member.tier}, hemat {member.persen_diskon}% jadi {rupiah(member.harga_member)} per jam
                                </div>
                            )}
                        </div>
                    </section>

                    <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
                        <section id="pilih-jadwal" className="scroll-mt-20 rounded-2xl border border-border bg-card p-4 sm:p-5">
                            <PilihJadwal
                                tanggalPilihan={tanggalPilihan}
                                tanggal={tanggal}
                                onTanggal={pilihTanggal}
                                slot={slot}
                                galatMuat={galatMuat}
                                onMuatUlang={() => setMuatUlang((n) => n + 1)}
                                slotDipilih={hold?.slot_id ?? null}
                                slotDiproses={slotDiproses}
                                pesan={pesanSlot}
                                onPilihSlot={pilihSlot}
                            />
                        </section>

                        <form ref={formRef} onSubmit={kirim} noValidate className="rounded-2xl border border-border bg-card p-4 sm:p-5 lg:sticky lg:top-20">
                            <h2 className="mb-2 text-xs font-bold uppercase tracking-wide text-[var(--aksen)]">Ringkasan Booking</h2>
                            <div className="divide-y divide-dashed divide-border">
                                <BarisRingkasan label="Lapangan" nilai={lapanganAktif.nama} />
                                <BarisRingkasan label="Tanggal" nilai={hold ? tanggalLabel : 'Belum dipilih'} />
                                <BarisRingkasan label="Jam" nilai={hold ? `${hold.jam_mulai} sampai ${hold.jam_selesai}` : 'Belum dipilih'} />
                            </div>

                            {hold && (
                                <div className="mt-2 border-t border-border pt-2">
                                    <BarisRingkasan label="Harga" nilai={rupiah(ringkasan?.harga_normal ?? hold.harga)} />
                                    {ringkasan && ringkasan.diskon_member > 0 && (
                                        <BarisRingkasan
                                            label={`Diskon member ${ringkasan.member?.tier ?? ''}`}
                                            nilai={`-${rupiah(ringkasan.diskon_member)}`}
                                            className="text-green"
                                        />
                                    )}
                                    {ringkasan && ringkasan.diskon_promo > 0 && (
                                        <BarisRingkasan label={`Promo ${ringkasan.kode_promo}`} nilai={`-${rupiah(ringkasan.diskon_promo)}`} className="text-green" />
                                    )}
                                    {ringkasan && tipe === 'dp' && <BarisRingkasan label="Total harga" nilai={rupiah(ringkasan.total_setelah_diskon)} />}
                                </div>
                            )}

                            <div className="mt-2 flex items-baseline justify-between border-t-2 border-border pt-3">
                                <span className="font-bold">{tipe === 'dp' ? `Bayar sekarang (DP ${persenDp}%)` : 'Total bayar'}</span>
                                <span className="text-lg font-bold text-[var(--aksen)]">{hold ? rupiah(total) : '-'}</span>
                            </div>
                            {hold && (
                                <div className="mt-1 text-right text-xs">
                                    <HitungMundur sampai={hold.hold_sampai} onHabis={holdHabis} />
                                </div>
                            )}

                            {fitur.tipePembayaran.length > 1 && (
                                <fieldset className="mt-5">
                                    <legend className="mb-2 text-sm font-medium">Pilihan bayar</legend>
                                    <div className="grid grid-cols-2 gap-2">
                                        {fitur.tipePembayaran.map((pilihan) => {
                                            const aktif = tipe === pilihan;
                                            const nilai = ringkasan
                                                ? pilihan === 'dp'
                                                    ? Math.round((ringkasan.total_setelah_diskon * persenDp) / 100)
                                                    : ringkasan.total_setelah_diskon
                                                : null;

                                            return (
                                                <label
                                                    key={pilihan}
                                                    className={cn(
                                                        'flex min-h-14 cursor-pointer flex-col items-center justify-center rounded-xl border px-2 text-center text-sm font-semibold transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring',
                                                        aktif ? 'border-[var(--aksen)] bg-[var(--aksen-pucat)] text-[var(--aksen)]' : 'border-border text-ink-mid',
                                                    )}
                                                >
                                                    <input type="radio" name="tipe" value={pilihan} checked={aktif} onChange={() => setTipe(pilihan)} className="sr-only" />
                                                    {pilihan === 'dp' ? `DP ${persenDp}%` : 'Bayar Lunas'}
                                                    {nilai !== null && <span className="text-xs font-normal opacity-80">{rupiah(nilai)}</span>}
                                                </label>
                                            );
                                        })}
                                    </div>
                                </fieldset>
                            )}

                            {fitur.kodePromo && (
                                <div className="mt-5 space-y-1.5">
                                    <label htmlFor="kode-promo" className="text-sm font-medium">
                                        Kode promo
                                    </label>
                                    <div className="flex gap-2">
                                        <div className="relative flex-1">
                                            <Tag className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                id="kode-promo"
                                                value={kodePromo}
                                                onChange={(event) => setKodePromo(event.target.value)}
                                                onKeyDown={(event) => {
                                                    if (event.key === 'Enter') {
                                                        event.preventDefault();
                                                        pakaiPromo();
                                                    }
                                                }}
                                                placeholder="Misal SEPI20"
                                                autoCapitalize="characters"
                                                autoCorrect="off"
                                                spellCheck={false}
                                                className="pl-9 uppercase placeholder:normal-case"
                                            />
                                        </div>
                                        <Button type="button" variant="outline" onClick={pakaiPromo}>
                                            Pakai
                                        </Button>
                                    </div>
                                    {galat.kode_promo ? (
                                        <p className="text-sm text-destructive" role="alert">
                                            {galat.kode_promo}
                                        </p>
                                    ) : ringkasan?.status_promo === 'valid' ? (
                                        <p className="text-sm text-green">Kode promo {ringkasan.kode_promo} dipakai.</p>
                                    ) : kodeDipakai && !hold ? (
                                        <p className="text-xs text-muted-foreground">Diskon dihitung setelah kamu memilih jam.</p>
                                    ) : null}
                                </div>
                            )}

                            <div className="mt-5 grid gap-4">
                                <Field id="nama" label="Nama lengkap" error={galat.nama} wajib>
                                    <Input
                                        id="nama"
                                        value={nama}
                                        onChange={(event) => setNama(event.target.value)}
                                        autoComplete="name"
                                        placeholder="Nama kamu"
                                        aria-invalid={Boolean(galat.nama)}
                                    />
                                </Field>
                                <Field id="whatsapp" label="Nomor WhatsApp" error={galat.whatsapp} wajib hint="Kode booking dan pengingat dikirim ke nomor ini.">
                                    <Input
                                        id="whatsapp"
                                        type="tel"
                                        inputMode="tel"
                                        autoComplete="tel"
                                        value={whatsapp}
                                        onChange={(event) => setWhatsapp(event.target.value)}
                                        placeholder="08xxxxxxxxxx"
                                        aria-invalid={Boolean(galat.whatsapp)}
                                    />
                                </Field>
                            </div>

                            {fitur.reminder && (
                                <label className="mt-4 flex min-h-12 cursor-pointer items-center justify-between gap-3 rounded-xl border border-border px-3 py-2 text-sm text-ink-mid">
                                    Kirim pengingat WhatsApp 2 jam sebelum main
                                    <span className="relative inline-flex">
                                        <input type="checkbox" checked={reminder} onChange={(event) => setReminder(event.target.checked)} className="peer sr-only" role="switch" />
                                        <span className="h-6 w-11 rounded-full bg-cream-deep transition-colors peer-checked:bg-[var(--aksen)] peer-focus-visible:ring-2 peer-focus-visible:ring-ring" />
                                        <span className="absolute left-1 top-1 size-4 rounded-full bg-card transition-transform peer-checked:translate-x-5" />
                                    </span>
                                </label>
                            )}

                            {galat.umum && (
                                <p className="mt-4 text-sm text-destructive" role="alert">
                                    {galat.umum}
                                </p>
                            )}

                            <Button
                                type="submit"
                                size="lg"
                                disabled={mengirim}
                                className="mt-4 hidden w-full bg-[var(--aksen)] text-[var(--aksen-teks)] hover:bg-[var(--aksen)] hover:brightness-95 lg:inline-flex"
                            >
                                {mengirim && <LoaderCircle className="animate-spin" />}
                                {labelTombol}
                            </Button>
                            <p className="mt-3 text-center text-xs text-muted-foreground">Pembayaran lewat QRIS, e-wallet, atau transfer VA di halaman Mayar.</p>
                        </form>
                    </div>
                </main>
            )}

            {lapanganAktif && (
                <div className="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-background/95 px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 backdrop-blur lg:hidden">
                    {hold && (
                        <div className="mb-2 flex items-center justify-between gap-3 text-xs">
                            <span className="truncate font-semibold">
                                {tanggalLabel}, {hold.jam_mulai}
                            </span>
                            <HitungMundur sampai={hold.hold_sampai} onHabis={() => {}} />
                        </div>
                    )}
                    <Button
                        type="button"
                        size="lg"
                        disabled={mengirim}
                        onClick={() => (hold ? formRef.current?.requestSubmit() : document.getElementById('pilih-jadwal')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))}
                        className="w-full bg-[var(--aksen)] text-[var(--aksen-teks)] hover:bg-[var(--aksen)] hover:brightness-95"
                    >
                        {mengirim && <LoaderCircle className="animate-spin" />}
                        {labelTombol}
                    </Button>
                </div>
            )}

            <footer className="border-t border-border px-4 py-6 pb-36 text-center text-sm text-muted-foreground lg:pb-6">
                <strong className="text-[var(--aksen)]">{bisnis.nama}</strong>
            </footer>

            {dialog && (
                <StatusPembayaran
                    kodeBooking={dialog.kode}
                    totalAwal={dialog.total}
                    galatPembayaran={dialog.galatPembayaran}
                    namaBisnis={bisnis.nama}
                    whatsappBisnis={bisnis.whatsapp}
                    menitPantau={menitHold}
                    onTutup={() => setDialog(null)}
                />
            )}
        </div>
    );
}
