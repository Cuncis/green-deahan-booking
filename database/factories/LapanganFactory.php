<?php

namespace Database\Factories;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lapangan>
 */
class LapanganFactory extends Factory
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
            'cabang_id' => Cabang::factory(),
            'nama' => 'Lapangan '.fake()->numberBetween(1, 10),
            'jenis_olahraga' => fake()->randomElement(['futsal', 'padel', 'badminton', 'tennis', 'mini soccer']),
            'harga_per_jam' => fake()->numberBetween(50000, 300000),
            'harga_jam_sibuk' => null,
            'status_aktif' => true,
        ];
    }
}
