import { useState, type MouseEvent } from 'react';
import BlogCard from './BlogCard';
import type { ArtikelKartu } from '../../../data/blog';

const ITEMS_PER_PAGE = 6;

/** Port of Fuzio's /blog grid: 3 cards per row, numbered pager below. */
export default function BlogGrid({ artikel }: { artikel: ArtikelKartu[] }) {
    const [currentPage, setCurrentPage] = useState(1);
    const totalPages = Math.max(1, Math.ceil(artikel.length / ITEMS_PER_PAGE));
    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const currentItems = artikel.slice(startIndex, startIndex + ITEMS_PER_PAGE);

    const goToPage = (event: MouseEvent<HTMLAnchorElement>, page: number) => {
        event.preventDefault();
        if (page < 1 || page > totalPages) {
            return;
        }

        setCurrentPage(page);
        document.querySelector('.blog-page')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    return (
        <section className="blog-page sec-padding">
            <div className="container">
                {artikel.length === 0 ? (
                    <div className="text-center py-5">
                        <h3 className="text-uppercase">Belum ada artikel</h3>
                        <p>Artikel akan segera hadir.</p>
                    </div>
                ) : (
                    <div className="row">
                        {currentItems.map((item) => (
                            <div key={item.id} className="col-lg-4 col-sm-6">
                                <BlogCard item={item} />
                            </div>
                        ))}
                        {totalPages > 1 && (
                            <div className="col-12">
                                <nav className="pager text-center mt-5" aria-label="Halaman artikel">
                                    <a href="#" className="next-btn" aria-label="Halaman sebelumnya" onClick={(event) => goToPage(event, currentPage - 1)}>
                                        <i className="feather-icon icon-arrow-left"></i>
                                    </a>
                                    {Array.from({ length: totalPages }).map((_, index) => (
                                        <a
                                            key={index}
                                            href="#"
                                            className={currentPage === index + 1 ? 'current' : ''}
                                            aria-current={currentPage === index + 1 ? 'page' : undefined}
                                            onClick={(event) => goToPage(event, index + 1)}
                                        >
                                            {index + 1}
                                        </a>
                                    ))}
                                    <a href="#" className="prev-btn" aria-label="Halaman berikutnya" onClick={(event) => goToPage(event, currentPage + 1)}>
                                        <i className="feather-icon icon-arrow-right"></i>
                                    </a>
                                </nav>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </section>
    );
}
