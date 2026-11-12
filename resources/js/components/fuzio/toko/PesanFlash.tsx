import { usePage } from '@inertiajs/react';
import type { TokoSharedProps } from './types';

/** Success / error message from the server (session flash). */
export default function PesanFlash() {
    const { flash } = usePage<TokoSharedProps>().props;

    if (!flash?.success && !flash?.error) {
        return null;
    }

    return (
        <div className={`gd-pesan ${flash.error ? 'galat' : 'sukses'}`} role={flash.error ? 'alert' : 'status'}>
            {flash.error ?? flash.success}
            {flash.success && !flash.error && (
                <>
                    {' '}
                    <a href="/keranjang" className="text-decoration-underline">
                        Lihat keranjang
                    </a>
                </>
            )}
        </div>
    );
}
