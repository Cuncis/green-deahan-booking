// 5-step zigzag road timeline for the "Cara Kerja" tab. Steps are from
// the "Cara Kerja" section of green-deahan-booking
// (resources/views/pages/home.blade.php).
// The road zigzags in its own lane on the left (one SVG); badges sit on
// its corners, alternating left and right in the lane. Each step's text is
// on a card in the column to the right, so nothing covers the road.
// Styles: resources/fuzio/css/brand-greendeahan.css section 18.
const steps = [
   { judul: 'Konsultasi', teks: 'Ceritakan kebutuhan, lokasi, dan budget. Tim kami respon dalam 1 jam.' },
   { judul: 'Survey & Desain', teks: 'Tim kami datang ke lokasi, ukur lahan, dan buat desain lapangan.' },
   { judul: 'Penawaran Harga', teks: 'RAB detail dikirim. Transparan, tanpa biaya tersembunyi.' },
   { judul: 'Konstruksi', teks: 'Dikerjakan tim ahli, tepat waktu. Progress bisa dipantau setiap hari.' },
   { judul: 'Lapangan Siap!', teks: 'Serah terima siap pakai. Pelunasan setelah 100% selesai. Garansi aktif.' },
];

// Badge centres in SVG units: x is % of the road lane width (must match
// the badge `left` values in the CSS), each row is 100 units tall. The SVG
// stretches to the lane (preserveAspectRatio none) and strokes don't
// scale, so the road keeps its width on any screen.
const X_LEFT = 25;
const X_RIGHT = 75;
const xOf = (i: number) => (i % 2 === 0 ? X_LEFT : X_RIGHT);
const roadPath = steps
   .map((_, i) => {
      const x = xOf(i);
      const y = i * 100 + 50;
      return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
   })
   .join(' ');

export default function CaraKerjaTimeline() {
   return (
      <div className="kerja-road">
         <div className="kerja-road-inner">
            <svg
               className="kerja-road-svg"
               viewBox={`0 0 100 ${steps.length * 100}`}
               preserveAspectRatio="none"
               aria-hidden="true"
               focusable="false"
            >
               <path className="road-edge" d={roadPath} />
               <path className="road-surface" d={roadPath} />
               <path className="road-line" d={roadPath} />
            </svg>
            <ol className="list-unstyled mb-0">
               {steps.map((step, i) => {
                  const last = i === steps.length - 1;
                  return (
                     <li
                        key={step.judul}
                        className={`kerja-step${i % 2 ? ' is-right' : ''}${last ? ' is-done' : ''}`}
                        style={{ animationDelay: `${i * 140}ms` }}
                     >
                        <div className="kerja-lane">
                           <span className="kerja-badge" aria-hidden="true">
                              {last ? <i className="feather-icon icon-check" /> : String(i + 1).padStart(2, '0')}
                           </span>
                        </div>
                        <div className="kerja-card">
                           <h4>
                              <span className="visually-hidden">Langkah {i + 1}: </span>
                              {step.judul}
                           </h4>
                           <p>{step.teks}</p>
                        </div>
                     </li>
                  );
               })}
            </ol>
         </div>
      </div>
   );
}
