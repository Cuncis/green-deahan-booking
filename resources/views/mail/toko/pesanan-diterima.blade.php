<x-mail::message>
# Pembayaran kamu sudah kami terima

Halo {{ $order->customer_name }}, terima kasih! Pembayaran untuk pesanan **{{ $order->code }}** sudah masuk dan pesananmu segera kami proses.

<x-mail::table>
| Barang | Jumlah | Subtotal |
|:-------|:------:|---------:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if ($item->variant_name) ({{ $item->variant_name }})@endif | {{ $item->qty }} | Rp{{ number_format($item->unit_price * $item->qty, 0, ',', '.') }} |
@endforeach
@if ($order->discount > 0)
| Diskon{{ $order->promo_code ? ' '.$order->promo_code : '' }} | | -Rp{{ number_format($order->discount, 0, ',', '.') }} |
@endif
| Ongkir{{ $layanan ? ' ('.$layanan.')' : '' }} | | Rp{{ number_format($order->shipping_cost, 0, ',', '.') }} |
| **Total dibayar** | | **Rp{{ number_format($order->total, 0, ',', '.') }}** |
</x-mail::table>

**Dikirim ke:**<br>
{{ $alamat }}

Simpan email ini. Kamu bisa membuka status pesanan kapan saja lewat tombol di bawah, dan kami akan mengabari lagi begitu barangnya dikirim beserta nomor resinya.

<x-mail::button :url="$urlPesanan">
Lihat Status Pesanan
</x-mail::button>

Kalau link di atas hilang, buka **{{ $urlLacak }}** lalu masukkan kode pesanan **{{ $order->code }}** dan email ini.

Ada pertanyaan? Balas email ini atau hubungi kami lewat WhatsApp dengan menyebut kode pesanan **{{ $order->code }}**.

Green Deahan Sport
</x-mail::message>
