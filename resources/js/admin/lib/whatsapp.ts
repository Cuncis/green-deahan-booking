/**
 * Opens a WhatsApp chat the same way the Blade admin did
 * (components/wa-popup-script.blade.php): phones and tablets navigate so the
 * OS opens the WhatsApp app; desktops get a WhatsApp Web popup without the
 * "Open app / Continue to WhatsApp Web" page that wa.me shows.
 */
const perangkatMobileAtauTablet = (): boolean => /android|iphone|ipad|ipod|iemobile|blackberry|opera mini|mobile|tablet/i.test(navigator.userAgent);

const keUrlWhatsAppWeb = (url: string): string => {
    try {
        const asal = new URL(url, window.location.href);
        const tujuan = new URL('https://web.whatsapp.com/send');
        tujuan.searchParams.set('phone', asal.pathname.replace(/^\//, ''));
        const teks = asal.searchParams.get('text');
        if (teks) {
            tujuan.searchParams.set('text', teks);
        }

        return tujuan.toString();
    } catch {
        return url;
    }
};

export function bukaChatWhatsApp(url: string): void {
    if (!url || url === '#') {
        return;
    }

    if (perangkatMobileAtauTablet()) {
        window.location.href = url;
        return;
    }

    const lebar = 420;
    const tinggi = 680;
    const kiri = window.screenX + (window.outerWidth - lebar) / 2;
    const atas = window.screenY + (window.outerHeight - tinggi) / 2;
    window.open(keUrlWhatsAppWeb(url), 'whatsapp-chat', `width=${lebar},height=${tinggi},left=${kiri},top=${atas},noopener,noreferrer`);
}
