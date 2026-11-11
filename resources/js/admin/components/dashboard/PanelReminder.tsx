import { router } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import { bukaChatWhatsApp } from '../../lib/whatsapp';
import { Badge, type BadgeProps } from '../ui/badge';
import { Button } from '../ui/button';
import { Card, CardHeader, CardTitle } from '../ui/card';

export interface ReminderRingkas {
    id: number | null;
    nama: string;
    lapangan: string;
    waktu: string;
    status: string;
    waUrl: string | null;
    tandaiUrl: string | null;
}

const STATUS: Record<string, { label: string; varian: BadgeProps['variant'] }> = {
    perlu_dikirim: { label: 'Perlu Dikirim', varian: 'pending' },
    terjadwal: { label: 'Terjadwal', varian: 'info' },
    terkirim: { label: 'Terkirim', varian: 'confirmed' },
    gagal: { label: 'Gagal', varian: 'cancelled' },
};

/**
 * "Reminder Otomatis" (Premium). Until the WhatsApp Business API is in,
 * "Kirim Sekarang" opens the chat with the message and marks it sent in one
 * tap (references/notifikasi-whatsapp.md).
 */
export default function PanelReminder({ reminder }: { reminder: ReminderRingkas[] }) {
    const kirim = (item: ReminderRingkas) => {
        if (!item.waUrl || !item.tandaiUrl) {
            return;
        }
        bukaChatWhatsApp(item.waUrl);
        router.post(item.tandaiUrl, {}, { preserveScroll: true });
    };

    return (
        <Card id="reminder" className="mb-6 scroll-mt-24 overflow-hidden">
            <CardHeader className="border-b border-border py-4">
                <CardTitle className="text-xs uppercase tracking-wide text-green">Reminder Otomatis</CardTitle>
            </CardHeader>
            {reminder.length === 0 ? (
                <p className="px-5 py-6 text-center text-sm text-muted-foreground">Belum ada reminder.</p>
            ) : (
                <ul className="divide-y divide-cream">
                    {reminder.map((item, index) => {
                        const status = STATUS[item.status] ?? {
                            label: item.status,
                            varian: 'info',
                        };

                        return (
                            <li key={item.id ?? `jadwal-${index}`} className="flex flex-col gap-3 px-4 py-3.5 md:flex-row md:items-center md:justify-between md:px-5">
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-bold">{item.nama}</span>
                                        <Badge variant={status.varian}>{status.label}</Badge>
                                    </div>
                                    <div className="mt-0.5 text-sm text-muted-foreground">
                                        {item.lapangan}, {item.waktu}
                                    </div>
                                </div>
                                {item.status === 'perlu_dikirim' && item.waUrl && (
                                    <Button size="sm" className="w-full md:w-auto" onClick={() => kirim(item)}>
                                        <MessageCircle />
                                        Kirim Sekarang
                                    </Button>
                                )}
                            </li>
                        );
                    })}
                </ul>
            )}
        </Card>
    );
}
