<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\KeranjangService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Keranjang di session. Request hanya membawa product_id dan qty; harga
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:'.config('toko.maks_qty_per_produk')],
        ]);

        $product = Product::aktif()->findOrFail($data['product_id']);
        $this->keranjang->tambah($product, $data['qty'] ?? 1);

        return back()->with('success', "{$product->name} masuk keranjang.");
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:'.config('toko.maks_qty_per_produk')],
        ]);

        $this->keranjang->ubah($product, $data['qty']);

        return back();
    }

    public function destroy(int $product): RedirectResponse
    {
        $this->keranjang->hapus($product);

        return back();
    }
}
