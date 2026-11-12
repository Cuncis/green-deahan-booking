<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'platform' => fake()->randomElement(['shopee', 'tokopedia', 'direct']),
            'shop_url' => fake()->url(),
            'whatsapp' => fake()->numerify('08##########'),
            'notes' => null,
        ];
    }
}
