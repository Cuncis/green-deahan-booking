<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_bisnis' => fake()->company(),
            'domain' => fake()->unique()->domainName(),
            'paket' => fake()->randomElement(['basic', 'pro', 'premium']),
            'status_aktif' => true,
            'tanggal_mulai' => now()->subMonth(),
            'tanggal_berakhir' => now()->addYear(),
            'warna_utama' => '#3A6B4A',
        ];
    }
}
