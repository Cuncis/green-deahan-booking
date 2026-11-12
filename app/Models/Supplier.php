<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Supplier toko online (dropship). Hanya terlihat oleh superadmin.
 */
#[Fillable(['name', 'platform', 'shop_url', 'whatsapp', 'notes'])]
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    public const PLATFORM = ['shopee', 'tokopedia', 'direct'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
