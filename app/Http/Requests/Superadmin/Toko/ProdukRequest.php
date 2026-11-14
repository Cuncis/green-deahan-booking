<?php

namespace App\Http\Requests\Superadmin\Toko;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        ];
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
