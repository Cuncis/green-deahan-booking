<?php

namespace App\Http\Requests\Superadmin\Toko;

use App\Models\Product;
use App\Services\FotoProdukService;
use App\Services\ImporProdukHtmlService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_superadmin;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $produk = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'alpha_dash', Rule::unique('products', 'slug')->ignore($produk?->id)],
            'category' => ['required', Rule::in(array_keys(Product::KATEGORI))],
            'unit' => ['required', Rule::in(Product::SATUAN)],
            'badge' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:3000'],
            'sell_price' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'compare_price' => ['nullable', 'integer', 'gt:sell_price', 'max:100000000'],
            'cost_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_product_url' => ['nullable', 'url', 'max:500'],
            'weight_gram' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['required', 'boolean'],
            'foto' => ['nullable', 'image', 'max:4096'],
            'foto_baru' => ['nullable', 'array', 'max:'.FotoProdukService::MAKS_FOTO],
            'foto_baru.*' => ['image', 'max:4096'],
            'foto_diatur' => ['nullable', 'boolean'],
            'foto_ada' => ['nullable', 'array', 'max:'.FotoProdukService::MAKS_FOTO],
            'foto_ada.*' => ['integer'],
            'gambar_url' => ['nullable', 'string', 'max:500', $this->aturanAlamatGambar()],
            'gambar_urls' => ['nullable', 'array', 'max:'.FotoProdukService::MAKS_FOTO],
            'gambar_urls.*' => ['string', 'max:500', $this->aturanAlamatGambar()],
        ];
    }

    /**
     * Jumlah foto (lama yang dipertahankan, unggahan, dan impor) tidak boleh
     * melebihi batas per produk.
     *
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $jumlah = count($this->input('foto_ada', [])) + count($this->fotoBaru()) + count($this->gambarUrl());

                if ($jumlah > FotoProdukService::MAKS_FOTO) {
                    $validator->errors()->add('foto', 'Foto produk maksimal '.FotoProdukService::MAKS_FOTO.'.');
                }
            },
        ];
    }

    /**
     * Foto yang diunggah, termasuk field lama "foto".
     *
     * @return array<int, UploadedFile>
     */
    public function fotoBaru(): array
    {
        return array_values(array_filter([...(array) $this->file('foto_baru'), $this->file('foto')]));
    }

    /**
     * Alamat foto impor Shopee, termasuk field lama "gambar_url".
     *
     * @return array<int, string>
     */
    public function gambarUrl(): array
    {
        return array_values(array_unique(array_filter([...(array) $this->input('gambar_urls', []), $this->input('gambar_url')], 'is_string')));
    }

    /**
     * @return \Closure(string, mixed, \Closure): mixed
     */
    private function aturanAlamatGambar(): \Closure
    {
        return fn (string $atribut, mixed $nilai, \Closure $gagal) => ImporProdukHtmlService::gambarDiizinkan(is_string($nilai) ? $nilai : null) ?: $gagal('Alamat foto impor tidak diizinkan.');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama produk',
            'sell_price' => 'harga jual',
            'compare_price' => 'harga coret',
            'cost_price' => 'harga modal',
            'supplier_product_url' => 'link produk supplier',
            'weight_gram' => 'berat',
        ];
    }
}
