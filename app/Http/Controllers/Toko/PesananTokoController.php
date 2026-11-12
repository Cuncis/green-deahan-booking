<?php

namespace App\Http\Controllers\Toko;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman status pesanan untuk pelanggan (/pesanan/{code}). Kode berisi 6
 * karakter acak, jadi halaman ini tidak bisa ditebak dari nomor urut.
 */
class PesananTokoController extends Controller
{
    /**
     * @var array<int, OrderStatus>
     */
    private const LANGKAH = [OrderStatus::PendingPayment, OrderStatus::Paid, OrderStatus::OrderedToSupplier, OrderStatus::Shipped, OrderStatus::Completed];

    public function show(string $code): Response
    {
        $order = Order::with('items')->where('code', $code)->firstOrFail();

        return Inertia::render('toko/Pesanan', [
            'pesanan' => [
                'kode' => $order->code,
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'nama' => $order->customer_name,
                'alamat' => "{$order->address}, {$order->city}, {$order->province} {$order->postal_code}",
                'items' => $order->items->map(fn (OrderItem $item) => [
                    'nama' => $item->product_name,
                    'qty' => $item->qty,
                    'harga' => $item->unit_price,
                    'subtotal' => $item->unit_price * $item->qty,
                ])->all(),
                'subtotal' => $order->subtotal,
                'ongkir' => $order->shipping_cost,
                'total' => $order->total,
                'linkBayar' => $order->status === OrderStatus::PendingPayment ? $order->mayar_payment_link : null,
                'kurir' => $order->courier,
                'resi' => $order->tracking_number,
                'dibuat' => $order->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'dibayar' => $order->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
            ],
            'langkah' => collect(self::LANGKAH)->map(fn (OrderStatus $status) => ['nilai' => $status->value, 'label' => $status->label()])->all(),
            'meta' => ['title' => "Pesanan {$order->code} | Toko Green Deahan Sport"],
        ]);
    }
}
