<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\KeranjangService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Keranjang di session. Request hanya membawa product_id, variant_id, dan qty; harga
 * tidak pernah diterima dari browser.
 */
class KeranjangController extends Controller
{
    public function __construct(private readonly KeranjangService $keranjang) {}

    public function index(): Response
    {
        return Inertia::render('toko/Keranjang', [
            'keranjang' => $this->keranjang->ringkasan(),
            'maksQty' => (int) config('toko.maks_qty_per_produk'),
            'meta' => ['title' => 'Keranjang | Toko Green Deahan Sport', 'noindex' => true],
        ]);
    }

    /**
     * Ringkasan isi keranjang untuk popup di ikon keranjang header. Hanya
     * isi session pengunjung itu sendiri, dan hanya kolom yang tampil di
     * popup (tanpa harga modal atau link supplier).
     */
    public function ringkas(): JsonResponse
    {
        $ringkasan = $this->keranjang->ringkasan();

        return response()->json([
            'items' => array_map(fn (array $item) => [
                'id' => $item['id'],
                'kunci' => $item['kunci'],
                'name' => $item['name'],
                'varian' => $item['varian'],
                'slug' => $item['slug'],
                'imageUrl' => $item['imageUrl'],
                'unit' => $item['unit'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'subtotal' => $item['subtotal'],
            ], $ringkasan['items']),
            'jumlahItem' => $ringkasan['jumlahItem'],
            'subtotal' => $ringkasan['subtotal'],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'variant_id' => ['nullable', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:'.config('toko.maks_qty_per_produk')],
        ]);

        $product = Product::aktif()->with('variants')->findOrFail($data['product_id']);
        $varian = null;

        if ($product->variants->isNotEmpty()) {
            $varian = $product->variants->firstWhere('id', $data['variant_id'] ?? null);

            if ($varian === null) {
                return back()->with('error', 'Pilih '.mb_strtolower($product->variant_label ?: 'varian').' dulu.');
            }

            if (! $varian->is_available) {
                return back()->with('error', "{$varian->name} sedang habis, pilih yang lain.");
            }
        } elseif (($data['variant_id'] ?? null) !== null) {
            return back()->with('error', 'Pilihan varian tidak berlaku untuk produk ini.');
        }

        $this->keranjang->tambah($product, $data['qty'] ?? 1, $varian);

        return back()->with('success', ($varian ? "{$product->name} ({$varian->name})" : $product->name).' masuk keranjang.');
    }

    public function update(Request $request, string $kunci): RedirectResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:'.config('toko.maks_qty_per_produk')],
        ]);

        $this->keranjang->ubah($kunci, $data['qty']);

        return back();
    }

    public function destroy(string $kunci): RedirectResponse
    {
        $this->keranjang->hapus($kunci);

        return back();
    }
}
