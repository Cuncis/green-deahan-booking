import BlogCard from './BlogCard';
import WhatsAppIcon from '../elements/WhatsAppIcon';
import { BLOG_PATH, articlePath, type ArtikelDetail, type ArtikelKartu } from '../../../data/blog';
import { waLink } from '../../../data/greendeahan';

interface SinglePostProps {
    artikel: ArtikelDetail;
    terkait: ArtikelKartu[];
}

/**
 * Port of the Fuzio clone's article page (features/blog-detail/SinglePost.tsx):
 * photo, meta row, title, body, tags + share bar, WhatsApp box and
 * "Artikel Terkait". Body styles: brand-greendeahan.css section 20.
 */
export default function SinglePost({ artikel, terkait }: SinglePostProps) {
    const pageUrl = encodeURIComponent(`${window.location.origin}${articlePath(artikel.slug)}`);
    const shareText = encodeURIComponent(artikel.judul);
    const share = [
        { label: 'Facebook', icon: 'icon-facebook', href: `https://www.facebook.com/sharer/sharer.php?u=${pageUrl}` },
        { label: 'X (Twitter)', icon: 'icon-twitter', href: `https://twitter.com/intent/tweet?url=${pageUrl}&text=${shareText}` },
        { label: 'LinkedIn', icon: 'icon-linkedin', href: `https://www.linkedin.com/sharing/share-offsite/?url=${pageUrl}` },
    ];

    return (
        <>
            <article className="single-post">
                {artikel.fotoUrl && (
                    <div className="post-media">
                        <img src={artikel.fotoUrl} alt={artikel.judul} width={1200} height={675} className="img-fluid rounded-4 w-100" style={{ aspectRatio: '16 / 9', objectFit: 'cover' }} />
                    </div>
                )}
                <div className="entry-meta mt-3 d-sm-flex mb-5">
                    <span>
                        <i className="feather-icon icon-calendar me-2"></i>
                        {artikel.tanggal}
                    </span>
                    <span>
                        <i className="feather-icon icon-file-text me-2"></i>
                        <a href={BLOG_PATH}>{artikel.kategori}</a>
                    </span>
                    <span>
                        <i className="feather-icon icon-clock me-2"></i>
                        {artikel.menitBaca} Menit Baca
                    </span>
                </div>
                <h2 className="text-uppercase sub-title">{artikel.judul}</h2>

                {/* Article HTML written by our own admins in /superadmin/artikel */}
                <div className="post-body" dangerouslySetInnerHTML={{ __html: artikel.konten }} />

                <div className="article-footer py-3 mt-5 d-sm-flex justify-content-between">
                    <div className="article-tags d-sm-flex align-items-center mb-4 mb-sm-0">
                        <h4 className="m-0 text-uppercase">Tags</h4>
                        <div className="tagcloude widget_tag_cloud ms-3">
                            <a href={BLOG_PATH}>{artikel.kategori}</a>
                        </div>
                    </div>
                    <div className="article-share d-sm-flex align-items-center">
                        <h4 className="m-0 text-uppercase">Bagikan</h4>
                        <div className="social ms-3 mt-3 mt-sm-0">
                            {share.map((item) => (
                                <a key={item.label} href={item.href} target="_blank" rel="noopener noreferrer" aria-label={`Bagikan ke ${item.label}`}>
                                    <i className={`feather-icon ${item.icon}`}></i>
                                </a>
                            ))}
                            <a href={`https://wa.me/?text=${shareText}%20${pageUrl}`} target="_blank" rel="noopener noreferrer" aria-label="Bagikan ke WhatsApp">
                                <WhatsAppIcon size={16} />
                            </a>
                        </div>
                    </div>
                </div>

                <div className="post-cta mt-5 text-center">
                    <p>Tertarik konsultasi soal proyek lapangan Anda?</p>
                    <a href={waLink(`Halo, saya baca artikel ${artikel.judul} dan ingin konsultasi`)} target="_blank" rel="noopener noreferrer" className="btn btn-primary btn-whatsapp">
                        <WhatsAppIcon className="me-2" />
                        Konsultasi Gratis via WhatsApp
                    </a>
                </div>
            </article>

            {terkait.length > 0 && (
                <div className="related-posts mt-5 pt-3">
                    <h4 className="text-uppercase sub-title mb-4 h3">Artikel Terkait</h4>
                    <div className="row">
                        {terkait.map((item) => (
                            <div key={item.id} className="col-lg-4 col-sm-6">
                                <BlogCard item={item} />
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </>
    );
}
