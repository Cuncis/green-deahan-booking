import { router } from '@inertiajs/react';
import { Card } from './ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';

export interface DataFilterCabang {
    terpilih: number | null;
    pilihan: { id: number; nama: string }[];
}

/** Branch filter (multi_cabang): reloads only the given props, keeps scroll. */
export default function FilterCabang({ filter, url, only }: { filter: DataFilterCabang; url: string; only: string[] }) {
    const pilih = (nilai: string) =>
        router.get(url, nilai === 'semua' ? {} : { cabang: nilai }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only,
        });

    return (
        <Card className="mb-5 flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
            <span className="text-xs font-bold uppercase tracking-wide text-muted-foreground">Filter Cabang</span>
            <Select value={filter.terpilih ? String(filter.terpilih) : 'semua'} onValueChange={pilih}>
                <SelectTrigger className="sm:w-64" aria-label="Filter cabang">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="semua">Semua Cabang</SelectItem>
                    {filter.pilihan.map((cabang) => (
                        <SelectItem key={cabang.id} value={String(cabang.id)}>
                            {cabang.nama}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </Card>
    );
}
