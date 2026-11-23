<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => strtoupper(fake()->unique()->word()),
            'sell_price' => null,
            'cost_price' => null,
            'is_available' => true,
            'sort_order' => 0,
        ];
    }

    public function habis(): static
    {
        return $this->state(['is_available' => false]);
    }
}
