import { createRoot } from 'react-dom/client';
import { Area, AreaChart, CartesianGrid, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

/**
 * Superadmin dashboard: visitor charts for the corporate site, shadcn-style
 * cards + Recharts. Data comes from StatistikPengunjungService via the
 * data-statistik attribute on #statistik-pengunjung
 * (resources/views/pages/superadmin/dashboard.blade.php).
 */

interface Statistik {
    rentang: number;
    pilihanRentang: number[];
    ringkasan: {
        pengunjung: number;
        tampilan: number;
        pengunjungHariIni: number;
        halamanPerPengunjung: number;
        perubahanPengunjung: number | null;
        perubahanTampilan: number | null;
    };
    harian: { tanggal: string; label: string; pengunjung: number; tampilan: number }[];
    halamanTeratas: { path: string; nama: string; tampilan: number; pengunjung: number }[];
    perangkat: { nama: string; jumlah: number }[];
    sumber: { nama: string; jumlah: number }[];
}

/** Superadmin design tokens (tailwind.config.js) */
const WARNA = {
    green: '#3A6B4A',
    greenLight: '#6AAF7C',
    gold: '#C99A3A',
    plum: '#6B4A6B',
    sand: '#C9B99A',
    ink: '#2A2018',
    inkSoft: '#8A7260',
    creamDeep: '#E0D5C0',
};
const WARNA_PERANGKAT = [WARNA.green, WARNA.gold, WARNA.plum, WARNA.sand];

const angka = (n: number) => n.toLocaleString('id-ID');

function Kartu({ children, className = '' }: { children: React.ReactNode; className?: string }) {
    return <div className={`rounded-card border border-cream-deep bg-white shadow-sm ${className}`}>{children}</div>;
}

function JudulKartu({ judul, keterangan }: { judul: string; keterangan?: string }) {
    return (
        <div className="px-5 pt-5">
            <h3 className="text-sm font-bold text-ink">{judul}</h3>
            {keterangan && <p className="mt-0.5 text-xs text-ink-soft">{keterangan}</p>}
        </div>
    );
}

function Perubahan({ nilai }: { nilai: number | null }) {
    if (nilai === null) {
        return <span className="text-xs text-ink-soft">belum ada pembanding</span>;
    }
    const naik = nilai >= 0;

    return (
        <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-bold ${naik ? 'bg-green-pale text-green' : 'bg-danger-pale text-danger'}`}>
            <svg viewBox="0 0 24 24" width={12} height={12} fill="none" stroke="currentColor" strokeWidth={2.6} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d={naik ? 'M7 17L17 7M9 7h8v8' : 'M7 7l10 10M17 9v8H9'} />
            </svg>
            {naik ? '+' : ''}
            {nilai.toLocaleString('id-ID')}%
        </span>
    );
}

function AngkaUtama({ label, nilai, catatan }: { label: string; nilai: string; catatan: React.ReactNode }) {
    return (
        <Kartu className="p-5">
            <div className="text-xs font-semibold text-ink-soft">{label}</div>
            <div className="mt-1.5 font-display text-2xl font-semibold text-ink">{nilai}</div>
            <div className="mt-2">{catatan}</div>
        </Kartu>
    );
}

function TooltipGrafik({ active, payload, label }: { active?: boolean; payload?: { name: string; value: number; color: string }[]; label?: string }) {
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className="rounded-lg border border-cream-deep bg-white px-3 py-2 text-xs shadow-md">
            <div className="mb-1 font-bold text-ink">{label}</div>
            {payload.map((item) => (
                <div key={item.name} className="flex items-center gap-2 text-ink-mid">
                    <span className="h-2 w-2 rounded-full" style={{ backgroundColor: item.color }} />
                    {item.name}: <span className="font-bold text-ink">{angka(item.value)}</span>
                </div>
            ))}
        </div>
    );
}

function DaftarBatang({ baris, kosong }: { baris: { nama: string; nilai: number; sub?: string }[]; kosong: string }) {
    const maks = Math.max(1, ...baris.map((item) => item.nilai));

    if (baris.length === 0) {
        return <p className="px-5 pb-5 pt-3 text-sm text-ink-soft">{kosong}</p>;
    }

    return (
        <ul className="space-y-2.5 px-5 pb-5 pt-4">
            {baris.map((item) => (
                <li key={item.nama} className="relative">
                    <div className="absolute inset-y-0 left-0 rounded-md bg-green-pale" style={{ width: `${(item.nilai / maks) * 100}%` }} aria-hidden="true" />
                    <div className="relative flex items-center justify-between gap-3 px-2.5 py-1.5 text-sm">
                        <span className="min-w-0 truncate font-medium text-ink" title={item.sub ?? item.nama}>
                            {item.nama}
                            {item.sub && <span className="ml-1.5 text-xs font-normal text-ink-soft">{item.sub}</span>}
                        </span>
                        <span className="shrink-0 font-bold text-ink">{angka(item.nilai)}</span>
                    </div>
                </li>
            ))}
        </ul>
    );
}

function StatistikPengunjung({ data }: { data: Statistik }) {
    const { ringkasan, harian } = data;
    const kosong = ringkasan.tampilan === 0;
    const totalPerangkat = data.perangkat.reduce((total, item) => total + item.jumlah, 0);

    return (
        <div>
            <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="judul-statistik-pengunjung" className="font-display text-lg font-semibold text-ink">
                        Pengunjung Website
                    </h2>
                    <p className="mt-0.5 text-sm text-ink-soft">Kunjungan ke greendeahan.com, {data.rentang} hari terakhir.</p>
                </div>
                <nav className="inline-flex rounded-lg border border-cream-deep bg-white p-1" aria-label="Rentang waktu">
                    {data.pilihanRentang.map((hari) => (
                        <a
                            key={hari}
                            href={`?rentang=${hari}`}
                            aria-current={hari === data.rentang ? 'page' : undefined}
                            className={`rounded-md px-3 py-1.5 text-xs font-bold transition-colors ${hari === data.rentang ? 'bg-green text-white' : 'text-ink-mid hover:bg-cream'}`}
                        >
                            {hari} hari
                        </a>
                    ))}
                </nav>
            </div>

            {kosong && (
                <Kartu className="mb-4 p-5 text-sm text-ink-mid">
                    Belum ada kunjungan tercatat di rentang ini. Data mulai terkumpul sejak fitur pencatatan aktif; kunjungan dari bot dan tim yang sedang login tidak dihitung.
                </Kartu>
            )}

            <div className="mb-4 grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                <AngkaUtama label="Pengunjung" nilai={angka(ringkasan.pengunjung)} catatan={<Perubahan nilai={ringkasan.perubahanPengunjung} />} />
                <AngkaUtama label="Tampilan halaman" nilai={angka(ringkasan.tampilan)} catatan={<Perubahan nilai={ringkasan.perubahanTampilan} />} />
                <AngkaUtama label="Pengunjung hari ini" nilai={angka(ringkasan.pengunjungHariIni)} catatan={<span className="text-xs text-ink-soft">sejak 00.00 WIB</span>} />
                <AngkaUtama
                    label="Halaman per pengunjung"
                    nilai={ringkasan.halamanPerPengunjung.toLocaleString('id-ID')}
                    catatan={<span className="text-xs text-ink-soft">rata-rata dibuka</span>}
                />
            </div>

            <Kartu className="mb-4">
                <JudulKartu judul="Kunjungan per hari" keterangan="Pengunjung unik dan tampilan halaman setiap hari (WIB)." />
                <div className="h-72 px-2 pb-3 pt-4">
                    <ResponsiveContainer width="100%" height="100%">
                        <AreaChart data={harian} margin={{ top: 8, right: 16, left: 0, bottom: 0 }}>
                            <defs>
                                <linearGradient id="isiTampilan" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor={WARNA.gold} stopOpacity={0.3} />
                                    <stop offset="95%" stopColor={WARNA.gold} stopOpacity={0} />
                                </linearGradient>
                                <linearGradient id="isiPengunjung" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor={WARNA.green} stopOpacity={0.35} />
                                    <stop offset="95%" stopColor={WARNA.green} stopOpacity={0} />
                                </linearGradient>
                            </defs>
                            <CartesianGrid vertical={false} stroke={WARNA.creamDeep} strokeDasharray="3 3" />
                            <XAxis dataKey="label" tickLine={false} axisLine={false} interval="preserveStartEnd" minTickGap={28} tick={{ fill: WARNA.inkSoft, fontSize: 12 }} tickMargin={8} />
                            <YAxis allowDecimals={false} tickLine={false} axisLine={false} width={40} tick={{ fill: WARNA.inkSoft, fontSize: 12 }} />
                            <Tooltip content={<TooltipGrafik />} cursor={{ stroke: WARNA.sand }} />
                            <Area type="monotone" dataKey="tampilan" name="Tampilan" stroke={WARNA.gold} strokeWidth={2} fill="url(#isiTampilan)" />
                            <Area type="monotone" dataKey="pengunjung" name="Pengunjung" stroke={WARNA.green} strokeWidth={2.5} fill="url(#isiPengunjung)" />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
                <div className="flex gap-5 px-5 pb-4 text-xs text-ink-mid">
                    <span className="inline-flex items-center gap-1.5">
                        <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: WARNA.green }} />
                        Pengunjung
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: WARNA.gold }} />
                        Tampilan halaman
                    </span>
                </div>
            </Kartu>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <Kartu className="lg:col-span-2">
                    <JudulKartu judul="Halaman terpopuler" keterangan="Diurutkan dari tampilan terbanyak." />
                    <DaftarBatang
                        baris={data.halamanTeratas.map((item) => ({ nama: item.nama, nilai: item.tampilan, sub: item.nama !== item.path ? item.path : undefined }))}
                        kosong="Belum ada data halaman."
                    />
                </Kartu>

                <div className="grid grid-cols-1 gap-4">
                    <Kartu>
                        <JudulKartu judul="Perangkat" />
                        {totalPerangkat === 0 ? (
                            <p className="px-5 pb-5 pt-3 text-sm text-ink-soft">Belum ada data.</p>
                        ) : (
                            <div className="flex items-center gap-4 px-5 pb-5 pt-2">
                                <div className="h-28 w-28 shrink-0">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie data={data.perangkat} dataKey="jumlah" nameKey="nama" innerRadius={32} outerRadius={52} paddingAngle={2} stroke="none">
                                                {data.perangkat.map((item, index) => (
                                                    <Cell key={item.nama} fill={WARNA_PERANGKAT[index % WARNA_PERANGKAT.length]} />
                                                ))}
                                            </Pie>
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>
                                <ul className="min-w-0 flex-1 space-y-1.5 text-sm">
                                    {data.perangkat.map((item, index) => (
                                        <li key={item.nama} className="flex items-center justify-between gap-2">
                                            <span className="inline-flex items-center gap-2 text-ink-mid">
                                                <span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: WARNA_PERANGKAT[index % WARNA_PERANGKAT.length] }} />
                                                {item.nama}
                                            </span>
                                            <span className="font-bold text-ink">{Math.round((item.jumlah / totalPerangkat) * 100)}%</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </Kartu>

                    <Kartu>
                        <JudulKartu judul="Sumber kunjungan" keterangan="Dari mana pengunjung datang." />
                        <DaftarBatang baris={data.sumber.map((item) => ({ nama: item.nama, nilai: item.jumlah }))} kosong="Belum ada data." />
                    </Kartu>
                </div>
            </div>
        </div>
    );
}

const wadah = document.getElementById('statistik-pengunjung');
if (wadah?.dataset.statistik) {
    createRoot(wadah).render(<StatistikPengunjung data={JSON.parse(wadah.dataset.statistik) as Statistik} />);
}
