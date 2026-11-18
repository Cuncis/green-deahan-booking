<x-mail::message>
# Pesanan kamu dibatalkan

Halo {{ $order->customer_name }}, mohon maaf, pesanan **{{ $order->code }}** terpaksa kami batalkan.

**Alasan:** {{ $order->cancel_reason }}

Karena pembayaran sudah kami terima, kami akan mengembalikan dana sebesar **Rp{{ number_format($order->total, 0, ',', '.') }}** (termasuk ongkir) ke metode pembayaran yang kamu pakai. Jika dana belum kamu terima dalam beberapa hari, balas email ini atau hubungi kami lewat WhatsApp dengan menyebut kode pesanan.

<x-mail::button :url="$urlPesanan">
Lihat Status Pesanan
</x-mail::button>

Sekali lagi mohon maaf atas ketidaknyamanannya.

Green Deahan Sport
</x-mail::message>
