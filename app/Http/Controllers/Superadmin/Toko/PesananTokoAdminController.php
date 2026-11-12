<?php

namespace App\Http\Controllers\Superadmin\Toko;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\Toko\UbahStatusPesananRequest;
use App\Mail\PesananTokoDikirim;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pesanan toko untuk admin: daftar (default status paid = perlu dibeli ke
 * supplier), detail, alur status, dan catatan.
 */
class PesananTokoAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status', OrderStatus::Paid->value);
        $filter = $status === 'semua' ? null : OrderStatus::tryFrom((string) $status) ?? OrderStatus::Paid;

        $pesanan = Order::with('items')
            ->when($filter, fn ($q, OrderStatus $filter) => $q->where('status', $filter))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'kode' => $order->code,
                'pelanggan' => $order->customer_name,
                'kota' => $order->city,
                'jumlahBarang' => (int) $order->items->sum('qty'),
                'total' => $order->total,
                'untung' => $order->untung(),
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'tanggal' => $order->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'url' => route('superadmin.toko.pesanan.show', $order),
            ]);

        return Inertia::render('superadmin/Toko/Pesanan/Index', [
            'pesanan' => $pesanan,
            'filterStatus' => $filter?->value ?? 'semua',
            'pilihanStatus' => collect(OrderStatus::cases())->map(fn (OrderStatus $s) => [
                'nilai' => $s->value,
                'label' => $s->label(),
                'jumlah' => Order::where('status', $s)->count(),
            ])->all(),
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load('items.product');

        return Inertia::render('superadmin/Toko/Pesanan/Show', [
            'pesanan' => [
                'id' => $order->id,
                'kode' => $order->code,
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'pelanggan' => [
                    'nama' => $order->customer_name,
                    'hp' => $order->phone,
                    'email' => $order->email,
                    'alamat' => $order->address,
                    'kota' => $order->city,
                    'provinsi' => $order->province,
                    'kodePos' => $order->postal_code,
                    'alamatLengkap' => $order->alamatLengkap(),
                ],
                'items' => $order->items->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'nama' => $item->product_name,
                    'qty' => $item->qty,
                    'harga' => $item->unit_price,
                    'modal' => $item->unit_cost,
                    'linkSupplier' => $item->product?->supplier_product_url,
                ])->all(),
                'subtotal' => $order->subtotal,
                'ongkir' => $order->shipping_cost,
                'total' => $order->total,
                'totalModal' => $order->totalModal(),
                'untung' => $order->untung(),
                'dibuat' => $order->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'dibayar' => $order->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'refSupplier' => $order->supplier_order_ref,
                'kurir' => $order->courier,
                'resi' => $order->tracking_number,
                'catatan' => $order->admin_notes,
                'mayarId' => $order->mayar_payment_id,
                'langkahBerikutnya' => collect($order->status->langkahBerikutnya())->map(fn (OrderStatus $s) => ['nilai' => $s->value, 'label' => $s->label()])->all(),
                'statusUrl' => route('superadmin.toko.pesanan.status', $order),
                'catatanUrl' => route('superadmin.toko.pesanan.catatan', $order),
                'publikUrl' => route('toko.pesanan', $order->code),
            ],
            'kembaliUrl' => route('superadmin.toko.pesanan'),
        ]);
    }

    public function ubahStatus(UbahStatusPesananRequest $request, Order $order): RedirectResponse
    {
        $tujuan = OrderStatus::from($request->validated('status'));

        $berubah = DB::transaction(function () use ($request, $order, $tujuan) {
            $terkunci = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $terkunci->status->bisaMenjadi($tujuan)) {
                return false;
            }

            $terkunci->update(array_filter([
                'status' => $tujuan,
                'supplier_order_ref' => $request->validated('supplier_order_ref'),
                'courier' => $request->validated('courier'),
                'tracking_number' => $request->validated('tracking_number'),
            ], fn ($nilai) => $nilai !== null));

            return true;
        });

        if (! $berubah) {
            return back()->with('error', 'Status pesanan sudah berubah, muat ulang halaman.');
        }

        if ($tujuan === OrderStatus::Shipped) {
            Mail::to($order->email)->queue(new PesananTokoDikirim($order->fresh()));
        }

        return back()->with('success', "Status pesanan diubah menjadi \"{$tujuan->label()}\".".($tujuan === OrderStatus::Shipped ? ' Email resi dikirim ke pelanggan.' : ''));
    }

    public function simpanCatatan(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:2000']]);
        $order->update($data);

        return back()->with('success', 'Catatan disimpan.');
    }
}
