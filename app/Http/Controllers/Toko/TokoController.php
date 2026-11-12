<?php

namespace App\Http\Controllers\Toko;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman toko pelanggan (/toko dan /toko/{slug}). Hanya mengirim
 * Product::dataPublik(), jadi harga modal dan link supplier tidak pernah
 * sampai ke browser.
 */
class TokoController extends Controller
{
    public function index(Request $request): Response
    {
        $kategori = array_key_exists((string) $request->query('kategori'), Product::KATEGORI) ? (string) $request->query('kategori') : null;

        return Inertia::render('toko/Index', [
            'produk' => Product::aktif()
                ->when($kategori, fn ($q, $kategori) => $q->where('category', $kategori))
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => $product->dataPublik())
                ->all(),
            'kategori' => collect(Product::KATEGORI)->map(fn (string $label, string $nilai) => ['nilai' => $nilai, 'label' => $label])->values()->all(),
            'kategoriAktif' => $kategori,
            'meta' => [
                'title' => 'Toko Perlengkapan Olahraga | Green Deahan Sport',
                'description' => 'Belanja bola padel, shuttlecock, bola futsal, grip, dan perlengkapan tim. Bayar online, dikirim ke seluruh Indonesia.',
                'noindex' => ! config('toko.publik'),
            ],
        ]);
    }

    public function show(string $slug): Response
    {
        $product = Product::aktif()->where('slug', $slug)->firstOrFail();

        return Inertia::render('toko/Produk', [
            'produk' => [
                ...$product->dataPublik(),
                'weightGram' => $product->weight_gram,
            ],
            'lainnya' => Product::aktif()
                ->where('category', $product->category)
                ->whereKeyNot($product->id)
                ->inRandomOrder()
                ->limit(3)
                ->get()
                ->map(fn (Product $lain) => $lain->dataPublik())
                ->all(),
            'maksQty' => (int) config('toko.maks_qty_per_produk'),
            'meta' => [
                'title' => "{$product->name} | Toko Green Deahan Sport",
                'description' => (string) str($product->description ?: $product->name)->limit(155),
                'image' => $product->image_path,
                'noindex' => ! config('toko.publik'),
            ],
        ]);
    }
}
