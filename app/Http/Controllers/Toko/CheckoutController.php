<?php

namespace App\Http\Controllers\Toko;

use App\Enums\OrderStatus;
use App\Exceptions\MayarException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\CheckoutRequest;
use App\Models\Order;
use App\Services\KeranjangService;
use App\Services\MayarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Checkout toko: buat pesanan (pending_payment) dari keranjang, lalu buat
 * payment request Mayar dan arahkan pelanggan ke halaman bayar Mayar.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly KeranjangService $keranjang,
        private readonly MayarService $mayar,
    ) {}

    public function create(): Response|RedirectResponse
    {
        $ringkasan = $this->keranjang->ringkasan();

        if ($ringkasan['items'] === []) {
            return redirect()->to(url('/keranjang'));
        }

        return Inertia::render('toko/Checkout', [
            'keranjang' => $ringkasan,
            'sandbox' => $this->mayar->modeSandbox(),
            'meta' => ['title' => 'Checkout | Toko Green Deahan Sport', 'noindex' => true],
        ]);
    }

    public function store(CheckoutRequest $request): SymfonyResponse
    {
        $isi = $this->keranjang->isi();

        if ($isi->isEmpty()) {
            return redirect()->to(url('/keranjang'))->with('error', 'Keranjang kosong atau produknya sudah tidak tersedia.');
        }

        // Harga, modal, dan total dihitung ulang dari database di sini.
        $order = DB::transaction(function () use ($request, $isi) {
            $subtotal = (int) $isi->sum(fn (array $baris) => $baris['product']->sell_price * $baris['qty']);
            $ongkir = (int) config('toko.ongkir_flat');

            $order = Order::create([
                ...$request->validated(),
                'code' => Order::buatKode(),
                'subtotal' => $subtotal,
                'shipping_cost' => $ongkir,
                'total' => $subtotal + $ongkir,
                'status' => OrderStatus::PendingPayment,
            ]);

            foreach ($isi as $baris) {
                $order->items()->create([
                    'product_id' => $baris['product']->id,
                    'product_name' => $baris['product']->name,
                    'unit_price' => $baris['product']->sell_price,
                    'unit_cost' => $baris['product']->cost_price,
                    'qty' => $baris['qty'],
                ]);
            }

            return $order;
        });

        // Panggilan ke Mayar di LUAR transaksi supaya database tidak terkunci selama menunggu jaringan.
        try {
            $pembayaran = $this->mayar->createPayment($order);
        } catch (MayarException $galat) {
            report($galat);
            $order->update([
                'status' => OrderStatus::Cancelled,
                'admin_notes' => 'Gagal membuat pembayaran Mayar: '.$galat->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Halaman pembayaran gagal dibuat. Keranjang kamu masih tersimpan, silakan coba lagi sebentar lagi.');
        }

        $order->update([
            'mayar_payment_id' => $pembayaran['id'],
            'mayar_payment_link' => $pembayaran['link'],
        ]);

        $this->keranjang->kosongkan();

        // Halaman Mayar ada di domain lain, jadi pakai redirect penuh (bukan kunjungan Inertia).
        return Inertia::location($pembayaran['link']);
    }
}
