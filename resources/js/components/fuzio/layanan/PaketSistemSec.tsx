import TextAnimation from '../elements/TextAnimation';
import { addonSistemBooking } from '../../../data/sistemBooking';

export interface PaketSistem {
    kunci: string;
    nama: string;
    tagline: string;
    satuan: string;
    sorotan: string[];
    harga: number;
    batasLapangan: number | null;
    unggulan: boolean;
    daftarUrl: string;
}

/** 1500000 -> "Rp1,5 juta" */
const juta = (nilai: number) => `Rp${(nilai / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} juta`;

/** Package cards (Fuzio's pricing-card) and the add-on list. Prices come from the server. */
export default function PaketSistemSec({ paket, diskonPerpanjanganPersen }: { paket: PaketSistem[]; diskonPerpanjanganPersen: number }) {
    return (
        <section id="paket" className="pricing-sec sec-padding gd-paket-sec">
            <div className="container">
                <TextAnimation>
                    <h2 className="sec-title text-center mb-3">Pilihan Paket</h2>
                </TextAnimation>
                <p className="text-center mx-auto mb-5" style={{ maxWidth: 640 }}>
                    Harga per tahun, sudah termasuk setup dan support 1 tahun. Perpanjangan tahun berikutnya hemat {diskonPerpanjanganPersen}%.
                </p>
                <div className="row g-4">
                    {paket.map((item) => (
                        <div key={item.kunci} className="col-lg-4">
                            <div className={`pricing-card gd-paket rounded-4 h-100${item.unggulan ? ' unggulan' : ''}`}>
                                {item.unggulan && <span className="gd-paket-label">Paling Populer</span>}
                                <h3 className="h4 text-uppercase mb-1">{item.nama}</h3>
                                <p className="gd-paket-tagline">{item.tagline}</p>
                                <div className="gd-paket-harga">
                                    {juta(item.harga)}
                                    <span>{item.satuan}</span>
                                </div>
                                <p className="gd-paket-lapangan">{item.batasLapangan === null ? 'Lapangan tanpa batas' : `Sampai ${item.batasLapangan} lapangan`}</p>
                                <ul>
                                    {item.sorotan.map((fitur) => (
                                        <li key={fitur}>{fitur}</li>
                                    ))}
                                </ul>
                                <a href={item.daftarUrl} className={`btn ${item.unggulan ? 'btn-primary' : 'btn-outline-primary'} w-100 mt-auto`}>
                                    Pilih {item.nama}
                                </a>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="gd-addon mt-5">
                    <h3>Tambahan Opsional</h3>
                    <ul>
                        {addonSistemBooking.map((addon) => (
                            <li key={addon.nama}>
                                <span>{addon.nama}</span>
                                <strong>{addon.harga}</strong>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </section>
    );
}
