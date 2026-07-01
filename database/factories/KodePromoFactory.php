<?php

namespace Database\Factories;

use App\Models\KodePromo;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KodePromo>
 */
class KodePromoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'kode' => strtoupper(fake()->unique()->bothify('PROMO##')),
            'tipe_diskon' => 'persen',
            'nilai' => 10,
            'tanggal_mulai' => now()->subDay(),
            'tanggal_berakhir' => now()->addMonth(),
            'kuota' => null,
            'status_aktif' => true,
        ];
    }
}
