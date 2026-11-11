import { BLOG_PATH, articlePath, type ArtikelKartu } from '../../../data/blog';

/** Same image ratio as the homepage blog cards (550x360). */
const blogThumbStyle = { aspectRatio: '550 / 360', objectFit: 'cover' } as const;

/** Port of Fuzio's BlogCard, used on /blog and under each article ("Artikel Terkait"). */
export default function BlogCard({ item }: { item: ArtikelKartu }) {
    const href = articlePath(item.slug);

    return (
        <div className="single-entry2 bg-mute rounded-2">
            <div className="entry-media">
                <a href={href}>
                    {item.fotoUrl && <img src={item.fotoUrl} alt={item.judul} width={550} height={360} loading="lazy" style={blogThumbStyle} className="img-fluid" />}
                </a>
            </div>
            <div className="entry-content p-4">
                <div className="entry-meta pb-3">
                    <span>
                        <i className="feather-icon icon-file-text"></i>
                        <a href={BLOG_PATH}>{item.kategori}</a>{' '}
                    </span>
                    <span>
                        <i className="feather-icon icon-clock"></i>
                        {item.menitBaca} Menit Baca
                    </span>
                </div>
                <h3 className="text-uppercase">
                    <a href={href}>{item.judul}</a>
                </h3>
                <p>{item.ringkasan}</p>
                <a href={href} className="btn-link d-block mt-4">
                    Baca Selengkapnya <i className="feather-icon icon-chevrons-right"></i>
                </a>
            </div>
        </div>
    );
}
