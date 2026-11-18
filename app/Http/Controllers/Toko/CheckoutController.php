<?php

namespace App\Http\Controllers\Toko;

use App\Enums\OrderStatus;
use App\Exceptions\BiteshipException;
use App\Exceptions\MayarException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\CheckoutRequest;
use App\Models\Order;
use App\Models\Wilayah;
use App\Services\KeranjangService;
use App\Services\MayarService;
use App\Services\OngkirTokoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        private readonly OngkirTokoService $ongkir,
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
            // Dropdown alamat butuh data dari `php artisan wilayah:impor`; tanpa itu form menampilkan pemberitahuan.
            'wilayahTersedia' => Wilayah::query()->exists(),
            'wilayahUrl' => url('/wilayah'),
            'ongkirUrl' => url('/checkout/ongkir'),
            'meta' => ['title' => 'Checkout | Toko Green Deahan Sport', 'noindex' => true],
        ]);
    }

    /**
     * Layanan kurir dan ongkirnya untuk isi keranjang ke kelurahan tujuan,
     * dari Biteship. Hanya untuk ditampilkan: saat bayar, ongkir dihitung
     * ulang dari layanan yang dipilih.
     */
    public function ongkir(Request $request): JsonResponse
    {
        // Validator manual: pengecualian di aplikasi ini hanya dirender JSON untuk /api/*, jadi
        // $request->validate() akan mengalihkan (302) alih-alih memberi 422.
        $validator = Validator::make($request->query(), [
            'wilayah_kode' => ['required', 'string', Rule::exists('wilayah', 'kode')->where('level', Wilayah::LEVEL_KELURAHAN)],
            'postal_code' => ['nullable', 'digits:5'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Pilih kelurahan tujuan dulu.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        if (! $this->ongkir->aktif()) {
            return response()->json(['aktif' => false, 'opsi' => []]);
        }

        $kodePos = Wilayah::find($data['wilayah_kode'])?->kodepos ?? ($data['postal_code'] ?? null);

        if (! is_string($kodePos) || ! preg_match('/^\d{5}$/', $kodePos)) {
            return response()->json(['message' => 'Isi kode pos 5 angka dulu.'], 422);
        }

        try {
            $opsi = $this->ongkir->opsi($kodePos);
        } catch (BiteshipException $galat) {
            report($galat);

            // Alasan sebenarnya (pesan Biteship tidak pernah memuat API key) hanya tampil saat APP_DEBUG menyala.
            $detail = config('app.debug') ? $galat->getMessage() : null;

            if ($darurat = $this->ongkir->opsiDarurat()) {
                return response()->json(array_filter([
                    'aktif' => true,
                    'darurat' => true,
                    'opsi' => [$darurat],
                    'message' => 'Ongkir kurir belum bisa dihitung. Kamu bisa memakai ongkir tetap atau coba hitung ulang.',
                    'detail' => $detail,
                ]))->header('Cache-Control', 'private, no-store');
            }

            return response()->json(array_filter([
                'message' => 'Ongkir belum bisa dihitung, coba lagi sebentar lagi.',
                'detail' => $detail,
            ]), 502);
        }

        return response()->json(['aktif' => true, 'opsi' => $opsi])->header('Cache-Control', 'private, no-store');
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
            $data = $request->dataPesanan();
            // Ongkir dari layanan kurir yang dihitung ulang di server; ongkir flat kalau Biteship belum aktif.
            $ongkir = (int) ($data['shipping_cost'] ?? config('toko.ongkir_flat'));

            $order = Order::create([
                ...$data,
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
