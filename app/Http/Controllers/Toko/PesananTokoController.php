<?php

namespace App\Http\Controllers\Toko;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Toko\LacakPesananRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\BypassBayarToko;
use App\Services\PesanPesananTokoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman status pesanan untuk pelanggan (/pesanan/{token}). Token 40
 * karakter acak per pesanan, bukan kode pesanan, jadi tidak bisa ditebak.
 */
class PesananTokoController extends Controller
{
    /**
     * Urutan normal pesanan untuk timeline pelanggan, dengan teks saat
     * langkah sedang berjalan ("sekarang") dan saat sudah lewat ("selesai").
     *
     * @var array<string, array{judul: string, sekarang: string, selesai: string}>
     */
    private const LANGKAH = [
        'dibuat' => ['judul' => 'Pesanan dibuat', 'sekarang' => 'Pesanan tercatat.', 'selesai' => 'Pesanan tercatat.'],
        'dibayar' => ['judul' => 'Pembayaran', 'sekarang' => 'Menunggu pembayaran. Halaman ini otomatis berubah begitu pembayaran diterima.', 'selesai' => 'Pembayaranmu sudah kami terima.'],
        'diproses' => ['judul' => 'Pesanan diproses', 'sekarang' => 'Barang sedang kami siapkan untuk dikirim.', 'selesai' => 'Barang sudah disiapkan dan diserahkan ke kurir.'],
        'dikirim' => ['judul' => 'Pesanan dikirim', 'sekarang' => 'Paket dalam perjalanan ke alamatmu. Lacak memakai nomor resi di bawah.', 'selesai' => 'Paket sudah dikirim.'],
        'selesai' => ['judul' => 'Pesanan selesai', 'sekarang' => 'Pesanan sudah sampai. Terima kasih sudah belanja di Green Deahan Sport!', 'selesai' => 'Pesanan sudah sampai. Terima kasih sudah belanja di Green Deahan Sport!'],
    ];

    /**
     * Langkah yang sedang berjalan per status (null: semua langkah selesai).
     *
     * @var array<string, string|null>
     */
    private const LANGKAH_SEKARANG = [
        'pending_payment' => 'dibayar',
        'paid' => 'diproses',
        'ordered_to_supplier' => 'diproses',
        'shipped' => 'dikirim',
        'completed' => null,
    ];

    /**
     * Form lacak pesanan untuk pelanggan yang kehilangan link status.
     */
    public function lacak(): Response
    {
        return Inertia::render('toko/LacakPesanan', [
            'meta' => [
                'title' => 'Lacak Pesanan | Toko Green Deahan Sport',
                'description' => 'Cek status pesanan toko Green Deahan Sport memakai kode pesanan dan email atau nomor HP.',
                'noindex' => ! config('toko.publik'),
            ],
        ]);
    }

    /**
     * Cocokkan kode dengan email atau nomor HP pesanan, lalu arahkan ke
     * halaman status. Pesan gagal sengaja sama untuk semua kasus supaya
     * tidak membocorkan kode mana yang ada.
     */
    public function cari(LacakPesananRequest $request): RedirectResponse
    {
        $order = Order::where('code', $request->kode())->first();
        $kontak = trim((string) $request->input('kontak'));

        $cocok = $order !== null && (str_contains($kontak, '@')
            ? strcasecmp($kontak, (string) $order->email) === 0
            : ($nomor = PesanPesananTokoService::nomorWhatsApp($kontak)) !== null && $nomor === PesanPesananTokoService::nomorWhatsApp((string) $order->phone));

        if (! $cocok) {
            return back()->withInput($request->only('kode'))->withErrors([
                'kode' => 'Pesanan tidak ditemukan. Periksa lagi kode pesanan dan email atau nomor HP yang dipakai saat checkout.',
            ]);
        }

        return redirect()->to(url($order->pathStatus()));
    }

    public function show(string $token, Request $request, BypassBayarToko $bypass): Response
    {
        $order = Order::with('items', 'shipments')->where('status_token', $token)->firstOrFail();

        return Inertia::render('toko/Pesanan', [
            'pesanan' => [
                'kode' => $order->code,
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'nama' => $order->customer_name,
                'alamat' => $order->alamatPengiriman(),
                'items' => $order->items->map(fn (OrderItem $item) => [
                    'nama' => $item->product_name,
                    'varian' => $item->variant_name,
                    'qty' => $item->qty,
                    'harga' => $item->unit_price,
                    'subtotal' => $item->unit_price * $item->qty,
                ])->all(),
                'subtotal' => $order->subtotal,
                'diskon' => $order->discount,
                'kodePromo' => $order->promo_code,
                'ongkir' => $order->shipping_cost,
                'layanan' => $order->layananPengiriman(),
                'total' => $order->total,
                'linkBayar' => $order->status === OrderStatus::PendingPayment ? $order->mayar_payment_link : null,
                'pengiriman' => $order->daftarPengiriman(),
                'alasanBatal' => $order->cancel_reason,
                'statusRefund' => $order->statusRefund(),
                'dibuat' => $order->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'dibayar' => $order->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
            ],
            // SEMENTARA (BypassBayarToko): url tombol gelembung mode uji, null kalau tidak boleh dipakai.
            'bypassBayarUrl' => $bypass->bolehDipakai($request->user()) && in_array($order->status, [OrderStatus::PendingPayment, OrderStatus::Expired], true)
                ? url($order->pathStatus().'/bypass-bayar')
                : null,
            'timeline' => $this->timeline($order),
            'meta' => ['title' => "Pesanan {$order->code} | Toko Green Deahan Sport", 'noindex' => true],
        ]);
    }

    /**
     * Langkah pesanan untuk timeline vertikal: "selesai" untuk yang sudah
     * lewat, "sekarang" untuk yang sedang berjalan, "nanti" untuk sisanya.
     * Pesanan batal atau kedaluwarsa: langkah yang sempat dicapai, lalu satu
     * langkah "batal".
     *
     * @return array<int, array{kunci: string, judul: string, keterangan: string, waktu: ?string, keadaan: string}>
     */
    private function timeline(Order $order): array
    {
        $format = fn ($tanggal) => $tanggal?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j M Y, H:i');
        $waktu = [
            'dibuat' => $format($order->created_at),
            'dibayar' => $format($order->paid_at),
            'diproses' => null,
            'dikirim' => $format($order->shipments->min('created_at')),
            'selesai' => null,
        ];
        $langkah = fn (string $kunci, string $keadaan) => [
            'kunci' => $kunci,
            'judul' => self::LANGKAH[$kunci]['judul'],
            'keterangan' => $keadaan === 'nanti' ? '' : self::LANGKAH[$kunci][$keadaan === 'sekarang' ? 'sekarang' : 'selesai'],
            'waktu' => $keadaan === 'selesai' ? $waktu[$kunci] : null,
            'keadaan' => $keadaan,
        ];

        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Expired], true)) {
            $tercapai = array_keys(array_filter([
                'dibuat' => true,
                'dibayar' => $order->paid_at !== null,
                'diproses' => $order->supplier_order_ref !== null,
                'dikirim' => $order->shipments->isNotEmpty(),
            ]));

            return [
                ...array_map(fn (string $kunci) => $langkah($kunci, 'selesai'), $tercapai),
                $order->status === OrderStatus::Expired
                    ? ['kunci' => 'kedaluwarsa', 'judul' => 'Pesanan kedaluwarsa', 'keterangan' => 'Pembayaran tidak diterima dalam batas waktu. Silakan pesan ulang dari toko.', 'waktu' => null, 'keadaan' => 'batal']
                    : ['kunci' => 'dibatalkan', 'judul' => 'Pesanan dibatalkan', 'keterangan' => $order->cancel_reason ? "Alasan: {$order->cancel_reason}" : 'Pesanan ini dibatalkan.', 'waktu' => $format($order->cancelled_at), 'keadaan' => 'batal'],
            ];
        }

        $kunci = array_keys(self::LANGKAH);
        $sekarang = self::LANGKAH_SEKARANG[$order->status->value] ?? null;
        $posisi = $sekarang === null ? count($kunci) : array_search($sekarang, $kunci, true);

        return array_map(
            fn (string $item, int $urutan) => $langkah($item, $urutan < $posisi ? 'selesai' : ($urutan === $posisi ? 'sekarang' : 'nanti')),
            $kunci,
            array_keys($kunci),
        );
    }

    /**
     * SEMENTARA (BypassBayarToko): tandai dibayar tanpa Mayar. Hanya superadmin
     * yang login dan hanya saat mode uji dinyalakan; selain itu 404.
     */
    public function bypassBayar(string $token, Request $request, BypassBayarToko $bypass): RedirectResponse
    {
        abort_unless($bypass->bolehDipakai($request->user()), 404);

        $order = Order::where('status_token', $token)->firstOrFail();

        return $bypass->tandaiDibayar($order)
            ? back()->with('success', 'Mode uji: pesanan ditandai dibayar.')
            : back()->with('error', 'Pesanan ini sudah tidak menunggu pembayaran.');
    }
}
