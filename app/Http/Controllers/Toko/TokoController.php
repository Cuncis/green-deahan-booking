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
    /**
     * @var array<string, string>
     */
    private const URUTAN = [
        'bawaan' => 'Urutan Bawaan',
        'terbaru' => 'Terbaru',
        'murah' => 'Harga Terendah',
        'mahal' => 'Harga Tertinggi',
    ];

    private const PER_HALAMAN = 9;

    /**
     * Daftar produk dengan cari, filter kategori, urutan, dan halaman. Semua
     * lewat query string (?q=&kategori=&urut=&page=) supaya bisa dibagikan.
     */
    public function index(Request $request): Response
    {
        $kategori = array_key_exists((string) $request->query('kategori'), Product::KATEGORI) ? (string) $request->query('kategori') : null;
        $urut = array_key_exists((string) $request->query('urut'), self::URUTAN) ? (string) $request->query('urut') : 'bawaan';
        $cari = mb_substr(trim((string) $request->query('q')), 0, 100);

        $jumlahPerKategori = Product::aktif()->selectRaw('category, count(*) as jumlah')->groupBy('category')->pluck('jumlah', 'category');

        $produk = Product::aktif()
            ->when($kategori, fn ($q, $kategori) => $q->where('category', $kategori))
            ->when($cari !== '', fn ($q) => $q->where('name', 'like', '%'.$cari.'%'))
            ->when($urut === 'murah', fn ($q) => $q->orderBy('sell_price'))
            ->when($urut === 'mahal', fn ($q) => $q->orderByDesc('sell_price'))
            ->when($urut === 'terbaru', fn ($q) => $q->latest('id'))
            ->when($urut === 'bawaan', fn ($q) => $q->orderBy('category')->orderBy('name'))
            ->paginate(self::PER_HALAMAN)
            ->withQueryString()
            ->through(fn (Product $product) => $product->dataPublik());

        return Inertia::render('toko/Index', [
            'produk' => $produk,
            'kategori' => collect(Product::KATEGORI)
                ->map(fn (string $label, string $nilai) => ['nilai' => $nilai, 'label' => $label, 'jumlah' => (int) ($jumlahPerKategori[$nilai] ?? 0)])
                ->values()
                ->all(),
            'totalProduk' => (int) $jumlahPerKategori->sum(),
            'filter' => ['kategori' => $kategori, 'q' => $cari !== '' ? $cari : null, 'urut' => $urut],
            'pilihanUrutan' => collect(self::URUTAN)->map(fn (string $label, string $nilai) => ['nilai' => $nilai, 'label' => $label])->values()->all(),
            'meta' => [
                'title' => 'Toko Perlengkapan Lapangan & Olahraga | Green Deahan Sport',
                'description' => 'Rumput sintetis, lantai interlock, perlengkapan lapangan, lampu, material perawatan, dan perlengkapan olahraga dari Green Deahan Sport. Bayar online, dikirim ke seluruh Indonesia.',
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
                'kategoriUrl' => '/toko?kategori='.$product->category,
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
