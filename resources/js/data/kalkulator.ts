/**
 * Payback calculator ("Kalkulator") on the homepage, ported from the Fuzio
 * clone (~/remove-later/fuzio/src/data/kalkulatorData.ts). Build cost per
 * court from Green Deahan; default rent: Futsal from the futsal-cost blog
 * article, Padel from the calculator draft, Mini Soccer and Badminton are
 * typical market rates, update when the real ones are known.
 */
export const kalkulatorTypes = [
  { id: 1, value: 'futsal', label: 'Futsal', buildCost: 300_000_000, rent: 150_000 },
  { id: 2, value: 'minisoccer', label: 'Mini Soccer', buildCost: 600_000_000, rent: 400_000 },
  { id: 3, value: 'padel', label: 'Padel', buildCost: 450_000_000, rent: 250_000 },
  { id: 4, value: 'badminton', label: 'Badminton', buildCost: 150_000_000, rent: 60_000 },
];

export const kalkulatorDefaults = {
  type: 'padel',
  courts: 2,
  hours: 8, // jam terisi per hari, slider 1–17
  ops: 30, // biaya operasional, % of revenue, slider 10–60
};

export const KALKULATOR_DAYS = 30; // days per month
export const KALKULATOR_MONTHS = 36; // chart length

export const kalkulatorText = {
  title: 'Berapa lama lapangan Anda balik modal?',
  intro: 'Masukkan perkiraan Anda. Hasilnya bisa langsung dikirim ke tim kami untuk dihitung lebih detail dengan harga asli.',
  opsNote: 'Biaya operasional termasuk listrik, gaji penjaga, sewa lahan, dan perawatan.',
  disclaimer: 'Hasil ini perkiraan. Hitungan pasti mengikuti survey lokasi dan RAB dari tim kami.',
};

export const kalkulatorSteps = [
  { title: 'Pendapatan', text: 'Harga sewa × jam terisi × 30 hari × jumlah lapangan.' },
  { title: 'Laba bersih', text: 'Pendapatan dikurangi biaya operasional bulanan.' },
  { title: 'Balik modal', text: 'Biaya bangun dibagi laba bersih per bulan.' },
];
