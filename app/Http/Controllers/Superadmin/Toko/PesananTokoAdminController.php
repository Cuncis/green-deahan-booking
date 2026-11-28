<?php

namespace App\Http\Controllers\Superadmin\Toko;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\Toko\KirimPesanRequest;
use App\Http\Requests\Superadmin\Toko\TambahResiRequest;
use App\Http\Requests\Superadmin\Toko\UbahStatusPesananRequest;
use App\Mail\PesananTokoDibatalkan;
use App\Mail\PesananTokoDikirim;
use App\Mail\PesanPesananToko;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderMessage;
use App\Models\OrderShipment;
use App\Services\BypassBayarToko;
use App\Services\PesanPesananTokoService;
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
    private const FILTER_PERLU_REFUND = 'perlu_refund';

    public function index(Request $request, BypassBayarToko $bypass, PesanPesananTokoService $pesan): Response
    {
        $status = $request->query('status', OrderStatus::Paid->value);
        $perluRefund = $status === self::FILTER_PERLU_REFUND;
        $filter = $status === 'semua' || $perluRefund ? null : OrderStatus::tryFrom((string) $status) ?? OrderStatus::Paid;

        $pesanan = Order::with('items', 'shipments')
            ->when($perluRefund, fn ($q) => $q->menungguRefund())
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
                'perluRefund' => $order->perluRefund(),
                'tanggal' => $order->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'url' => route('superadmin.toko.pesanan.show', $order),
                'whatsapp' => $pesan->whatsAppStatus($order),
                'pesanUrl' => route('superadmin.toko.pesanan.pesan', $order),
                'emailUlangUrl' => route('superadmin.toko.pesanan.email-ulang', $order),
                'email' => $order->email,
            ]);

        return Inertia::render('superadmin/Toko/Pesanan/Index', [
            'pesanan' => $pesanan,
            'filterStatus' => $perluRefund ? self::FILTER_PERLU_REFUND : ($filter?->value ?? 'semua'),
            'pilihanStatus' => collect(OrderStatus::cases())->map(fn (OrderStatus $s) => [
                'nilai' => $s->value,
                'label' => $s->label(),
                'jumlah' => Order::where('status', $s)->count(),
            ])->push(['nilai' => self::FILTER_PERLU_REFUND, 'label' => 'Perlu refund', 'jumlah' => Order::menungguRefund()->count()])->all(),
            // SEMENTARA (BypassBayarToko): hapus setelah sandbox Mayar hidup.
            'bypassBayar' => ['aktif' => $bypass->aktif(), 'url' => route('superadmin.toko.bypass-bayar')],
        ]);
    }

    /**
     * SEMENTARA (BypassBayarToko): nyalakan/matikan mode uji bayar toko.
     */
    public function aturBypassBayar(Request $request, BypassBayarToko $bypass): RedirectResponse
    {
        $aktif = $request->validate(['aktif' => ['required', 'boolean']])['aktif'];
        $bypass->atur((bool) $aktif);

        return back()->with('success', $aktif ? 'Mode uji bayar aktif. Ingat matikan lagi setelah selesai menguji.' : 'Mode uji bayar dimatikan.');
    }

    public function show(Order $order, PesanPesananTokoService $pesan): Response
    {
        $order->load('items.product', 'shipments', 'messages.user');

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
                    'kelurahan' => $order->village,
                    'kecamatan' => $order->district,
                    'kota' => $order->city,
                    'provinsi' => $order->province,
                    'kodePos' => $order->postal_code,
                    'alamatLengkap' => $order->alamatLengkap(),
                ],
                'items' => $order->items->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'nama' => $item->product_name,
                    'varian' => $item->variant_name,
                    'qty' => $item->qty,
                    'harga' => $item->unit_price,
                    'modal' => $item->unit_cost,
                    'linkSupplier' => $item->product?->supplier_product_url,
                ])->all(),
                'subtotal' => $order->subtotal,
                'diskon' => $order->discount,
                'kodePromo' => $order->promo_code,
                'ongkir' => $order->shipping_cost,
                'layanan' => $order->layananPengiriman(),
                'ongkirDarurat' => $order->ongkirDarurat(),
                'total' => $order->total,
                'totalModal' => $order->totalModal(),
                'untung' => $order->untung(),
                'dibuat' => $order->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'dibayar' => $order->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'refSupplier' => $order->supplier_order_ref,
                'pengiriman' => collect($order->daftarPengiriman())->map(fn (array $paket) => [
                    ...$paket,
                    'hapusUrl' => route('superadmin.toko.pesanan.resi.hapus', [$order, $paket['id']]),
                ])->all(),
                'resiUrl' => route('superadmin.toko.pesanan.resi.tambah', $order),
                'catatan' => $order->admin_notes,
                'alasanBatal' => $order->cancel_reason,
                'statusRefund' => $order->statusRefund(),
                'refundUrl' => route('superadmin.toko.pesanan.refund', $order),
                'templatePesan' => $pesan->daftar($order),
                'waNomor' => PesanPesananTokoService::nomorWhatsApp($order->phone),
                'pesanUrl' => route('superadmin.toko.pesanan.pesan', $order),
                'riwayatPesan' => $order->messages->map(fn (OrderMessage $item) => [
                    'id' => $item->id,
                    'saluran' => $item->channel,
                    'template' => PesanPesananTokoService::TEMPLATE[$item->template] ?? $item->template,
                    'subjek' => $item->subject,
                    'isi' => $item->body,
                    'oleh' => $item->user?->name,
                    'waktu' => $item->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                ])->all(),
                'mayarId' => $order->mayar_payment_id,
                'langkahBerikutnya' => collect($order->status->langkahBerikutnya())->map(fn (OrderStatus $s) => ['nilai' => $s->value, 'label' => $s->label()])->all(),
                'statusUrl' => route('superadmin.toko.pesanan.status', $order),
                'catatanUrl' => route('superadmin.toko.pesanan.catatan', $order),
                'publikUrl' => $order->urlStatus(),
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
            ], fn ($nilai) => $nilai !== null));

            if ($tujuan === OrderStatus::Cancelled) {
                $terkunci->update(['cancel_reason' => $request->validated('cancel_reason'), 'cancelled_at' => now()]);
            }

            if ($tujuan === OrderStatus::Shipped) {
                $terkunci->shipments()->createMany(collect($request->validated('pengiriman'))->map(fn (array $paket) => [
                    'courier' => $paket['courier'],
                    'tracking_number' => $paket['tracking_number'],
                    'shipped_from' => $paket['shipped_from'] ?? null,
                ])->all());
            }

            return true;
        });

        if (! $berubah) {
            return back()->with('error', 'Status pesanan sudah berubah, muat ulang halaman.');
        }

        if ($tujuan === OrderStatus::Shipped) {
            Mail::to($order->email)->queue(new PesananTokoDikirim($order->fresh()));
        }

        if ($tujuan === OrderStatus::Cancelled && $order->paid_at !== null) {
            Mail::to($order->email)->queue(new PesananTokoDibatalkan($order->fresh()));

            return back()->with('success', 'Pesanan dibatalkan dan pelanggan diberi tahu lewat email. Refund masih perlu Anda proses manual, lalu tandai selesai.');
        }

        return back()->with('success', "Status pesanan diubah menjadi \"{$tujuan->label()}\".".($tujuan === OrderStatus::Shipped ? ' Email resi dikirim ke pelanggan.' : ''));
    }

    public function tambahResi(TambahResiRequest $request, Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::Shipped) {
            return back()->with('error', 'Paket tambahan hanya bisa dicatat untuk pesanan berstatus dikirim.');
        }

        $order->shipments()->create($request->validated());
        Mail::to($order->email)->queue(new PesananTokoDikirim($order->fresh(), tambahan: true));

        return back()->with('success', 'Paket ditambahkan. Email resi terbaru dikirim ke pelanggan.');
    }

    public function hapusResi(Order $order, OrderShipment $shipment): RedirectResponse
    {
        abort_unless($shipment->order_id === $order->id, 404);

        if ($order->status !== OrderStatus::Shipped) {
            return back()->with('error', 'Resi hanya bisa dihapus saat pesanan berstatus dikirim.');
        }

        if ($order->shipments()->count() <= 1) {
            return back()->with('error', 'Pesanan dikirim harus punya minimal satu resi. Tambah resi pengganti dulu.');
        }

        $shipment->delete();

        return back()->with('success', 'Resi dihapus.');
    }

    public function kirimPesan(KirimPesanRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();
        $email = $data['channel'] === 'email';

        $order->messages()->create([
            'user_id' => $request->user()->id,
            'channel' => $data['channel'],
            'template' => $data['template'],
            'subject' => $email ? $data['subject'] : null,
            'body' => $data['body'],
        ]);

        if ($email) {
            Mail::to($order->email)->queue(new PesanPesananToko($order, $data['subject'], $data['body']));
        }

        return back()->with('success', $email ? 'Email dikirim ke pelanggan.' : 'Pesan WhatsApp dicatat di riwayat.');
    }

    /**
     * Kirim ulang email status saat ini ke pelanggan (misal pelanggan bilang
     * emailnya tidak masuk). Dicatat di riwayat kabar.
     */
    public function kirimUlangEmail(Request $request, Order $order, PesanPesananTokoService $pesan): RedirectResponse
    {
        $email = $pesan->emailStatus($order);
        $template = $pesan->templateUntukStatus($order);

        Mail::to($order->email)->queue($email);

        $order->messages()->create([
            'user_id' => $request->user()->id,
            'channel' => 'email',
            'template' => $template,
            'subject' => $email->envelope()->subject,
            'body' => 'Kirim ulang email status ('.PesanPesananTokoService::TEMPLATE[$template].') ke '.$order->email.'.',
        ]);

        return back()->with('success', "Email status dikirim ulang ke {$order->email}.");
    }

    public function refundSelesai(Order $order): RedirectResponse
    {
        if (! $order->perluRefund()) {
            return back()->with('error', 'Pesanan ini tidak sedang menunggu refund.');
        }

        $order->update(['refunded_at' => now()]);

        return back()->with('success', 'Refund ditandai selesai.');
    }

    public function simpanCatatan(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:2000']]);
        $order->update($data);

        return back()->with('success', 'Catatan disimpan.');
    }
}
