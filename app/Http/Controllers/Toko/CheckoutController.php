<?php

namespace App\Http\Controllers\Toko;

use App\Enums\OrderStatus;
use App\Exceptions\MayarException;
use App\Exceptions\OngkirGagalException;
use App\Exceptions\PromoTokoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\CheckoutRequest;
use App\Models\Order;
use App\Models\Wilayah;
use App\Services\BypassBayarToko;
use App\Services\KeranjangService;
use App\Services\MayarService;
use App\Services\OngkirTokoService;
use App\Services\PromoTokoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        private readonly PromoTokoService $promo,
        private readonly BypassBayarToko $bypass,
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
            // SEMENTARA (BypassBayarToko): hapus setelah sandbox Mayar hidup.
            'bypassBayar' => $this->bypass->bolehDipakai(request()->user()),
            // Dropdown alamat butuh data dari `php artisan wilayah:impor`; tanpa itu form menampilkan pemberitahuan.
            'wilayahTersedia' => Wilayah::query()->exists(),
            'wilayahUrl' => url('/wilayah'),
            'ongkirUrl' => url('/checkout/ongkir'),
            'promoUrl' => url('/checkout/promo'),
            'meta' => ['title' => 'Checkout | Toko Green Deahan Sport', 'noindex' => true],
        ]);
    }

    /**
     * Layanan kurir dan ongkirnya untuk isi keranjang ke kelurahan tujuan,
     * dari RajaOngkir atau Biteship. Hanya untuk ditampilkan: saat bayar, ongkir dihitung
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
        } catch (OngkirGagalException $galat) {
            report($galat);

            // Alasan sebenarnya (pesan galat penyedia ongkir tidak pernah memuat API key) hanya tampil saat APP_DEBUG menyala.
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

    /**
     * Pratinjau potongan kode promo untuk isi keranjang. Hanya untuk ditampilkan:
     * saat bayar, kode diperiksa dan potongannya dihitung ulang di server.
     */
    public function promo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), ['kode' => ['required', 'string', 'max:40']], [
            'kode.required' => 'Isi kode promo dulu.',
            'kode.max' => 'Kode promo terlalu panjang.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first('kode')], 422);
        }

        $isi = $this->keranjang->isi();

        if ($isi->isEmpty()) {
            return response()->json(['message' => 'Keranjang kosong.'], 422);
        }

        $subtotal = (int) $isi->sum(fn (array $baris) => $baris['harga'] * $baris['qty']);

        try {
            ['promo' => $promo, 'diskon' => $diskon] = $this->promo->periksa((string) $request->query('kode'), $subtotal);
        } catch (PromoTokoException $galat) {
            return response()->json(['message' => $galat->getMessage()], 422);
        }

        return response()->json(['kode' => $promo->code, 'diskon' => $diskon, 'ringkasan' => $promo->ringkasan()])->header('Cache-Control', 'private, no-store');
    }

    public function store(CheckoutRequest $request): SymfonyResponse
    {
        $isi = $this->keranjang->isi();

        if ($isi->isEmpty()) {
            return redirect()->to(url('/keranjang'))->with('error', 'Keranjang kosong atau produknya sudah tidak tersedia.');
        }

        // Harga, modal, dan total dihitung ulang dari database di sini. Data alamat dicari dulu
        // di luar transaksi supaya kunci baris promo tidak ditahan selama query wilayah.
        $data = $request->dataPesanan();

        $order = DB::transaction(function () use ($request, $isi, $data) {
            $subtotal = (int) $isi->sum(fn (array $baris) => $baris['harga'] * $baris['qty']);
            // Ongkir dari layanan kurir yang dihitung ulang di server; ongkir flat kalau belum ada penyedia ongkir aktif.
            $ongkir = (int) ($data['shipping_cost'] ?? config('toko.ongkir_flat'));

            $kodePromo = (string) $request->input('promo_code', '');
            $diskon = 0;

            if ($kodePromo !== '') {
                try {
                    ['promo' => $promo, 'diskon' => $diskon] = $this->promo->periksaDenganKunci($kodePromo, $subtotal);
                    $kodePromo = $promo->code;
                } catch (PromoTokoException $galat) {
                    throw ValidationException::withMessages(['promo_code' => $galat->getMessage()]);
                }
            }

            $order = Order::create([
                ...$data,
                'code' => Order::buatKode(),
                'subtotal' => $subtotal,
                'shipping_cost' => $ongkir,
                'promo_code' => $kodePromo !== '' ? $kodePromo : null,
                'discount' => $diskon,
                'total' => $subtotal - $diskon + $ongkir,
                'status' => OrderStatus::PendingPayment,
            ]);

            foreach ($isi as $baris) {
                $order->items()->create([
                    'product_id' => $baris['product']->id,
                    'variant_id' => $baris['variant']?->id,
                    'product_name' => $baris['product']->name,
                    'variant_name' => $baris['variant']?->name,
                    'unit_price' => $baris['harga'],
                    'unit_cost' => $baris['modal'],
                    'qty' => $baris['qty'],
                ]);
            }

            return $order;
        });

        // SEMENTARA (BypassBayarToko): mode uji untuk superadmin, Mayar dilewati. Hapus setelah sandbox Mayar hidup.
        if ($this->bypass->bolehDipakai($request->user())) {
            $this->keranjang->kosongkan();

            return redirect()->to(url($order->pathStatus()))->with('success', 'Mode uji: pesanan dibuat tanpa Mayar. Klik gelembung di kanan untuk menandai dibayar.');
        }

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
