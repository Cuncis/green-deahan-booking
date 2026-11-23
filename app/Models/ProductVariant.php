<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu pilihan varian produk (misalnya warna BLACK). Harga jual dan modal
 * boleh kosong: kosong berarti memakai harga produknya.
 */
#[Fillable(['product_id', 'name', 'sell_price', 'cost_price', 'is_available', 'sort_order'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /** Batas varian per produk. */
    public const MAKS = 30;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sell_price' => 'integer',
            'cost_price' => 'integer',
            'is_available' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hargaJual(): int
    {
        return $this->sell_price ?? $this->product->sell_price;
    }

    public function hargaModal(): int
    {
        return $this->cost_price ?? $this->product->cost_price;
    }
}
