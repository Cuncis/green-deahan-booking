import { Fragment } from 'react';
import TextAnimation from '../elements/TextAnimation';
import { tabelFitur, type NilaiFitur } from '../../../data/sistemBooking';

const PAKET = [
    { kunci: 'basic', nama: 'Basic' },
    { kunci: 'pro', nama: 'Pro' },
    { kunci: 'premium', nama: 'Premium' },
] as const;

function Nilai({ nilai }: { nilai: NilaiFitur }) {
    if (nilai === true) {
        return (
            <span className="gd-ya">
                <i className="feather-icon icon-check" aria-hidden="true"></i>
                <span className="visually-hidden">Termasuk</span>
            </span>
        );
    }

    if (nilai === false) {
        return (
            <span className="gd-tidak">
                <i className="feather-icon icon-minus" aria-hidden="true"></i>
                <span className="visually-hidden">Tidak termasuk</span>
            </span>
        );
    }

    return <span className="gd-teks">{nilai}</span>;
}

/** Full feature comparison: a table on desktop, one card per feature group on phones. */
export default function TabelFiturSec() {
    return (
        <section className="sec-padding pt-0 gd-paket-sec">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-5">Bandingkan Fitur</h2>
                </TextAnimation>

                <div className="gd-tabel-fitur d-none d-md-block">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Fitur</th>
                                {PAKET.map((paket) => (
                                    <th key={paket.kunci} scope="col">
                                        {paket.nama}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {tabelFitur.map((grup) => (
                                <Fragment key={grup.judul}>
                                    <tr className="grup">
                                        <th scope="colgroup" colSpan={4}>
                                            {grup.judul}
                                        </th>
                                    </tr>
                                    {grup.baris.map((baris) => (
                                        <tr key={baris.fitur}>
                                            <th scope="row">{baris.fitur}</th>
                                            {PAKET.map((paket) => (
                                                <td key={paket.kunci}>
                                                    <Nilai nilai={baris[paket.kunci]} />
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </Fragment>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="d-md-none">
                    {tabelFitur.map((grup) => (
                        <div key={grup.judul} className="gd-fitur-hp">
                            <h3>{grup.judul}</h3>
                            {grup.baris.map((baris) => (
                                <div key={baris.fitur} className="baris">
                                    <div className="nama">{baris.fitur}</div>
                                    <div className="nilai">
                                        {PAKET.map((paket) => (
                                            <div key={paket.kunci}>
                                                <span className="label">{paket.nama}</span>
                                                <Nilai nilai={baris[paket.kunci]} />
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    ))}
                </div>

                <p className="text-center mt-4 mb-0" style={{ fontSize: 15 }}>
                    Centang berarti tersedia, minus berarti tidak termasuk, teks berarti tersedia dengan batasan tertentu.
                </p>
            </div>
        </section>
    );
}
