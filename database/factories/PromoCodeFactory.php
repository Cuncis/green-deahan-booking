<?php

namespace Database\Factories;

use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromoCode>
 */
class PromoCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PROMO####')),
            'description' => null,
            'type' => PromoCode::PERCENT,
            'value' => 10,
            'max_discount' => null,
            'min_subtotal' => 0,
            'quota' => null,
            'starts_on' => null,
            'ends_on' => null,
            'is_active' => true,
        ];
    }

    public function nominal(int $rupiah): static
    {
        return $this->state(['type' => PromoCode::FIXED, 'value' => $rupiah]);
    }

    public function nonaktif(): static
    {
        return $this->state(['is_active' => false]);
    }
}
