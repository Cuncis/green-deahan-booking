<script>
    function waPerangkatMobileAtauTablet() {
        const ua = navigator.userAgent || navigator.vendor || '';

        return /android|iphone|ipad|ipod|iemobile|blackberry|opera mini|mobile|tablet/i.test(ua);
    }

    /**
     * wa.me/api.whatsapp.com selalu menampilkan halaman pilihan "Open app /
     * Continue to WhatsApp Web" di desktop. web.whatsapp.com/send adalah
     * endpoint resmi WhatsApp yang langsung masuk ke WhatsApp Web, tanpa
     * halaman pilihan itu.
     */
    function keUrlWhatsAppWeb(url) {
        try {
            const asal = new URL(url, window.location.href);
            const nomor = asal.pathname.replace(/^\//, '');
            const teks = asal.searchParams.get('text');

            const tujuan = new URL('https://web.whatsapp.com/send');
            tujuan.searchParams.set('phone', nomor);
            if (teks) {
                tujuan.searchParams.set('text', teks);
            }

            return tujuan.toString();
        } catch (e) {
            return url;
        }
    }

    window.bukaChatWhatsApp = function (url) {
        if (!url || url === '#') {
            return;
        }

        if (waPerangkatMobileAtauTablet()) {
            // Navigasi di tab yang sama, biar OS yang tangani app link dan
            // langsung buka app WhatsApp. window.open dengan fitur popup
            // malah mencegah OS mendeteksi ini sebagai app link, jadi selalu
            // jatuh ke halaman pilihan "Open app / Continue to WhatsApp Web".
            window.location.href = url;
            return;
        }

        const width = 420;
        const height = 680;
        const left = window.screenX + (window.outerWidth - width) / 2;
        const top = window.screenY + (window.outerHeight - height) / 2;

        window.open(
            keUrlWhatsAppWeb(url),
            'whatsapp-chat',
            `width=${width},height=${height},left=${left},top=${top},noopener,noreferrer`
        );
    };
</script>
