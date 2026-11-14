import TextAnimation from '../elements/TextAnimation';
import { bandingLantai } from '../../../data/bangunLapangan';

/** Inline SVG paths (24px grid, stroke only) for the row labels. */
const IKON: Record<string, string> = {
    harga: 'M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8zM7.5 7.5h.01',
    lokasi: 'M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V11z',
    perbaikan:
        'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z',
    nyaman: 'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z',
    rawat: 'M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3zM19 16v4M17 18h4',
};

/** Interlock vs synthetic grass, the floor question owners ask most. Relative comparison only, no prices. */
export default function BandingLantaiSec() {
    return (
        <section className="feature-sec bg-light sec-padding">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-3">Interlock atau rumput sintetis?</h2>
                </TextAnimation>
                <p className="text-center mb-5">Pilihan lantai paling sering ditanyakan. Ini perbandingan singkatnya.</p>
                <div className="gd-banding-lantai">
                    <table className="gd-banding-tabel">
                        <caption className="visually-hidden">Perbandingan lantai interlock dan rumput sintetis</caption>
                        <thead>
                            <tr>
                                <th scope="col">
                                    <span className="visually-hidden">Hal</span>
                                </th>
                                {bandingLantai.kolom.map((nama, i) => (
                                    <th key={nama} scope="col" className={i === 0 ? 'kolom-interlock' : 'kolom-rumput'}>
                                        <span>{nama}</span>
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {bandingLantai.baris.map((baris) => (
                                <tr key={baris.hal}>
                                    <th scope="row">
                                        <span className="ikon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                                <path d={IKON[baris.ikon]} />
                                            </svg>
                                        </span>
                                        {baris.hal}
                                    </th>
                                    <td>{baris.interlock}</td>
                                    <td>{baris.rumput}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    );
}
