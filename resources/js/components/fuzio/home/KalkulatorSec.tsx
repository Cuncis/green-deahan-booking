import { useState } from 'react';
import TextAnimation from '../elements/TextAnimation';
import CustomFormSelect from '../elements/CustomFormSelect';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import {
   KALKULATOR_DAYS, KALKULATOR_MONTHS, kalkulatorDefaults, kalkulatorSteps, kalkulatorText, kalkulatorTypes,
} from '../../../data/kalkulator';
import { waLink } from '../../../data/greendeahan';

/**
 * Homepage "Kalkulator" section, ported from the Fuzio clone
 * (features/HomeOne/KalkulatorSec.tsx): how many months until a court pays
 * for itself. Dark brand-green band. Styles: brand-greendeahan.css section 23.
 */

const rupiah = (n: number) => `Rp${Math.round(n).toLocaleString('id-ID')}`;
const toNumber = (s: string) => {
   const n = Number(s);
   return Number.isFinite(n) && n > 0 ? n : 0;
};

// Chart geometry (SVG units): cumulative net profit for 36 months against
// the build cost (dashed "titik balik modal" line).
const CH = { w: 520, h: 180, left: 28, right: 492, top: 28, bottom: 152 };

function PaybackChart({ profit, build, payback }: { profit: number; build: number; payback: number | null }) {
   const maxY = Math.max(profit * KALKULATOR_MONTHS, build, 1);
   const x = (m: number) => CH.left + (m / KALKULATOR_MONTHS) * (CH.right - CH.left);
   const y = (v: number) => CH.bottom - (Math.max(v, 0) / maxY) * (CH.bottom - CH.top);
   const points = Array.from({ length: KALKULATOR_MONTHS + 1 }, (_, m) => `${x(m)},${y(profit * m)}`).join(' ');
   const buildY = y(build);
   const label = payback !== null && payback <= KALKULATOR_MONTHS
      ? `Grafik: modal kembali di bulan ${payback}`
      : `Grafik: modal belum kembali dalam ${KALKULATOR_MONTHS} bulan`;

   return (
      <svg className="calc-chart" viewBox={`0 0 ${CH.w} ${CH.h}`} role="img" aria-label={label}>
         <line x1={CH.left} x2={CH.right} y1={CH.bottom} y2={CH.bottom} stroke="rgba(255,255,255,.25)" />
         <line x1={CH.left} x2={CH.right} y1={buildY} y2={buildY} stroke="rgba(255,255,255,.55)" strokeDasharray="4 4" />
         <text x={CH.right} y={buildY - 6} fill="#fff" fontSize="12" textAnchor="end" opacity=".85">titik balik modal</text>
         <polyline points={points} fill="none" stroke="#feb046" strokeWidth="3" strokeLinejoin="round" />
         {payback !== null && payback <= KALKULATOR_MONTHS && (
            <g>
               <circle cx={x(payback)} cy={buildY} r="6" fill="#feb046" stroke="#fff" strokeWidth="2" />
               <text
                  x={x(payback)}
                  y={buildY + 22}
                  fill="#feb046"
                  fontSize="12"
                  fontWeight="700"
                  textAnchor={payback > KALKULATOR_MONTHS - 4 ? 'end' : payback < 4 ? 'start' : 'middle'}
               >
                  Bulan {payback}
               </text>
            </g>
         )}
         <text x={CH.left} y={CH.h - 6} fill="#fff" fontSize="12" opacity=".8">Bulan 0</text>
         <text x={CH.right} y={CH.h - 6} fill="#fff" fontSize="12" textAnchor="end" opacity=".8">Bulan {KALKULATOR_MONTHS}</text>
      </svg>
   );
}

export default function KalkulatorSec() {
   const initialType = kalkulatorTypes.find((t) => t.value === kalkulatorDefaults.type) ?? kalkulatorTypes[0];
   const [type, setType] = useState(initialType.value);
   // text inputs keep the raw string so the visitor can clear and retype
   const [courts, setCourts] = useState(String(kalkulatorDefaults.courts));
   const [price, setPrice] = useState(String(initialType.rent));
   const [hours, setHours] = useState(kalkulatorDefaults.hours);
   const [ops, setOps] = useState(kalkulatorDefaults.ops);

   const t = kalkulatorTypes.find((k) => k.value === type) ?? initialType;
   const nCourts = Math.min(toNumber(courts), 20);
   const nPrice = toNumber(price);

   const build = t.buildCost * nCourts;
   const revenue = nPrice * hours * KALKULATOR_DAYS * nCourts;
   const cost = revenue * (ops / 100);
   const profit = revenue - cost;
   const payback = profit > 0 && build > 0 ? Math.ceil(build / profit) : null;

   const paybackText = payback === null
      ? 'Belum bisa dihitung'
      : `${payback} bulan`;
   const paybackNote = payback !== null && payback >= 24
      ? `± ${(payback / 12).toLocaleString('id-ID', { maximumFractionDigits: 1 })} tahun`
      : null;

   const waMessage =
      `Halo Green Deahan, hasil kalkulator saya: ${nCourts}x ${t.label}, sewa ${rupiah(nPrice)}/jam, ` +
      `${hours} jam/hari, biaya operasional ${ops}%, perkiraan balik modal ${paybackText}. Mohon dihitung lebih detail.`;

   const onType = (v: string) => {
      setType(v);
      const next = kalkulatorTypes.find((k) => k.value === v);
      if (next) setPrice(String(next.rent));
   };

   return (
      <section className="calc-sec sec-padding" id="kalkulator">
         <div className="container">
            <div className="row">
               <div className="col-lg-8 mx-auto text-center mb-5">
                  <p className="calc-eyebrow">Kalkulator</p>
                  <TextAnimation>
                     <h2 className="sec-title text-info">{kalkulatorText.title}</h2>
                  </TextAnimation>
                  <p className="calc-intro">{kalkulatorText.intro}</p>
               </div>
            </div>

            <div className="row g-4 align-items-stretch">
               {/* inputs */}
               <div className="col-lg-6">
                  <div className="calc-box contact-form">
                     <div className="row">
                        <div className="col-12 form-group">
                           <label className="calc-label">Jenis lapangan</label>
                           <i className="feather-icon icon-clipboard"></i>
                           <CustomFormSelect
                              optionArray={kalkulatorTypes}
                              value={type}
                              onChange={onType}
                              aria-label="Jenis lapangan"
                           />
                        </div>
                        <div className="col-sm-6 form-group">
                           <label className="calc-label" htmlFor="calc-courts">Jumlah lapangan</label>
                           <i className="feather-icon icon-grid"></i>
                           <input
                              id="calc-courts"
                              className="form-control"
                              type="number"
                              inputMode="numeric"
                              min={1}
                              max={20}
                              value={courts}
                              onChange={(e) => setCourts(e.target.value)}
                           />
                        </div>
                        <div className="col-sm-6 form-group">
                           <label className="calc-label" htmlFor="calc-price">Sewa per jam (Rp)</label>
                           <i className="feather-icon icon-tag"></i>
                           <input
                              id="calc-price"
                              className="form-control"
                              type="number"
                              inputMode="numeric"
                              min={0}
                              step={10000}
                              value={price}
                              onChange={(e) => setPrice(e.target.value)}
                           />
                        </div>
                        <div className="col-12 calc-range">
                           <label htmlFor="calc-hours">Jam terisi per hari: <strong>{hours} jam</strong></label>
                           <input id="calc-hours" type="range" min={1} max={17} value={hours} onChange={(e) => setHours(Number(e.target.value))} />
                        </div>
                        <div className="col-12 calc-range">
                           <label htmlFor="calc-ops">Biaya operasional: <strong>{ops}%</strong> dari pendapatan</label>
                           <input id="calc-ops" type="range" min={10} max={60} value={ops} onChange={(e) => setOps(Number(e.target.value))} />
                        </div>
                        <div className="col-12">
                           <p className="calc-note">{kalkulatorText.opsNote}</p>
                        </div>
                     </div>
                  </div>
               </div>

               {/* results */}
               <div className="col-lg-6">
                  <div className="calc-out h-100" aria-live="polite">
                     <div className="calc-out-label">Perkiraan balik modal</div>
                     <div className="calc-big">
                        {paybackText}
                        {paybackNote && <small> ({paybackNote})</small>}
                     </div>
                     <dl className="calc-list">
                        <div><dt>Biaya bangun (estimasi)</dt><dd>{rupiah(build)}</dd></div>
                        <div><dt>Pendapatan per bulan</dt><dd>{rupiah(revenue)}</dd></div>
                        <div><dt>Biaya operasional</dt><dd>{rupiah(cost)}</dd></div>
                        <div><dt>Laba bersih per bulan</dt><dd>{rupiah(profit)}</dd></div>
                        <div className="is-total"><dt>Laba bersih per tahun</dt><dd>{rupiah(profit * 12)}</dd></div>
                     </dl>
                     <PaybackChart profit={profit} build={build} payback={payback} />
                     <p className="calc-fine">{kalkulatorText.disclaimer}</p>
                     <a href={waLink(waMessage)} target="_blank" rel="noopener noreferrer" className="btn btn-primary btn-whatsapp calc-send">
                        <WhatsAppIcon className="me-2" />
                        Kirim hasil ke WhatsApp
                     </a>
                  </div>
               </div>
            </div>

            {/* how it is calculated */}
            <div className="calc-steps">
               <h3>Cara kami menghitung</h3>
               <div className="row g-4">
                  {kalkulatorSteps.map((s, i) => (
                     <div key={s.title} className="col-md-4">
                        <div className="calc-step h-100">
                           <span className="calc-step-no">{i + 1}</span>
                           <h4>{s.title}</h4>
                           <p>{s.text}</p>
                        </div>
                     </div>
                  ))}
               </div>
            </div>
         </div>
      </section>
   );
}
