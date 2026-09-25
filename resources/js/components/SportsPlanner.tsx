import { useState } from 'react';
import Icon from './Icon';
import Reveal from './Reveal';
import {
    PLANNER_CONFIG,
    buildOptions,
    formatM2,
    formatRupiah,
    formatRupiahRange,
    budgetStatusLabel,
    type BudgetKey,
    type FacilityKey,
    type FutsalVariant,
    type PlannerOption,
    type SportKey,
} from '../lib/plannerCalculator';

const WA_NUMBER = '6281357570064';

const stepLabels = ['Lahan', 'Budget', 'Olahraga', 'Fasilitas', 'Hasil'];

const sportsList: { key: SportKey; label: string; icon: string }[] = [
    { key: 'futsal', label: 'Futsal', icon: 'futsal-goal' },
    { key: 'miniSoccer', label: 'Mini Soccer', icon: 'soccer-ball' },
    { key: 'padel', label: 'Padel', icon: 'padel-racket' },
    { key: 'badminton', label: 'Badminton', icon: 'shuttlecock' },
    { key: 'basketball', label: 'Basketball', icon: 'basketball-ball' },
    { key: 'lainnya', label: 'Lainnya', icon: 'plus' },
];

const futsalVariants: { key: FutsalVariant; label: string; ukuran: string }[] = [
    { key: 'compact', label: 'Compact / Rekreasi', ukuran: '15 x 30 m' },
    { key: 'standard', label: 'Standard', ukuran: '18 x 35 m' },
    { key: 'large', label: 'Large', ukuran: '20 x 40 m' },
    { key: 'kompetisi', label: 'Kompetisi', ukuran: '22 x 40 m' },
];

const facilitiesList: { key: FacilityKey; label: string; icon: string }[] = [
    { key: 'parkir', label: 'Area Parkir', icon: 'parking' },
    { key: 'toilet', label: 'Toilet', icon: 'toilet' },
    { key: 'ruangGanti', label: 'Ruang Ganti', icon: 'shower' },
    { key: 'kafe', label: 'Kafe', icon: 'coffee-cup' },
    { key: 'resepsionis', label: 'Resepsionis', icon: 'building' },
    { key: 'musala', label: 'Musala', icon: 'prayer-room' },
    { key: 'gym', label: 'Gym', icon: 'dumbbell' },
    { key: 'retail', label: 'Area Retail', icon: 'shopping-bag' },
    { key: 'gudang', label: 'Gudang', icon: 'storage-box' },
    { key: 'lainnya', label: 'Lainnya', icon: 'plus' },
];

export const budgetOptions: { key: Exclude<BudgetKey, 'custom'>; label: string }[] = [
    { key: 'di_bawah_1m', label: 'Di bawah Rp 1 Miliar' },
    { key: 'satu_dua_m', label: 'Rp 1 sampai 2 Miliar' },
    { key: 'dua_tiga_m', label: 'Rp 2 sampai 3 Miliar' },
    { key: 'tiga_lima_m', label: 'Rp 3 sampai 5 Miliar' },
    { key: 'lima_plus_m', label: 'Rp 5 Miliar ke atas' },
];

const landPresets = [
    { p: 20, l: 30 },
    { p: 30, l: 30 },
    { p: 35, l: 40 },
    { p: 45, l: 35 },
];

const facilityIconByKey: Record<string, string> = Object.fromEntries(facilitiesList.map((f) => [f.key, f.icon]));
const sportIconByKey: Record<string, string> = Object.fromEntries(sportsList.map((s) => [s.key, s.icon]));

export default function SportsPlanner() {
    const [step, setStep] = useState(1);
    const [panjang, setPanjang] = useState<number | ''>('');
    const [lebar, setLebar] = useState<number | ''>('');
    const [landChoice, setLandChoice] = useState<string | null>(null);
    const [budgetKey, setBudgetKey] = useState<BudgetKey | null>(null);
    const [budgetCustom, setBudgetCustom] = useState<number | ''>('');
    const [selectedSports, setSelectedSports] = useState<SportKey[]>([]);
    const [futsalVariant, setFutsalVariant] = useState<FutsalVariant>('standard');
    const [sportLainnyaText, setSportLainnyaText] = useState('');
    const [selectedFacilities, setSelectedFacilities] = useState<FacilityKey[]>([]);
    const [facilityLainnyaText, setFacilityLainnyaText] = useState('');
    const [parkirUnits, setParkirUnits] = useState(6);
    const [hasil, setHasil] = useState<PlannerOption[]>([]);
    const [activeOptionIndex, setActiveOptionIndex] = useState(0);

    const [leadNama, setLeadNama] = useState('');
    const [leadWa, setLeadWa] = useState('');
    const [leadEmail, setLeadEmail] = useState('');
    const [leadLokasi, setLeadLokasi] = useState('');
    const [leadError, setLeadError] = useState('');
    const [leadTerkirim, setLeadTerkirim] = useState(false);

    const luas = typeof panjang === 'number' && panjang > 0 && typeof lebar === 'number' && lebar > 0 ? panjang * lebar : 0;
    const aktif: PlannerOption | null = hasil[activeOptionIndex] || null;

    function budgetValue(): [number, number] {
        if (budgetKey === 'custom') {
            const v = typeof budgetCustom === 'number' ? budgetCustom : 0;
            return [v, v];
        }
        return budgetKey ? PLANNER_CONFIG.budgetBands[budgetKey] : [0, 0];
    }

    const canNext =
        step === 1
            ? luas > 0
            : step === 2
              ? !!budgetKey && (budgetKey !== 'custom' || (typeof budgetCustom === 'number' && budgetCustom > 0))
              : step === 3
                ? selectedSports.length > 0
                : true;

    function toggleSport(key: SportKey) {
        setSelectedSports((prev) => (prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]));
    }

    function toggleFacility(key: FacilityKey) {
        setSelectedFacilities((prev) => (prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]));
    }

    function next() {
        if (!canNext) return;
        if (step === 4) {
            const result = buildOptions({
                area: luas,
                userBudget: budgetValue(),
                sports: selectedSports,
                futsalVariant,
                facilities: selectedFacilities,
                parkirUnits,
            });
            setHasil(result);
            setActiveOptionIndex(0);
        }
        setStep((s) => s + 1);
    }

    function back() {
        setStep((s) => Math.max(1, s - 1));
    }

    function reset() {
        setStep(1);
        setHasil([]);
        setLeadTerkirim(false);
    }

    function kirimKonsultasi() {
        setLeadError('');
        if (!leadNama.trim()) return setLeadError('Nama lengkap wajib diisi.');
        if (!leadWa.trim()) return setLeadError('Nomor WhatsApp wajib diisi.');
        if (!leadLokasi.trim()) return setLeadError('Lokasi proyek wajib diisi.');

        const sportsLabel =
            selectedSports.map((s) => (s === 'lainnya' ? sportLainnyaText.trim() || 'Lainnya' : PLANNER_CONFIG.sports[s]?.label || s)).join(', ') || 'Belum ditentukan';
        const facilitiesLabel =
            selectedFacilities.map((f) => (f === 'lainnya' ? facilityLainnyaText.trim() || 'Lainnya' : PLANNER_CONFIG.facilities[f]?.label || f)).join(', ') ||
            'Belum ditentukan';
        const rekomendasi = aktif
            ? `${aktif.courts.map((c) => c.label).join(', ')} plus ${aktif.facilities.map((f) => f.label).join(', ')}`
            : 'Belum ada rekomendasi';

        const waText = encodeURIComponent(
            'Halo kak, saya baru coba kalkulator perencanaan sport center di website \u{1F64F}\n\n' +
                '━━━━━━━━━━━━━━━━━━━━\n' +
                '\u{1F464} *INFORMASI PEMESAN*\n' +
                `Nama       : ${leadNama}\n` +
                `WhatsApp   : ${leadWa}\n` +
                `Email      : ${leadEmail || 'Tidak diisi'}\n` +
                `Lokasi     : ${leadLokasi}\n` +
                '━━━━━━━━━━━━━━━━━━━━\n' +
                '\u{1F4D0} *HASIL KALKULATOR*\n' +
                `Lahan      : ${panjang} x ${lebar} m (${formatM2(luas)})\n` +
                `Budget     : ${formatRupiah(budgetValue()[1])}\n` +
                `Olahraga   : ${sportsLabel}\n` +
                `Fasilitas  : ${facilitiesLabel}\n` +
                `Rekomendasi: ${rekomendasi}\n` +
                '━━━━━━━━━━━━━━━━━━━━\n' +
                'Mohon bantu review lebih lanjut ya kak, terima kasih! \u{1F60A}',
        );

        setLeadTerkirim(true);
        setTimeout(() => {
            window.open(`https://wa.me/${WA_NUMBER}?text=${waText}`, '_blank', 'noopener,noreferrer');
        }, 400);
    }

    return (
        <section id="kalkulator" className="mx-auto max-w-5xl px-6 py-16 scroll-mt-24 lg:py-24">
            <Reveal className="mb-10 text-center">
                <span className="mb-3 inline-block text-xs font-bold uppercase tracking-widest text-brand">Kalkulator Interaktif</span>
                <h2 className="mb-3 text-3xl font-bold text-stone-900 md:text-4xl">Coba Kalkulator Perencanaan Lapangan</h2>
                <p className="mx-auto max-w-xl text-sm text-stone-500">
                    Isi ukuran lahan, budget, dan kebutuhan Anda. Kami bantu hitung konfigurasi yang mungkin cocok untuk lokasi Anda.
                </p>
            </Reveal>

            <Reveal className="rounded-3xl border border-stone-200 bg-[#f5f5f5] p-5 shadow-sm md:p-8">
                {/* Progress bar */}
                <div className="mb-8 flex items-center justify-between">
                    {stepLabels.map((label, i) => (
                        <div key={label} className="flex flex-1 items-center">
                            <button
                                type="button"
                                disabled={i + 1 > step}
                                onClick={() => {
                                    if (i + 1 < step) setStep(i + 1);
                                }}
                                className={`flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold transition-colors ${
                                    i + 1 === step ? 'bg-brand text-white' : i + 1 < step ? 'cursor-pointer bg-brand-100 text-brand' : 'bg-stone-100 text-stone-400'
                                }`}
                            >
                                {i + 1 < step ? '✓' : i + 1}
                            </button>
                            <span className={`ml-2 hidden text-xs font-bold sm:inline ${i + 1 === step ? 'text-stone-900' : 'text-stone-400'}`}>{label}</span>
                            {i < stepLabels.length - 1 && <div className={`mx-2 h-0.5 flex-1 rounded-full ${i + 1 < step ? 'bg-brand-200' : 'bg-stone-100'}`} />}
                        </div>
                    ))}
                </div>

                {/* Step 1: Land */}
                {step === 1 && (
                    <div>
                        <h3 className="mb-1 text-lg font-bold text-stone-900">Seberapa besar lahan Anda?</h3>
                        <p className="mb-5 text-sm text-stone-500">Pilih salah satu ukuran umum, atau pilih &quot;Lainnya&quot; untuk masukkan ukuran sendiri.</p>

                        <div className="mb-5 flex flex-wrap gap-2">
                            {landPresets.map((preset) => {
                                const key = `${preset.p}x${preset.l}`;
                                return (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => {
                                            setPanjang(preset.p);
                                            setLebar(preset.l);
                                            setLandChoice(key);
                                        }}
                                        className={`rounded-lg border-2 px-3 py-2 text-xs font-bold transition-colors ${
                                            landChoice === key ? 'border-brand bg-brand-50 text-brand' : 'border-stone-200 text-stone-600 hover:border-brand hover:text-brand'
                                        }`}
                                    >
                                        {preset.p} x {preset.l} m
                                    </button>
                                );
                            })}
                            <button
                                type="button"
                                onClick={() => {
                                    setPanjang('');
                                    setLebar('');
                                    setLandChoice('lainnya');
                                }}
                                className={`rounded-lg border-2 px-3 py-2 text-xs font-bold transition-colors ${
                                    landChoice === 'lainnya' ? 'border-brand bg-brand-50 text-brand' : 'border-stone-200 text-stone-600 hover:border-brand hover:text-brand'
                                }`}
                            >
                                Lainnya
                            </button>
                        </div>

                        {landChoice === 'lainnya' && (
                            <div className="mb-5 grid grid-cols-2 gap-4">
                                <div>
                                    <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600">Panjang (meter)</label>
                                    <input
                                        type="number"
                                        min={1}
                                        value={panjang}
                                        onChange={(e) => setPanjang(e.target.value === '' ? '' : Number(e.target.value))}
                                        placeholder="45"
                                        className="w-full rounded-xl border-2 border-stone-200 bg-[#f5f5f5] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600">Lebar (meter)</label>
                                    <input
                                        type="number"
                                        min={1}
                                        value={lebar}
                                        onChange={(e) => setLebar(e.target.value === '' ? '' : Number(e.target.value))}
                                        placeholder="35"
                                        className="w-full rounded-xl border-2 border-stone-200 bg-[#f5f5f5] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none"
                                    />
                                </div>
                            </div>
                        )}

                        {luas > 0 && (
                            <div className="rounded-xl bg-brand-50 p-4 text-center">
                                <p className="text-xs font-bold uppercase tracking-wide text-brand">Total Luas Lahan</p>
                                <p className="text-2xl font-bold text-stone-900">{formatM2(luas)}</p>
                            </div>
                        )}
                    </div>
                )}

                {/* Step 2: Budget */}
                {step === 2 && (
                    <div>
                        <h3 className="mb-1 text-lg font-bold text-stone-900">Berapa estimasi budget Anda?</h3>
                        <p className="mb-5 text-sm text-stone-500">Ini estimasi awal saja, harga final tergantung hasil survey lokasi.</p>

                        <div className="grid gap-2 sm:grid-cols-2">
                            {budgetOptions.map((budget) => (
                                <button
                                    key={budget.key}
                                    type="button"
                                    onClick={() => {
                                        setBudgetKey(budget.key);
                                        setBudgetCustom('');
                                    }}
                                    className={`rounded-xl border-2 px-4 py-3 text-left text-sm font-bold transition-colors ${
                                        budgetKey === budget.key ? 'border-brand bg-brand-50 text-brand' : 'border-stone-200 text-stone-700 hover:border-brand'
                                    }`}
                                >
                                    {budget.label}
                                </button>
                            ))}
                            <button
                                type="button"
                                onClick={() => {
                                    setBudgetKey('custom');
                                    setBudgetCustom('');
                                }}
                                className={`rounded-xl border-2 px-4 py-3 text-left text-sm font-bold transition-colors ${
                                    budgetKey === 'custom' ? 'border-brand bg-brand-50 text-brand' : 'border-stone-200 text-stone-700 hover:border-brand'
                                }`}
                            >
                                Lainnya
                            </button>
                        </div>

                        {budgetKey === 'custom' && (
                            <div className="mt-3">
                                <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600">Masukkan budget spesifik (Rp)</label>
                                <input
                                    type="number"
                                    min={0}
                                    value={budgetCustom}
                                    onChange={(e) => setBudgetCustom(e.target.value === '' ? '' : Number(e.target.value))}
                                    placeholder="2000000000"
                                    className="w-full rounded-xl border-2 border-brand bg-[#f5f5f5] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none"
                                />
                            </div>
                        )}
                    </div>
                )}

                {/* Step 3: Sports */}
                {step === 3 && (
                    <div>
                        <h3 className="mb-1 text-lg font-bold text-stone-900">Apa yang ingin Anda bangun?</h3>
                        <p className="mb-5 text-sm text-stone-500">Boleh pilih lebih dari satu jenis olahraga.</p>

                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            {sportsList.map((sport) => (
                                <button
                                    key={sport.key}
                                    type="button"
                                    onClick={() => toggleSport(sport.key)}
                                    className={`flex flex-col items-center gap-2 rounded-xl border-2 px-3 py-4 text-center transition-colors ${
                                        selectedSports.includes(sport.key) ? 'border-brand bg-brand-50' : 'border-stone-200 hover:border-brand'
                                    }`}
                                >
                                    <Icon name={sport.icon} size={22} className="text-brand" />
                                    <span className="text-xs font-bold text-stone-700">{sport.label}</span>
                                </button>
                            ))}
                        </div>

                        {selectedSports.includes('futsal') && (
                            <div className="mt-5 rounded-xl border border-stone-200 p-4">
                                <p className="mb-3 text-xs font-bold uppercase tracking-wide text-stone-600">Pilih konfigurasi lapangan futsal</p>
                                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    {futsalVariants.map((variant) => (
                                        <button
                                            key={variant.key}
                                            type="button"
                                            onClick={() => setFutsalVariant(variant.key)}
                                            className={`rounded-lg border-2 px-2 py-2.5 text-center transition-colors ${
                                                futsalVariant === variant.key ? 'border-brand bg-brand-50 text-brand' : 'border-stone-200 text-stone-600 hover:border-brand'
                                            }`}
                                        >
                                            <span className="block text-xs font-bold">{variant.label}</span>
                                            <span className="block text-[11px] text-stone-400">{variant.ukuran}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {selectedSports.includes('lainnya') && (
                            <div className="mt-5 rounded-xl border border-stone-200 p-4">
                                <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600">Sebutkan jenis olahraga lainnya</label>
                                <input
                                    type="text"
                                    value={sportLainnyaText}
                                    onChange={(e) => setSportLainnyaText(e.target.value)}
                                    placeholder="Contoh: Voli, Tenis, Panjat Tebing"
                                    className="w-full rounded-xl border-2 border-stone-200 bg-[#f5f5f5] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none"
                                />
                            </div>
                        )}
                    </div>
                )}

                {/* Step 4: Facilities */}
                {step === 4 && (
                    <div>
                        <h3 className="mb-1 text-lg font-bold text-stone-900">Apa lagi yang Anda inginkan di lokasi?</h3>
                        <p className="mb-5 text-sm text-stone-500">Boleh pilih lebih dari satu, atau lewati kalau belum tahu.</p>

                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            {facilitiesList.map((facility) => (
                                <button
                                    key={facility.key}
                                    type="button"
                                    onClick={() => toggleFacility(facility.key)}
                                    className={`flex flex-col items-center gap-2 rounded-xl border-2 px-3 py-4 text-center transition-colors ${
                                        selectedFacilities.includes(facility.key) ? 'border-brand bg-brand-50' : 'border-stone-200 hover:border-brand'
                                    }`}
                                >
                                    <Icon name={facility.icon} size={22} className="text-brand" />
                                    <span className="text-xs font-bold text-stone-700">{facility.label}</span>
                                </button>
                            ))}
                        </div>

                        {selectedFacilities.includes('parkir') && (
                            <div className="mt-5 flex items-center justify-between rounded-xl border border-stone-200 p-4">
                                <div>
                                    <p className="text-xs font-bold uppercase tracking-wide text-stone-600">Perkiraan kapasitas parkir</p>
                                    <p className="text-xs text-stone-400">Jumlah mobil yang ingin ditampung</p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setParkirUnits((n) => Math.max(1, n - 1))}
                                        className="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 font-bold text-stone-600 hover:border-brand"
                                    >
                                        -
                                    </button>
                                    <span className="w-6 text-center text-sm font-bold text-stone-900">{parkirUnits}</span>
                                    <button
                                        type="button"
                                        onClick={() => setParkirUnits((n) => Math.min(30, n + 1))}
                                        className="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-stone-200 font-bold text-stone-600 hover:border-brand"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                        )}

                        {selectedFacilities.includes('lainnya') && (
                            <div className="mt-5 rounded-xl border border-stone-200 p-4">
                                <label className="mb-1.5 block text-xs font-bold uppercase tracking-wide text-stone-600">Sebutkan fasilitas lainnya</label>
                                <input
                                    type="text"
                                    value={facilityLainnyaText}
                                    onChange={(e) => setFacilityLainnyaText(e.target.value)}
                                    placeholder="Contoh: Mushola, Ruang Tunggu VIP"
                                    className="w-full rounded-xl border-2 border-stone-200 bg-[#f5f5f5] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none"
                                />
                            </div>
                        )}
                    </div>
                )}

                {/* Step 5: Result */}
                {step === 5 && (
                    <div>
                        <div className="mb-6 text-center">
                            <h3 className="mb-1 text-lg font-bold text-stone-900">Hasil Perencanaan Anda</h3>
                            <p className="text-sm text-stone-500">
                                Lahan <span className="font-bold text-stone-700">{panjang} x {lebar} m</span>, luas{' '}
                                <span className="font-bold text-stone-700">{formatM2(luas)}</span>
                            </p>
                        </div>

                        {hasil.length > 0 && (
                            <div className="mb-5 flex flex-wrap justify-center gap-2">
                                {hasil.map((opsi, i) => (
                                    <button
                                        key={opsi.nama}
                                        type="button"
                                        onClick={() => setActiveOptionIndex(i)}
                                        className={`rounded-full border-2 px-4 py-2 text-xs font-bold transition-colors ${
                                            activeOptionIndex === i ? 'border-brand bg-brand text-white' : 'border-stone-200 text-stone-600 hover:border-brand'
                                        }`}
                                    >
                                        Opsi {i + 1}: {opsi.nama}
                                    </button>
                                ))}
                            </div>
                        )}

                        {hasil.length === 0 && (
                            <div className="rounded-xl border-2 border-dashed border-stone-200 p-8 text-center text-sm text-stone-500">
                                Belum ada konfigurasi yang cocok untuk kombinasi lahan dan pilihan Anda. Coba perbesar lahan atau kurangi jumlah fasilitas, atau langsung
                                konsultasi dengan tim kami di bawah.
                            </div>
                        )}

                        {aktif && (
                            <div>
                                <div className="grid gap-5 md:grid-cols-5">
                                    {/* Left: details */}
                                    <div className="md:col-span-2">
                                        <div className="mb-4 rounded-2xl border border-stone-200 p-5">
                                            <div className="mb-3 flex items-center justify-between">
                                                <span className="text-xs font-bold uppercase tracking-wide text-stone-400">Planning Score</span>
                                                <span className="text-xl font-bold text-brand">{aktif.skor}/100</span>
                                            </div>
                                            <p className="mb-4 text-[11px] text-stone-400">Skor internal untuk membandingkan opsi, bukan penilaian teknik resmi.</p>

                                            <p className="mb-1 text-xs font-bold uppercase tracking-wide text-stone-400">Lapangan</p>
                                            <ul className="mb-3 space-y-1 text-sm text-stone-700">
                                                {aktif.courts.map((court, i) => (
                                                    <li key={i} className="flex items-center gap-2">
                                                        <Icon name={sportIconByKey[court.sportKey] || 'plus'} size={18} className="flex-shrink-0 text-brand" />
                                                        <span>
                                                            {court.label} ({court.width} x {court.length} m)
                                                        </span>
                                                    </li>
                                                ))}
                                            </ul>

                                            <p className="mb-1 text-xs font-bold uppercase tracking-wide text-stone-400">Fasilitas</p>
                                            <ul className="mb-4 space-y-1 text-sm text-stone-700">
                                                {aktif.facilities.map((fac, i) => (
                                                    <li key={i} className="flex items-center gap-2">
                                                        <Icon name={facilityIconByKey[fac.key] || 'plus'} size={18} className="flex-shrink-0 text-brand" />
                                                        <span>{fac.label}</span>
                                                    </li>
                                                ))}
                                                {aktif.facilities.length === 0 && <li className="text-stone-400">Tidak ada fasilitas tambahan</li>}
                                            </ul>

                                            <div className="mb-3 flex items-center justify-between border-t border-stone-100 pt-3 text-sm">
                                                <span className="text-stone-500">Estimasi penggunaan lahan</span>
                                                <span className="font-bold text-stone-900">{Math.round(aktif.landUsedPercent)}%</span>
                                            </div>

                                            <span
                                                className={`inline-flex items-center rounded-full px-3 py-1.5 text-xs font-bold ${
                                                    aktif.budgetStatus === 'feasible'
                                                        ? 'bg-brand-100 text-brand'
                                                        : aktif.budgetStatus === 'tight'
                                                          ? 'bg-amber-pale text-amber'
                                                          : 'bg-danger-pale text-danger'
                                                }`}
                                            >
                                                {budgetStatusLabel(aktif.budgetStatus)}
                                            </span>

                                            <p className="mt-4 text-sm italic leading-relaxed text-stone-500">{aktif.alasan}</p>
                                        </div>
                                    </div>

                                    {/* Right: conceptual layout */}
                                    <div className="md:col-span-3">
                                        <div className="mb-2 flex items-center justify-between">
                                            <p className="text-xs font-bold uppercase tracking-wide text-stone-400">Tata Letak Konseptual</p>
                                            <p className="text-[11px] italic text-stone-400">Bukan gambar teknik</p>
                                        </div>
                                        <div className="flex min-h-[220px] flex-wrap gap-1.5 rounded-2xl border-2 border-dashed border-stone-200 bg-[#f5f5f5] p-1.5">
                                            {aktif.layout.map((blok, i) => (
                                                <div
                                                    key={i}
                                                    className={`flex min-h-[90px] flex-col items-center justify-center gap-1 rounded-xl border p-2 text-center ${
                                                        blok.tipe === 'lapangan' ? 'border-brand-300 bg-brand-100' : 'border-stone-200 bg-[#f5f5f5]'
                                                    }`}
                                                    style={{ flexBasis: `${blok.persen}%`, flexGrow: 1 }}
                                                >
                                                    <Icon name={(facilityIconByKey[blok.key] || sportIconByKey[blok.key] || 'plus') as string} size={18} className="text-brand" />
                                                    <span className="text-[11px] font-bold leading-tight text-stone-700">{blok.label}</span>
                                                    <span className="text-[10px] text-stone-400">{blok.sub}</span>
                                                </div>
                                            ))}
                                        </div>

                                        <div className="mt-4 rounded-2xl border border-stone-200 p-4">
                                            <div className="mb-2 flex items-center justify-between text-sm">
                                                <span className="text-stone-500">Budget Anda</span>
                                                <span className="font-bold text-stone-900">{formatRupiah(budgetValue()[1])}</span>
                                            </div>
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-stone-500">Estimasi proyek (opsi ini)</span>
                                                <span className="font-bold text-stone-900">{formatRupiahRange(aktif.estimasiBiaya)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <p className="mt-6 rounded-xl bg-stone-50 p-4 text-xs leading-relaxed text-stone-500">
                                    Kalkulator ini memberikan estimasi perencanaan awal saja. Ukuran lapangan aktual, kebutuhan bangunan, parkir, garis sempadan, drainase,
                                    kebutuhan struktur, regulasi setempat, dan biaya konstruksi final harus dikonfirmasi oleh tim teknis kami.
                                </p>
                            </div>
                        )}

                        {/* Lead form */}
                        <div className="mt-8 rounded-2xl border-2 border-brand bg-brand-50 p-6">
                            {leadTerkirim ? (
                                <div className="py-6 text-center">
                                    <div className="mb-3 flex justify-center">
                                        <Icon name="check-circle" size={40} className="text-brand" />
                                    </div>
                                    <h4 className="mb-1 text-base font-bold text-stone-900">Terima kasih!</h4>
                                    <p className="text-sm text-stone-500">Jendela WhatsApp akan segera terbuka berisi ringkasan hasil kalkulator Anda.</p>
                                </div>
                            ) : (
                                <div>
                                    <h4 className="mb-1 text-base font-bold text-stone-900">Mau Rencana Site yang Lebih Akurat?</h4>
                                    <p className="mb-4 text-sm text-stone-500">Kirim detail lahan Anda, tim kami bantu cek tata letak dan opsi konstruksinya.</p>

                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <input
                                            type="text"
                                            value={leadNama}
                                            onChange={(e) => setLeadNama(e.target.value)}
                                            placeholder="Nama Lengkap"
                                            className="w-full rounded-xl border-2 border-stone-200 bg-[#f5f5f5] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none"
                                        />
                                        <input
                                            type="text"
                                            value={leadWa}
                                            onChange={(e) => setLeadWa(e.target.value)}
                                            placeholder="Nomor WhatsApp"
                                            className="w-full rounded-xl border-2 border-stone-200 bg-[#f5f5f5] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none"
                                        />
                                        <input
                                            type="email"
                                            value={leadEmail}
                                            onChange={(e) => setLeadEmail(e.target.value)}
                                            placeholder="Email (opsional)"
                                            className="w-full rounded-xl border-2 border-stone-200 bg-[#f5f5f5] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none"
                                        />
                                        <input
                                            type="text"
                                            value={leadLokasi}
                                            onChange={(e) => setLeadLokasi(e.target.value)}
                                            placeholder="Lokasi Proyek (kota)"
                                            className="w-full rounded-xl border-2 border-stone-200 bg-[#f5f5f5] px-4 py-3 text-sm placeholder-stone-400 focus:border-brand focus:outline-none"
                                        />
                                    </div>

                                    {leadError && <small className="mt-2 block text-xs font-semibold text-danger">{leadError}</small>}

                                    <button
                                        type="button"
                                        onClick={kirimKonsultasi}
                                        className="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-brand py-4 text-sm font-bold text-white shadow-lg shadow-brand-dark/20 transition-colors hover:bg-brand-dark"
                                    >
                                        <Icon name="whatsapp-logo" size={16} className="text-white" />
                                        Dapatkan Konsultasi Gratis
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Nav buttons */}
                {step < 5 && (
                    <div className="mt-6 flex items-center justify-between">
                        {step > 1 ? (
                            <button
                                type="button"
                                onClick={back}
                                className="flex items-center gap-1 rounded-xl border-2 border-stone-200 px-5 py-3 text-sm font-bold text-stone-600 transition-colors hover:border-brand hover:text-brand"
                            >
                                <Icon name="chevron-left" size={16} />
                                Kembali
                            </button>
                        ) : (
                            <span />
                        )}
                        <button
                            type="button"
                            onClick={next}
                            disabled={!canNext}
                            className="ml-auto flex items-center gap-1 rounded-xl bg-brand px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <span>{step === 4 ? 'Lihat Hasil' : 'Lanjut'}</span>
                            <Icon name="chevron-right" size={16} />
                        </button>
                    </div>
                )}
                {step === 5 && (
                    <div className="mt-6 text-center">
                        <button type="button" onClick={reset} className="text-xs font-bold text-stone-400 underline hover:text-brand">
                            Mulai ulang kalkulator
                        </button>
                    </div>
                )}
            </Reveal>
        </section>
    );
}
