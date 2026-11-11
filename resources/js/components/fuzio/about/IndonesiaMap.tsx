import IndonesiaMapSvg from '../../../../fuzio/images/indonesia-map.svg';

// Indonesia map with project markers for the "Cakupan Wilayah" tab.
// The map is resources/fuzio/images/indonesia-map.svg (Natural Earth 1:50m, public
// domain, equirectangular). Marker positions are % of that SVG, computed
// from each city's latitude/longitude with the same projection.
// Cities and project counts come from the galeri_items table in
// green-deahan-booking. Cities only a few km apart share one marker.
// Entries without `count` are finished projects not in the gallery yet;
// their tooltip says "Proyek selesai" until the number is filled in.
const markers: { name: string; count?: number; left: number; top: number; office?: boolean }[] = [
   { name: 'Medan', count: 2, left: 8.42, top: 15.61 },
   { name: 'Palembang', count: 1, left: 21.42, top: 52.55 },
   { name: 'Jakarta & Bekasi', count: 3, left: 25.92, top: 70.71 },
   { name: 'Bandung', count: 3, left: 27.57, top: 74.7 },
   { name: 'Semarang', count: 2, left: 33.57, top: 74.97 },
   { name: 'Yogyakarta', count: 1, left: 33.46, top: 79.63 },
   { name: 'Surabaya & Mojokerto', count: 3, left: 38.57, top: 76.61, office: true },
   { name: 'Malang', count: 1, left: 38.31, top: 80.59 },
   { name: 'Bali', count: 3, left: 43.84, top: 84.43 },
   { name: 'Balikpapan', count: 1, left: 47.3, top: 42.78 },
   { name: 'Makassar', count: 1, left: 52.87, top: 64.75 },
   // Not in galeri_items yet
   { name: 'Pontianak', left: 31.27, top: 35.97 },
   { name: 'Banjarmasin', left: 42.5, top: 54.48 },
   { name: 'Manado', left: 64.45, top: 27.53 },
   { name: 'Kendari', left: 59.47, top: 58.29 },
   { name: 'Mataram', left: 45.77, top: 84.06 },
   { name: 'Kupang', left: 61.81, top: 93.02 },
   { name: 'Ambon', left: 71.6, top: 56.59 },
   { name: 'Jayapura', left: 98.45, top: 50.06 },
];

export default function IndonesiaMap() {
   return (
      <div className="wilayah-map">
         <div className="wilayah-map-canvas">
            <div className="wilayah-map-inner">
            <img
               src={IndonesiaMapSvg}
               width={1000}
               height={381}
               alt="Peta Indonesia dengan kota-kota proyek Green Deahan Sport"
            />
            {markers.map((m) => {
               const projek = m.count ? `${m.count} proyek` : 'Proyek selesai';
               return (
               <button
                  key={m.name}
                  type="button"
                  // Markers near an edge open their tooltip inward.
                  className={`map-marker${m.office ? ' is-office' : ''}${m.left < 15 ? ' tip-start' : ''}${m.left > 85 ? ' tip-end' : ''}`}
                  style={{ left: `${m.left}%`, top: `${m.top}%` }}
                  aria-label={`${m.name}: ${projek}${m.office ? ', kantor pusat' : ''}`}
               >
                  <span className="map-tip" aria-hidden="true">
                     <strong>{m.name}</strong>
                     {m.office ? 'Kantor pusat · ' : ''}{projek}
                  </span>
               </button>
               );
            })}
            </div>
         </div>
         <p className="wilayah-map-stats">
            <span><strong>15+</strong> kota</span>
            <span className="dot" aria-hidden="true">·</span>
            <span><strong>100+</strong> proyek</span>
         </p>
      </div>
   );
}
