<?php

namespace App\Http\Controllers\Superadmin\Toko;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\Toko\ProdukRequest;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\FotoUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Produk toko. Harga modal dan link supplier hanya tampil di sini.
 */
class ProdukTokoController extends Controller
{
    public function __construct(private readonly FotoUploadService $fotoUploadService) {}

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
        return Inertia::render('superadmin/Toko/Produk/Form', ['produk' => null, ...$this->dataForm()]);
    }

    public function store(ProdukRequest $request): RedirectResponse
    {
        $data = $this->siapkan($request);

        if ($request->hasFile('foto')) {
            $data['image_path'] = $this->fotoUploadService->simpan($request->file('foto'), 'produk');
        }

        $product = Product::create($data);

        return redirect()->route('superadmin.toko.produk')->with('success', "Produk \"{$product->name}\" ditambahkan.");
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('superadmin/Toko/Produk/Form', [
            'produk' => [
                ...$product->only(['name', 'slug', 'category', 'unit', 'badge', 'description', 'sell_price', 'compare_price', 'cost_price', 'supplier_id', 'supplier_product_url', 'weight_gram', 'is_active']),
                'imageUrl' => $product->image_path,
                'updateUrl' => route('superadmin.toko.produk.update', $product),
            ],
            ...$this->dataForm(),
        ]);
    }

    public function update(ProdukRequest $request, Product $product): RedirectResponse
    {
        $data = $this->siapkan($request, $product);

        if ($request->hasFile('foto')) {
            $this->fotoUploadService->hapus($product->image_path);
            $data['image_path'] = $this->fotoUploadService->simpan($request->file('foto'), 'produk');
        }

        $product->update($data);

        return redirect()->route('superadmin.toko.produk')->with('success', "Produk \"{$product->name}\" diperbarui.");
    }

    /**
     * Pesanan lama tetap utuh: nama, harga, dan modal sudah disalin ke order_items.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->fotoUploadService->hapus($product->image_path);
        $nama = $product->name;
        $product->delete();

        return back()->with('success', "Produk \"{$nama}\" dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function siapkan(ProdukRequest $request, ?Product $product = null): array
    {
        $data = collect($request->validated())->except('foto')->all();
        $data['slug'] = $this->slugUnik($data['slug'] ?: $data['name'], $product);

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
     * @return array{pilihanKategori: array<int, array{nilai: string, label: string}>, pilihanSatuan: array<int, string>, pilihanSupplier: array<int, array{id: int, name: string}>, simpanUrl: string, kembaliUrl: string}
     */
    private function dataForm(): array
    {
        return [
            'pilihanKategori' => collect(Product::KATEGORI)->map(fn (string $label, string $nilai) => ['nilai' => $nilai, 'label' => $label])->values()->all(),
            'pilihanSatuan' => Product::SATUAN,
            'pilihanSupplier' => Supplier::orderBy('name')->get(['id', 'name'])->map(fn (Supplier $supplier) => ['id' => $supplier->id, 'name' => $supplier->name])->all(),
            'simpanUrl' => route('superadmin.toko.produk.store'),
            'kembaliUrl' => route('superadmin.toko.produk'),
        ];
    }
}
