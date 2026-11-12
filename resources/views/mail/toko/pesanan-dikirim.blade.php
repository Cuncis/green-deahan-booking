<x-mail::message>
# Pesanan kamu sudah dikirim

Halo {{ $order->customer_name }}, pesanan **{{ $order->code }}** sudah kami kirim.

- Kurir: **{{ $order->courier }}**
- Nomor resi: **{{ $order->tracking_number }}**

Kamu bisa melacak paket di situs kurir memakai nomor resi di atas.

<x-mail::button :url="$urlPesanan">
Lihat Status Pesanan
</x-mail::button>

Terima kasih sudah belanja di Green Deahan Sport.
</x-mail::message>
