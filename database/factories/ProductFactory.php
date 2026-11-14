<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->unique()->words(3, true);

        return [
            'name' => ucfirst($nama),
            'slug' => Str::slug($nama).'-'.fake()->unique()->numberBetween(100, 999),
            'category' => fake()->randomElement(array_keys(Product::KATEGORI)),
            'unit' => 'pcs',
            'badge' => null,
            'compare_price' => null,
            'description' => fake()->sentence(),
            'image_path' => null,
            'sell_price' => 100000,
            'cost_price' => 70000,
            'supplier_id' => Supplier::factory(),
            'supplier_product_url' => fake()->url(),
            'weight_gram' => 300,
            'is_active' => true,
        ];
    }
}
