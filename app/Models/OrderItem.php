<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'product_id', 'variant_id', 'product_name', 'variant_name', 'unit_price', 'unit_cost', 'qty'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'unit_cost' => 'integer',
            'qty' => 'integer',
        ];
    }

    /**
     * Nama untuk dibaca orang: "Kaos Kaki Helios (BLACK)" kalau ada varian.
     */
    public function namaLengkap(): string
    {
        return $this->variant_name ? "{$this->product_name} ({$this->variant_name})" : $this->product_name;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
