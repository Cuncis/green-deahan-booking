import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '../lib/utils';

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

/** Laravel paginator: "1-15 dari 40" plus previous/next (big enough to tap). */
export default function Pagination({ halaman }: { halaman: Paginated<unknown> }) {
    if (halaman.last_page <= 1) {
        return null;
    }

    const tombol = (url: string | null, label: string, ikon: React.ReactNode) =>
        url ? (
            <Link
                href={url}
                preserveScroll={false}
                prefetch
                className="inline-flex size-11 items-center justify-center rounded-lg border border-border bg-card hover:bg-accent md:size-10"
                aria-label={label}
            >
                {ikon}
            </Link>
        ) : (
            <span className={cn('inline-flex size-11 items-center justify-center rounded-lg border border-border bg-card opacity-40 md:size-10')} aria-hidden="true">
                {ikon}
            </span>
        );

    return (
        <nav className="flex items-center justify-between gap-3 border-t border-border px-4 py-3 md:px-5" aria-label="Halaman">
            <p className="text-sm text-muted-foreground">
                {halaman.from}-{halaman.to} dari {halaman.total}
            </p>
            <div className="flex items-center gap-2">
                {tombol(halaman.prev_page_url, 'Halaman sebelumnya', <ChevronLeft className="size-4" />)}
                <span className="min-w-[4.5rem] text-center text-sm font-semibold">
                    {halaman.current_page} / {halaman.last_page}
                </span>
                {tombol(halaman.next_page_url, 'Halaman berikutnya', <ChevronRight className="size-4" />)}
            </div>
        </nav>
    );
}
