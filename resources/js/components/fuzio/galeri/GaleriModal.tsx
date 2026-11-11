import { useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import type { GaleriItem, GaleriKategori } from '../../../data/galeri';
import { waLink } from '../../../data/greendeahan';

interface GaleriModalProps {
    items: GaleriItem[];
    kategori: GaleriKategori[];
    index: number;
    onIndex: (index: number) => void;
    onClose: () => void;
}

/**
 * Port of the Fuzio clone's project popup (features/galeri/GaleriModal.tsx):
 * photo with arrows and counter, info panel with a WhatsApp button, and a
 * thumbnail strip. Esc closes, left/right arrows browse, swipe on touch
 * screens. Styles: brand-greendeahan.css section 22.
 */
export default function GaleriModal({ items, kategori, index, onIndex, onClose }: GaleriModalProps) {
    const item = items[index];
    const total = items.length;
    const closeRef = useRef<HTMLButtonElement>(null);
    const thumbsRef = useRef<HTMLDivElement>(null);
    const touchX = useRef<number | null>(null);

    const kategoriLabel = (id: string) => kategori.find((tab) => tab.id === id)?.label ?? id;
    const go = (step: number) => onIndex((index + step + total) % total);

    // Scroll lock and focus while open; give focus back to the photo on close.
    useEffect(() => {
        const opener = document.activeElement as HTMLElement | null;
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        closeRef.current?.focus();

        return () => {
            document.body.style.overflow = previousOverflow;
            opener?.focus();
        };
    }, []);

    useEffect(() => {
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
            if (event.key === 'ArrowRight') {
                onIndex((index + 1) % total);
            }
            if (event.key === 'ArrowLeft') {
                onIndex((index - 1 + total) % total);
            }
        };
        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [index, total, onIndex, onClose]);

    // Keep the active thumbnail in view.
    useEffect(() => {
        const strip = thumbsRef.current;
        const thumb = strip?.children[index] as HTMLElement | undefined;
        if (strip && thumb) {
            strip.scrollTo({ left: thumb.offsetLeft - strip.clientWidth / 2 + thumb.clientWidth / 2, behavior: 'smooth' });
        }
    }, [index]);

    if (!item) {
        return null;
    }

    return createPortal(
        <div className="galeri-modal" role="dialog" aria-modal="true" aria-labelledby="galeri-modal-title">
            <div className="galeri-modal-backdrop" onClick={onClose} />
            <div className="galeri-modal-dialog">
                <div
                    className="gm-media"
                    onTouchStart={(event) => {
                        touchX.current = event.touches[0].clientX;
                    }}
                    onTouchEnd={(event) => {
                        if (touchX.current === null) {
                            return;
                        }
                        const deltaX = event.changedTouches[0].clientX - touchX.current;
                        if (Math.abs(deltaX) > 50) {
                            go(deltaX < 0 ? 1 : -1);
                        }
                        touchX.current = null;
                    }}
                >
                    {/* key: fade in each new photo */}
                    <img
                        key={item.id}
                        className="gm-photo"
                        src={item.foto}
                        alt={item.judul}
                        style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'contain' }}
                    />
                    {total > 1 && (
                        <>
                            <button type="button" className="gm-nav is-prev" onClick={() => go(-1)} aria-label="Foto sebelumnya">
                                <i className="feather-icon icon-chevron-left" />
                            </button>
                            <button type="button" className="gm-nav is-next" onClick={() => go(1)} aria-label="Foto berikutnya">
                                <i className="feather-icon icon-chevron-right" />
                            </button>
                        </>
                    )}
                    <span className="gm-counter" aria-live="polite">
                        {index + 1} / {total}
                    </span>
                </div>

                <div className="gm-info">
                    <button ref={closeRef} type="button" className="gm-close" onClick={onClose} aria-label="Tutup">
                        <i className="feather-icon icon-x" />
                    </button>
                    <span className="gm-badge">{kategoriLabel(item.kategori)}</span>
                    <h3 id="galeri-modal-title">{item.judul}</h3>
                    <ul className="gm-meta list-unstyled">
                        <li>
                            <i className="feather-icon icon-map-pin" aria-hidden="true" />
                            <span className="visually-hidden">Lokasi: </span>
                            {item.kota}
                        </li>
                        <li>
                            <i className="feather-icon icon-layers" aria-hidden="true" />
                            <span className="visually-hidden">Material: </span>
                            {item.material}
                        </li>
                    </ul>
                    <p>{item.deskripsi}</p>
                    <a
                        href={waLink(`Halo Green Deahan, saya lihat proyek "${item.judul}" (${item.kota}) di galeri dan ingin konsultasi lapangan serupa.`)}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn btn-primary btn-whatsapp gm-cta"
                    >
                        <WhatsAppIcon className="me-2" />
                        Tanya Proyek Serupa
                    </a>
                </div>

                {total > 1 && (
                    <div className="gm-thumbs" ref={thumbsRef}>
                        {items.map((thumb, thumbIndex) => (
                            <button
                                key={thumb.id}
                                type="button"
                                className={thumbIndex === index ? 'active' : ''}
                                onClick={() => onIndex(thumbIndex)}
                                aria-label={`Foto ${thumbIndex + 1}: ${thumb.judul}`}
                                aria-current={thumbIndex === index}
                            >
                                <img src={thumb.foto} alt="" width={96} height={64} loading="lazy" style={{ objectFit: 'cover' }} />
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </div>,
        document.body,
    );
}
