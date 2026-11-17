<?php

namespace App\Http\Controllers\Superadmin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\Toko\ProdukRequest;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\FotoProdukService;
use App\Services\ImporProdukHtmlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Produk toko. Harga modal dan link supplier hanya tampil di sini.
 */
class ProdukTokoController extends Controller
{
    public function __construct(
        private readonly FotoProdukService $fotoProdukService,
        private readonly ImporProdukHtmlService $imporProdukHtmlService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('superadmin/Toko/Produk/Index', [
            'produk' => Product::with('supplier:id,name')->orderBy('category')->orderBy('name')->get()->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'kategori' => Product::KATEGORI[$product->category] ?? $product->category,
                'imageUrl' => $product->image_path,
                'hargaJual' => $product->sell_price,
                'satuan' => $product->unit,
                'badge' => $product->badge,
                'hargaModal' => $product->cost_price,
                'margin' => $product->sell_price - $product->cost_price,
                'supplier' => $product->supplier?->name,
                'linkSupplier' => $product->supplier_product_url,
                'aktif' => $product->is_active,
                'editUrl' => route('superadmin.toko.produk.edit', $product),
                'hapusUrl' => route('superadmin.toko.produk.destroy', $product),
                'publikUrl' => route('toko.produk', $product->slug),
            ])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('superadmin/Toko/Produk/Form', ['produk' => null, 'impor' => session('impor'), ...$this->dataForm()]);
    }

    /**
     * Baca HTML halaman produk Shopee yang ditempel, lalu kembali ke form
     * Tambah Produk dengan isian awal. Tidak ada produk yang dibuat di sini
     * dan HTML mentah tidak disimpan.
     */
    public function impor(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->is_superadmin, 403);

        $request->validate(
            ['html' => ['required', 'string', 'max:3000000']],
            ['html.required' => 'Tempel HTML halaman produk dulu.', 'html.max' => 'HTML terlalu besar. Tempel hanya bagian halaman produknya.'],
            ['html' => 'HTML produk'],
        );

        $hasil = $this->imporProdukHtmlService->baca($request->string('html')->toString());

        if (! $hasil['nama']) {
            return back()->withErrors(['html' => 'Nama produk tidak ditemukan. Pastikan yang ditempel adalah HTML halaman produk Shopee.']);
        }

        $supplier = $hasil['namaSupplier']
            ? Supplier::whereRaw('lower(name) = ?', [mb_strtolower($hasil['namaSupplier'])])->first(['id'])
            : null;

        return redirect()->route('superadmin.toko.produk.create')->with('impor', [
            ...$hasil,
            'supplierId' => $supplier?->id,
        ]);
    }

    public function store(ProdukRequest $request): RedirectResponse
    {
        $data = $this->siapkan($request);

        try {
            $alamat = $this->fotoProdukService->simpanBaru($request->fotoBaru(), $request->gambarUrl());
        } catch (RuntimeException $galat) {
            return back()->withInput()->withErrors(['foto' => $galat->getMessage()]);
        }

        $product = Product::create($data);
        $this->fotoProdukService->terapkan($product, [], $alamat);

        return redirect()->route('superadmin.toko.produk')->with('success', "Produk \"{$product->name}\" ditambahkan.");
    }

    public function edit(Product $product): Response
    {
        $product->load('images');

        return Inertia::render('superadmin/Toko/Produk/Form', [
            'produk' => [
                ...$product->only(['name', 'slug', 'category', 'unit', 'badge', 'description', 'sell_price', 'compare_price', 'cost_price', 'supplier_id', 'supplier_product_url', 'weight_gram', 'is_active']),
                'imageUrl' => $product->image_path,
                'fotos' => $product->images->map(fn ($foto) => ['id' => $foto->id, 'url' => $foto->path])->all(),
                'updateUrl' => route('superadmin.toko.produk.update', $product),
            ],
            ...$this->dataForm(),
        ]);
    }

    public function update(ProdukRequest $request, Product $product): RedirectResponse
    {
        $data = $this->siapkan($request, $product);

        try {
            $alamat = $this->fotoProdukService->simpanBaru($request->fotoBaru(), $request->gambarUrl());
        } catch (RuntimeException $galat) {
            return back()->withInput()->withErrors(['foto' => $galat->getMessage()]);
        }

        $product->update($data);
        $this->fotoProdukService->terapkan($product, $request->boolean('foto_diatur') ? $request->input('foto_ada', []) : null, $alamat);

        return redirect()->route('superadmin.toko.produk')->with('success', "Produk \"{$product->name}\" diperbarui.");
    }

    /**
     * Pesanan lama tetap utuh: nama, harga, dan modal sudah disalin ke order_items.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->fotoProdukService->hapusSemua($product);
        $nama = $product->name;
        $product->delete();

        return back()->with('success', "Produk \"{$nama}\" dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function siapkan(ProdukRequest $request, ?Product $product = null): array
    {
        $data = collect($request->validated())->except(['foto', 'foto_baru', 'foto_diatur', 'foto_ada', 'gambar_url', 'gambar_urls'])->all();
        $data['slug'] = $this->slugUnik(($data['slug'] ?? null) ?: $data['name'], $product);

        return $data;
    }

    private function slugUnik(string $dasar, ?Product $product): string
    {
        $dasar = Str::slug($dasar) ?: 'produk';
        $slug = $dasar;
        $urutan = 2;

        while (Product::where('slug', $slug)->when($product, fn ($q) => $q->whereKeyNot($product->id))->exists()) {
            $slug = "{$dasar}-{$urutan}";
            $urutan++;
        }

        return $slug;
    }

    /**
     * @return array{pilihanKategori: array<int, array{nilai: string, label: string}>, pilihanSatuan: array<int, string>, pilihanSupplier: array<int, array{id: int, name: string}>, simpanUrl: string, imporUrl: string, kembaliUrl: string, maksFoto: int}
     */
    private function dataForm(): array
    {
        return [
            'pilihanKategori' => collect(Product::KATEGORI)->map(fn (string $label, string $nilai) => ['nilai' => $nilai, 'label' => $label])->values()->all(),
            'pilihanSatuan' => Product::SATUAN,
            'pilihanSupplier' => Supplier::orderBy('name')->get(['id', 'name'])->map(fn (Supplier $supplier) => ['id' => $supplier->id, 'name' => $supplier->name])->all(),
            'simpanUrl' => route('superadmin.toko.produk.store'),
            'imporUrl' => route('superadmin.toko.produk.impor'),
            'kembaliUrl' => route('superadmin.toko.produk'),
            'maksFoto' => FotoProdukService::MAKS_FOTO,
        ];
    }
}
