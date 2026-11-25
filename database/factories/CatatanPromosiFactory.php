<?php

namespace Database\Factories;

use App\Models\CatatanPromosi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatatanPromosi>
 */
class CatatanPromosiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul' => fake()->sentence(4),
            'kategori' => 'lainnya',
            'isi' => fake()->paragraph(),
            'disematkan' => false,
        ];
    }

    public function disematkan(): static
    {
        return $this->state(['disematkan' => true]);
    }
}
