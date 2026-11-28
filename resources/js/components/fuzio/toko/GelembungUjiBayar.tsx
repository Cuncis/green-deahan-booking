import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { TokoSharedProps } from './types';

/**
 * TEMPORARY (BypassBayarToko): sticky test-mode bubble on the right edge,
 * visible only to a logged-in superadmin while test mode is on. Remove once
 * the Mayar sandbox works again.
 *
 * With `url` (order page) the bubble marks the order as paid without Mayar.
 * Without it (checkout) it only says that the Pay button will skip Mayar.
 */
export default function GelembungUjiBayar({ url }: { url?: string | null }) {
    const { flash } = usePage<TokoSharedProps>().props;
    const [memproses, setMemproses] = useState(false);

    const tandaiDibayar = () => {
        if (!url) {
            return;
        }

        router.post(url, {}, { preserveScroll: true, onStart: () => setMemproses(true), onFinish: () => setMemproses(false) });
    };

    return (
        <div className="gd-uji-bayar" role="region" aria-label="Mode uji bayar">
            {url && flash?.success && <p className="gd-uji-bayar-pesan">{flash.success}</p>}
            {url && flash?.error && <p className="gd-uji-bayar-pesan galat">{flash.error}</p>}
            {url ? (
                <button type="button" className="gd-uji-bayar-tombol" onClick={tandaiDibayar} disabled={memproses}>
                    <span className="gd-uji-bayar-label">Mode uji</span>
                    {memproses ? 'Memproses...' : 'Tandai dibayar'}
                </button>
            ) : (
                <div className="gd-uji-bayar-tombol info">
                    <span className="gd-uji-bayar-label">Mode uji</span>
                    Bayar melewati Mayar
                </div>
            )}
        </div>
    );
}
