/**
 * Sport center planning calculator, ported 1:1 from the Alpine/vanilla-JS
 * engine in resources/views/components/sports-planner.blade.php so both
 * pages (/konsep and /v2/konsep) produce identical numbers. Pure functions,
 * no DOM/React dependency.
 */

export type SportKey = 'futsal' | 'miniSoccer' | 'padel' | 'badminton' | 'basketball' | 'lainnya';
export type FacilityKey = 'parkir' | 'toilet' | 'ruangGanti' | 'kafe' | 'resepsionis' | 'musala' | 'gym' | 'retail' | 'gudang' | 'lainnya';
export type FutsalVariant = 'compact' | 'standard' | 'large' | 'kompetisi';
export type BudgetKey = 'di_bawah_1m' | 'satu_dua_m' | 'dua_tiga_m' | 'tiga_lima_m' | 'lima_plus_m' | 'custom';
export type BudgetStatus = 'feasible' | 'tight' | 'insufficient';

interface SportDef {
    label: string;
    width: number;
    length: number;
    runoffMeters: number;
    costPerM2: [number, number];
    variants?: Record<FutsalVariant, { width: number; length: number; label: string }>;
}

interface FacilityDef {
    label: string;
    m2PerUnit?: number;
    fixedM2?: number;
    costPerM2: [number, number];
}

export const PLANNER_CONFIG = {
    sports: {
        futsal: {
            label: 'Futsal',
            width: 0,
            length: 0,
            variants: {
                compact: { width: 15, length: 30, label: 'Futsal Compact' },
                standard: { width: 18, length: 35, label: 'Futsal Standard' },
                large: { width: 20, length: 40, label: 'Futsal Large' },
                kompetisi: { width: 22, length: 40, label: 'Futsal Kompetisi' },
            },
            runoffMeters: 2,
            costPerM2: [800000, 1500000],
        },
        miniSoccer: { width: 25, length: 40, runoffMeters: 3, costPerM2: [600000, 1200000], label: 'Mini Soccer' },
        padel: { width: 10, length: 20, runoffMeters: 1.5, costPerM2: [3000000, 5000000], label: 'Padel' },
        badminton: { width: 6.1, length: 13.4, runoffMeters: 1.5, costPerM2: [1500000, 2500000], label: 'Badminton' },
        basketball: { width: 15, length: 28, runoffMeters: 2, costPerM2: [700000, 1300000], label: 'Basketball' },
        lainnya: { width: 15, length: 25, runoffMeters: 2, costPerM2: [700000, 1500000], label: 'Lapangan Lainnya' },
    } as Record<SportKey, SportDef>,
    facilities: {
        parkir: { m2PerUnit: 12.5, costPerM2: [300000, 600000], label: 'Area Parkir' },
        toilet: { fixedM2: 12, costPerM2: [2500000, 3500000], label: 'Toilet' },
        ruangGanti: { fixedM2: 20, costPerM2: [2500000, 3500000], label: 'Ruang Ganti' },
        kafe: { fixedM2: 25, costPerM2: [3000000, 4500000], label: 'Kafe' },
        resepsionis: { fixedM2: 9, costPerM2: [2500000, 4000000], label: 'Resepsionis' },
        musala: { fixedM2: 12, costPerM2: [2500000, 3500000], label: 'Musala' },
        gym: { fixedM2: 40, costPerM2: [3000000, 4500000], label: 'Gym' },
        retail: { fixedM2: 15, costPerM2: [3000000, 4500000], label: 'Area Retail' },
        gudang: { fixedM2: 9, costPerM2: [2000000, 3000000], label: 'Gudang' },
        lainnya: { fixedM2: 15, costPerM2: [2500000, 4000000], label: 'Fasilitas Lainnya' },
    } as Record<FacilityKey, FacilityDef>,
    circulationFactor: 0.12,
    budgetBands: {
        di_bawah_1m: [0, 1000000000],
        satu_dua_m: [1000000000, 2000000000],
        dua_tiga_m: [2000000000, 3000000000],
        tiga_lima_m: [3000000000, 5000000000],
        lima_plus_m: [5000000000, 8000000000],
    } as Record<Exclude<BudgetKey, 'custom'>, [number, number]>,
    scoringWeights: { landFit: 0.4, budgetFit: 0.3, facilityCompleteness: 0.15, priorities: 0.15 },
};

export interface CourtResult {
    sportKey: SportKey;
    variantKey: FutsalVariant | null;
    label: string;
    width: number;
    length: number;
    areaWithRunoff: number;
    costRange: [number, number];
}

export interface FacilityResult {
    key: FacilityKey;
    label: string;
    m2: number;
    costRange: [number, number];
}

export interface LayoutBlock {
    tipe: 'lapangan' | 'fasilitas';
    key: string;
    label: string;
    sub: string;
    persen: number;
}

export interface PlannerOption {
    nama: string;
    courts: CourtResult[];
    facilities: FacilityResult[];
    landUsedM2: number;
    landUsedPercent: number;
    estimasiBiaya: [number, number];
    alasan: string;
    layout: LayoutBlock[];
    budgetStatus: BudgetStatus;
    skor: number;
}

export interface PlannerInput {
    area: number;
    userBudget: [number, number];
    sports: SportKey[];
    futsalVariant: FutsalVariant;
    facilities: FacilityKey[];
    parkirUnits: number;
}

function luasLapangan(sportKey: SportKey, variantKey: FutsalVariant | null): CourtResult {
    const sport = PLANNER_CONFIG.sports[sportKey];
    const dim = sportKey === 'futsal' ? sport.variants![variantKey || 'standard'] : sport;
    const runoff = sport.runoffMeters;
    const width = dim.width + runoff * 2;
    const length = dim.length + runoff * 2;
    return {
        sportKey,
        variantKey: sportKey === 'futsal' ? variantKey || 'standard' : null,
        label: dim.label,
        width: Math.round(dim.width * 10) / 10,
        length: Math.round(dim.length * 10) / 10,
        areaWithRunoff: width * length,
        costRange: [sport.costPerM2[0] * dim.width * dim.length, sport.costPerM2[1] * dim.width * dim.length],
    };
}

function luasFasilitas(key: FacilityKey, units?: number): FacilityResult {
    const fac = PLANNER_CONFIG.facilities[key];
    const m2 = fac.m2PerUnit ? fac.m2PerUnit * (units || 1) : fac.fixedM2!;
    return { key, label: fac.label, m2, costRange: [fac.costPerM2[0] * m2, fac.costPerM2[1] * m2] };
}

function totalWithCirculation(m2: number): number {
    return m2 * (1 + PLANNER_CONFIG.circulationFactor);
}

function landFitScore(pct: number): number {
    if (pct > 100) return 0;
    if (pct >= 70 && pct <= 95) return 1;
    if (pct < 70) return pct / 70;
    return 1 - ((pct - 95) / 5) * 0.4;
}

function budgetStatusAndScore(estimasiBiaya: [number, number], userBudget: [number, number]): { status: BudgetStatus; score: number } {
    const [, uMax] = userBudget;
    const [oMin, oMax] = estimasiBiaya;
    if (uMax >= oMax) return { status: 'feasible', score: 1 };
    if (uMax >= oMin) return { status: 'tight', score: 0.6 };
    return { status: 'insufficient', score: 0.2 };
}

function scoreOption(option: Omit<PlannerOption, 'budgetStatus' | 'skor'>, input: PlannerInput): PlannerOption {
    const landFit = landFitScore(option.landUsedPercent);
    const budget = budgetStatusAndScore(option.estimasiBiaya, input.userBudget);
    const requested = input.facilities.length || 1;
    const included = option.facilities.filter((f) => input.facilities.includes(f.key)).length;
    const facilityCompleteness = input.facilities.length === 0 ? 1 : included / requested;
    const sportsIncluded = new Set(option.courts.map((c) => c.sportKey));
    const priorities = input.sports.length === 0 ? 1 : input.sports.filter((s) => sportsIncluded.has(s)).length / input.sports.length;

    const w = PLANNER_CONFIG.scoringWeights;
    const skor = Math.round(100 * (landFit * w.landFit + budget.score * w.budgetFit + facilityCompleteness * w.facilityCompleteness + priorities * w.priorities));

    return { ...option, budgetStatus: budget.status, skor: Math.max(0, Math.min(100, skor)) };
}

function buildLayout(courts: CourtResult[], facilities: FacilityResult[]): LayoutBlock[] {
    const totalArea = courts.reduce((s, c) => s + c.areaWithRunoff, 0) + facilities.reduce((s, f) => s + f.m2, 0);
    const blocks: LayoutBlock[] = [];
    courts.forEach((c) => {
        blocks.push({ tipe: 'lapangan', key: c.sportKey, label: c.label, sub: `${c.width} x ${c.length} m`, persen: Math.max(18, Math.round((c.areaWithRunoff / totalArea) * 100)) });
    });
    facilities.forEach((f) => {
        blocks.push({ tipe: 'fasilitas', key: f.key, label: f.label, sub: `${Math.round(f.m2)} m2`, persen: Math.max(12, Math.round((f.m2 / totalArea) * 100)) });
    });
    return blocks;
}

function makeOption(nama: string, courts: CourtResult[], facilityKeys: FacilityKey[], alasan: string, input: PlannerInput, parkirUnits: number): PlannerOption {
    const facilities = facilityKeys
        .filter((k) => k !== 'lainnya' && PLANNER_CONFIG.facilities[k])
        .map((k) => luasFasilitas(k, k === 'parkir' ? parkirUnits : 1));

    const rawArea = courts.reduce((s, c) => s + c.areaWithRunoff, 0) + facilities.reduce((s, f) => s + f.m2, 0);
    const landUsedM2 = totalWithCirculation(rawArea);
    const landUsedPercent = input.area > 0 ? (landUsedM2 / input.area) * 100 : 0;

    const costMin = courts.reduce((s, c) => s + c.costRange[0], 0) + facilities.reduce((s, f) => s + f.costRange[0], 0);
    const costMax = courts.reduce((s, c) => s + c.costRange[1], 0) + facilities.reduce((s, f) => s + f.costRange[1], 0);

    const base = {
        nama,
        courts,
        facilities,
        landUsedM2,
        landUsedPercent,
        estimasiBiaya: [costMin, costMax] as [number, number],
        alasan,
        layout: buildLayout(courts, facilities),
    };

    return scoreOption(base, input);
}

export function buildOptions(input: PlannerInput): PlannerOption[] {
    const { sports, futsalVariant, facilities, parkirUnits } = input;
    if (sports.length === 0) return [];

    const options: PlannerOption[] = [];
    const primary = sports.find((s) => s !== 'lainnya') || sports[0];

    // Opsi 1: Konfigurasi Seimbang, satu lapangan tiap olahraga terpilih
    const balancedCourts = sports.filter((s) => PLANNER_CONFIG.sports[s]).map((s) => luasLapangan(s, s === 'futsal' ? futsalVariant : null));
    if (balancedCourts.length > 0) {
        options.push(
            makeOption(
                'Konfigurasi Seimbang',
                balancedCourts,
                facilities,
                'Opsi ini memberi keseimbangan antara ukuran lapangan, fasilitas pendukung, dan sisa lahan.',
                input,
                parkirUnits,
            ),
        );
    }

    // Opsi 2: Maksimal jumlah lapangan (varian terkecil dari olahraga utama)
    if (PLANNER_CONFIG.sports[primary]) {
        const compactCourt = luasLapangan(primary, 'compact');
        const minimalFacilities = facilities.filter((f) => (['toilet', 'parkir'] as FacilityKey[]).includes(f));
        const reservedForFacilities = minimalFacilities.reduce((s, f) => {
            const fac = PLANNER_CONFIG.facilities[f];
            return s + (fac.m2PerUnit ? fac.m2PerUnit * Math.min(parkirUnits, 3) : fac.fixedM2!);
        }, 0);
        const usableForCourts = input.area - reservedForFacilities * (1 + PLANNER_CONFIG.circulationFactor);
        const maxCount = Math.floor(usableForCourts / (compactCourt.areaWithRunoff * (1 + PLANNER_CONFIG.circulationFactor)));

        if (maxCount >= 2) {
            const courts = Array.from({ length: Math.min(maxCount, 4) }, () => luasLapangan(primary, 'compact'));
            options.push(
                makeOption(
                    'Maksimal Jumlah Lapangan',
                    courts,
                    minimalFacilities,
                    'Opsi ini memaksimalkan jumlah lapangan yang bisa dibangun, dengan fasilitas pendukung seminimal mungkin.',
                    input,
                    Math.min(parkirUnits, 3),
                ),
            );
        }
    }

    // Opsi 3: Fasilitas lebih lengkap
    if (PLANNER_CONFIG.sports[primary]) {
        const upgradedVariant: FutsalVariant | null = primary === 'futsal' ? (futsalVariant === 'compact' ? 'standard' : 'large') : null;
        const upgradedCourt = luasLapangan(primary, upgradedVariant);
        const extraFacilities = Array.from(new Set<FacilityKey>([...facilities.filter((f) => f !== 'lainnya'), 'toilet', 'ruangGanti', 'resepsionis', 'parkir']));
        options.push(
            makeOption(
                'Fasilitas Lebih Lengkap',
                [upgradedCourt],
                extraFacilities,
                'Opsi ini cocok kalau Anda ingin membangun sport center dengan fasilitas paling lengkap sejak awal.',
                input,
                Math.max(parkirUnits, 6),
            ),
        );
    }

    return options
        .filter((o) => o.landUsedPercent <= 140)
        .sort((a, b) => b.skor - a.skor)
        .slice(0, 3);
}

export function formatM2(n: number): string {
    return `${new Intl.NumberFormat('id-ID').format(Math.round(n))} m2`;
}

export function formatRupiah(n: number): string {
    if (!n) return 'Rp 0';
    if (n >= 1000000000) return `Rp ${(Math.round((n / 1000000000) * 10) / 10).toString().replace('.', ',')} M`;
    return `Rp ${new Intl.NumberFormat('id-ID').format(Math.round(n / 1000000))} Jt`;
}

export function formatRupiahRange(range: [number, number]): string {
    return `${formatRupiah(range[0])} - ${formatRupiah(range[1])}`;
}

export function budgetStatusLabel(status: BudgetStatus): string {
    if (status === 'feasible') return 'Kemungkinan sesuai budget';
    if (status === 'tight') return 'Budget cukup ketat';
    return 'Budget kemungkinan kurang';
}
