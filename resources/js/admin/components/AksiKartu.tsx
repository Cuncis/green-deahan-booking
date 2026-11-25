import { Link } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '../lib/utils';
import { Button } from './ui/button';

interface AksiKartuProps {
    /** Secondary actions on the left (preview, copy, external links). */
    kiri?: ReactNode;
    /** Edit as a link to another page, or as a click handler when editing happens in a dialog. */
    editHref?: string;
    onEdit?: () => void;
    onHapus?: () => void;
    className?: string;
}

/**
 * Action row for a card or table row in the superadmin area: secondary actions on the
 * left, Edit and Hapus together on the right (Hapus last). Touch targets are 44px high
 * on phones. When the row is too narrow, the Edit and Hapus group wraps below and stays right-aligned.
 */
export default function AksiKartu({ kiri, editHref, onEdit, onHapus, className }: AksiKartuProps) {
    return (
        <div className={cn('mt-auto flex flex-wrap items-center gap-2', className)}>
            {kiri}
            <div className="ml-auto flex items-center gap-2">
                {editHref ? (
                    <Button asChild variant="outline" size="sm">
                        <Link href={editHref}>
                            <Pencil />
                            Edit
                        </Link>
                    </Button>
                ) : (
                    onEdit && (
                        <Button type="button" variant="outline" size="sm" onClick={onEdit}>
                            <Pencil />
                            Edit
                        </Button>
                    )
                )}
                {onHapus && (
                    <Button type="button" variant="ghost" size="sm" className="text-destructive hover:bg-destructive/10 hover:text-destructive" onClick={onHapus}>
                        <Trash2 />
                        Hapus
                    </Button>
                )}
            </div>
        </div>
    );
}
