import { useState } from 'react';
import GaleriModal from './GaleriModal';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import type { GaleriItem, GaleriKategori } from '../../../data/galeri';
import { waLink } from '../../../data/greendeahan';

/** Fills its tile, like next/image `fill` with object-fit cover. */
const tileImageStyle = { position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover' } as const;

/**
 * Port of the Fuzio clone's gallery (features/galeri/GaleriSec.tsx): category
 * filter with counts, photo grid with big tiles and hover captions. Clicking a
 * photo opens the project popup (GaleriModal). Styles: brand-greendeahan.css section 21.
 */
export default function GaleriSec({ kategori, items }: { kategori: GaleriKategori[]; items: GaleriItem[] }) {
    const [filter, setFilter] = useState('semua');
    const [index, setIndex] = useState(-1);

    const shown = filter === 'semua' ? items : items.filter((item) => item.kategori === filter);
    const count = (id: string) => (id === 'semua' ? items.length : items.filter((item) => item.kategori === id).length);

    return (
        <section className="gallery-masionary galeri-sec sec-padding">
            <div className="container">
                <div className="galeri-filter" role="group" aria-label="Filter kategori proyek">
                    {kategori.map((tab) => (
                        <button key={tab.id} type="button" className={filter === tab.id ? 'active' : ''} aria-pressed={filter === tab.id} onClick={() => setFilter(tab.id)}>
                            {tab.label} <span className="count">{count(tab.id)}</span>
                        </button>
                    ))}
                </div>

                {shown.length === 0 ? (
                    <p className="text-center py-5">Belum ada foto untuk kategori ini.</p>
                ) : (
                    // key: replay the fade-in when the filter changes
                    <div className="galeri-grid" key={filter}>
                        {shown.map((item, itemIndex) => (
                            <button
                                key={item.id}
                                type="button"
                                className={`galeri-item${item.besar ? ' is-big' : ''}`}
                                style={{ animationDelay: `${Math.min(itemIndex, 8) * 50}ms` }}
                                onClick={() => setIndex(itemIndex)}
                                aria-label={`Lihat foto: ${item.judul}, ${item.kota}`}
                            >
                                <img src={item.foto} alt={item.judul} loading="lazy" style={tileImageStyle} />
                                <span className="galeri-overlay" aria-hidden="true">
                                    <i className="feather-icon icon-maximize-2" />
                                    <strong>{item.judul}</strong>
                                    <span>
                                        {item.kota} · {item.material}
                                    </span>
                                </span>
                            </button>
                        ))}
                    </div>
                )}

                <div className="galeri-cta text-center">
                    <p>Tertarik membangun lapangan seperti ini?</p>
                    <a href={waLink('Halo Green Deahan, saya lihat galeri proyek dan ingin konsultasi pembuatan lapangan.')} target="_blank" rel="noopener noreferrer" className="btn btn-primary btn-whatsapp">
                        <WhatsAppIcon className="me-2" />
                        Minta Penawaran
                    </a>
                </div>
            </div>

            {index >= 0 && <GaleriModal items={shown} kategori={kategori} index={index} onIndex={setIndex} onClose={() => setIndex(-1)} />}
        </section>
    );
}
