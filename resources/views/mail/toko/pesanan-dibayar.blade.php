<x-mail::message>
# Pesanan baru sudah dibayar

Pesanan **{{ $order->code }}** sudah dibayar lewat Mayar. Silakan beli barangnya ke supplier lalu kirim ke alamat pelanggan.

<x-mail::table>
| Barang | Jumlah | Harga |
|:--|:--:|--:|
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->qty }} | Rp{{ number_format($item->unit_price * $item->qty, 0, ',', '.') }} |
@endforeach
| **Total dibayar** | | **Rp{{ number_format($order->total, 0, ',', '.') }}** |
</x-mail::table>

**Kirim ke:**
{{ $order->customer_name }}, {{ $order->phone }}
{{ $order->address }}, {{ $order->city }}, {{ $order->province }} {{ $order->postal_code }}

<x-mail::button :url="$urlAdmin">
Buka Pesanan di Admin
</x-mail::button>

Green Deahan Sport
</x-mail::message>
