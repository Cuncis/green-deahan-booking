<?php

namespace Database\Factories;

use App\Models\Cabang;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cabang>
 */
class CabangFactory extends Factory
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
            'nama_cabang' => fake()->company(),
            'alamat' => fake()->streetAddress(),
            'kota' => fake()->city(),
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
            'jam_buka' => '08:00',
            'jam_tutup' => '22:00',
            'status_aktif' => true,
        ];
    }
}
