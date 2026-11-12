/**
 * Product photo, or a simple placeholder (no photos from other shops) when
 * the product has none yet.
 */
export default function GambarProduk({ src, alt, label }: { src: string | null; alt: string; label?: string }) {
    if (src) {
        return <img src={src} alt={alt} loading="lazy" />;
    }

    return (
        <div className="gd-toko-placeholder" role="img" aria-label={alt}>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                <path d="M3 6h18" />
                <path d="M16 10a4 4 0 0 1-8 0" />
            </svg>
            {label && <span>{label}</span>}
        </div>
    );
}
