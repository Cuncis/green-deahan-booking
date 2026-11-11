import { Timer } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '../../admin/lib/utils';

/** Remaining time on the slot hold; calls onHabis once when it reaches zero. */
export default function HitungMundur({ sampai, onHabis, className }: { sampai: string; onHabis: () => void; className?: string }) {
    const hitungSisa = () => Math.max(0, Math.floor((new Date(sampai).getTime() - Date.now()) / 1000));
    const [sisa, setSisa] = useState(hitungSisa);

    useEffect(() => {
        setSisa(hitungSisa());
        const jeda = window.setInterval(() => {
            const baru = hitungSisa();
            setSisa(baru);
            if (baru === 0) {
                window.clearInterval(jeda);
                onHabis();
            }
        }, 1000);

        return () => window.clearInterval(jeda);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sampai]);

    const menit = Math.floor(sisa / 60);
    const detik = String(sisa % 60).padStart(2, '0');

    return (
        <span className={cn('inline-flex items-center gap-1.5 tabular-nums', sisa < 120 ? 'text-destructive' : 'text-amber', className)} role="timer" aria-live="off">
            <Timer className="size-3.5" aria-hidden="true" />
            Slot ditahan {menit}:{detik}
        </span>
    );
}
