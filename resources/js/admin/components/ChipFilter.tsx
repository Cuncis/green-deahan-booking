import { cn } from '../lib/utils';

export interface PilihanChip {
    nilai: string;
    label: string;
    jumlah?: number;
}

/**
 * Filter chips with an optional count pill, styled like the public Galeri
 * tabs: light green outline, pale green on hover (count pill turns solid),
 * solid green when active. Scrolls sideways on phones, wraps from `sm` up.
 */
export default function ChipFilter({ pilihan, aktif, onPilih, label }: { pilihan: PilihanChip[]; aktif: string; onPilih: (nilai: string) => void; label: string }) {
    return (
        <div
            className="-mx-4 mb-5 flex snap-x scroll-px-4 gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 [&::-webkit-scrollbar]:hidden"
            role="group"
            aria-label={label}
        >
            {pilihan.map((item) => {
                const dipilih = aktif === item.nilai;
                return (
                    <button
                        key={item.nilai}
                        type="button"
                        onClick={() => onPilih(item.nilai)}
                        aria-pressed={dipilih}
                        className={cn(
                            'group inline-flex min-h-10 shrink-0 snap-start items-center gap-1.5 whitespace-nowrap rounded-full border px-4 text-sm font-bold transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green focus-visible:ring-offset-2',
                            dipilih ? 'border-green bg-green text-white' : 'border-green-pale bg-card text-green hover:border-green hover:bg-green-pale/50',
                        )}
                    >
                        {item.label}
                        {item.jumlah !== undefined && (
                            <span
                                className={cn(
                                    'min-w-[1.625rem] rounded-full px-2 text-center text-xs font-bold leading-[1.6] tabular-nums transition-colors duration-200',
                                    dipilih ? 'bg-white text-green' : 'bg-green-pale text-green group-hover:bg-green group-hover:text-white',
                                )}
                            >
                                {item.jumlah}
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}
