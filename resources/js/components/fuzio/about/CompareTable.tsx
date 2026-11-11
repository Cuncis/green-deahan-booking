// "Green Deahan vs Kontraktor Biasa" comparison for the "Kenapa Kami" tab.
// Rows restate promises from green-deahan-booking (home.blade.php "Kenapa
// Pilih Kami?" and "Sistem Pembayaran Aman"). No other company is named.
// Styles: resources/fuzio/css/brand-greendeahan.css section 19.
const rows = [
   'Satu tim dari survey sampai serah terima',
   'RAB detail dikirim di awal',
   'Pelunasan setelah lapangan selesai 100%',
   'Garansi resmi tertulis',
   'Jadwal pengerjaan di kontrak',
];

export default function CompareTable() {
   return (
      <div className="compare-card">
         <table className="compare-table">
            <caption className="visually-hidden">Perbandingan Green Deahan dengan kontraktor biasa</caption>
            <thead>
               <tr>
                  <th scope="col"><span className="visually-hidden">Layanan</span></th>
                  <th scope="col" className="is-us">Green Deahan</th>
                  <th scope="col">Kontraktor Biasa</th>
               </tr>
            </thead>
            <tbody>
               {rows.map((row, i) => (
                  <tr key={row} style={{ animationDelay: `${i * 100}ms` }}>
                     <th scope="row">{row}</th>
                     <td className="is-us">
                        <span className="compare-icon is-yes">
                           <i className="feather-icon icon-check" aria-hidden="true" />
                           <span className="visually-hidden">Ya</span>
                        </span>
                     </td>
                     <td>
                        <span className="compare-icon is-no">
                           <i className="feather-icon icon-x" aria-hidden="true" />
                           <span className="visually-hidden">Belum tentu</span>
                        </span>
                     </td>
                  </tr>
               ))}
            </tbody>
         </table>
      </div>
   );
}
