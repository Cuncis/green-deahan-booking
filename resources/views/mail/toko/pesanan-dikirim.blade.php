<x-mail::message>
# {{ $tambahan ? 'Ada paket baru untuk pesananmu' : 'Pesanan kamu sudah dikirim' }}

Halo {{ $order->customer_name }}, {{ $tambahan ? 'ada paket tambahan untuk' : 'pesanan' }} **{{ $order->code }}** {{ $tambahan ? 'yang sudah kami kirim.' : 'sudah kami kirim.' }}
@if (count($pengiriman) > 1)

Pesananmu dikirim dalam {{ count($pengiriman) }} paket dari lokasi berbeda, jadi paket bisa tiba di hari yang berbeda.
@endif

@foreach ($pengiriman as $indeks => $paket)
- Paket {{ $indeks + 1 }}: **{{ $paket['kurir'] }}**, nomor resi **{{ $paket['resi'] }}**@if ($paket['dikirimDari']) (dari {{ $paket['dikirimDari'] }})@endif

@endforeach

Kamu bisa melacak paket di situs kurir memakai nomor resi di atas.

<x-mail::button :url="$urlPesanan">
Lihat Status Pesanan
</x-mail::button>

Terima kasih sudah belanja di Green Deahan Sport.
</x-mail::message>
