<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Produk toko. cost_price, supplier_id, dan supplier_product_url PRIVAT:
 * disembunyikan dari serialisasi, dan halaman pelanggan hanya menerima
 * array yang disusun lewat dataPublik().
 */
#[Fillable([
    'name',
    'slug',
    'category',
    'unit',
    'badge',
    'description',
    'image_path',
    'sell_price',
    'compare_price',
    'cost_price',
    'supplier_id',
    'supplier_product_url',
    'weight_gram',
    'is_active',
])]
#[Hidden(['cost_price', 'supplier_id', 'supplier_product_url'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @var array<string, string>
     */
    public const KATEGORI = [
        'rumput' => 'Rumput Sintetis',
        'lantai' => 'Lantai Interlock',
        'perlengkapan' => 'Perlengkapan Lapangan',
        'pencahayaan' => 'Pencahayaan',
        'perawatan' => 'Material Perawatan',
        'olahraga' => 'Perlengkapan Olahraga',
    ];

    /**
     * Satuan jual yang bisa dipilih admin.
     *
     * @var array<int, string>
     */
    public const SATUAN = ['pcs', 'm²', 'meter', 'set', 'pasang', 'unit', 'sak', 'pail', 'roll', 'tabung', 'slop'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sell_price' => 'integer',
            'compare_price' => 'integer',
            'cost_price' => 'integer',
            'weight_gram' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function scopeAktif(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Data aman untuk pelanggan. Jangan pernah kirim model mentah ke halaman toko.
     *
     * @return array{id: int, name: string, slug: string, category: string, categoryLabel: string, unit: string, badge: string|null, description: string|null, imageUrl: string|null, price: int, comparePrice: int|null}
     */
    public function dataPublik(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'categoryLabel' => self::KATEGORI[$this->category] ?? $this->category,
            'unit' => $this->unit,
            'badge' => $this->badge,
            'description' => $this->description,
            'imageUrl' => $this->image_path,
            'price' => $this->sell_price,
            // Harga coret hanya tampil kalau memang lebih mahal dari harga jual.
            'comparePrice' => $this->compare_price !== null && $this->compare_price > $this->sell_price ? $this->compare_price : null,
        ];
    }
}
