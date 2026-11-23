<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    'specs',
    'variant_label',
    'image_path',
    'sell_price',
    'compare_price',
    'cost_price',
    'supplier_id',
    'supplier_product_url',
    'weight_gram',
    'origin_postal_code',
    'is_active',
])]
#[Hidden(['cost_price', 'supplier_id', 'supplier_product_url'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** Batas baris spesifikasi per produk. */
    public const MAKS_SPESIFIKASI = 20;

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
            'specs' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Pilihan varian (warna, ukuran), sesuai urutan di admin.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Foto produk, foto utama di urutan pertama.
     *
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('urutan')->orderBy('id');
    }

    /**
     * Alamat semua foto, foto utama dulu. Produk lama tanpa baris di
     * product_images tetap menampilkan image_path.
     *
     * @return array<int, string>
     */
    public function daftarFoto(): array
    {
        $foto = $this->images->pluck('path')->all();

        return $foto === [] && $this->image_path ? [$this->image_path] : $foto;
    }

    public function scopeAktif(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Data aman untuk pelanggan. Jangan pernah kirim model mentah ke halaman toko.
     *
     * @return array{id: int, name: string, slug: string, category: string, categoryLabel: string, unit: string, badge: string|null, description: string|null, specs: array<int, array{nama: string, nilai: string}>, variantLabel: string|null, variants: array<int, array{id: int, name: string, price: int, available: bool}>, imageUrl: string|null, images: array<int, string>, price: int, comparePrice: int|null}
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
            'specs' => $this->specs ?? [],
            'variantLabel' => $this->variant_label,
            'variants' => $this->variants->map(fn (ProductVariant $varian) => [
                'id' => $varian->id,
                'name' => $varian->name,
                'price' => $varian->sell_price ?? $this->sell_price,
                'available' => $varian->is_available,
            ])->all(),
            'imageUrl' => $this->image_path,
            'images' => $this->daftarFoto(),
            'price' => $this->sell_price,
            // Harga coret hanya tampil kalau memang lebih mahal dari harga jual.
            'comparePrice' => $this->compare_price !== null && $this->compare_price > $this->sell_price ? $this->compare_price : null,
        ];
    }
}
