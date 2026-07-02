<?php

namespace Database\Factories;

use App\Models\GaleriItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GaleriItem>
 */
class GaleriItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kategori' => fake()->randomElement(['futsal', 'minisoccer', 'padel', 'badminton', 'proses']),
            'judul' => 'Proyek '.fake()->words(3, true),
            'kota' => fake()->city(),
            'material' => fake()->randomElement(['Lantai Interlock', 'Rumput Sintetis', 'Kaca Tempered + Artificial Grass']),
            'deskripsi' => fake()->sentence(15),
            'foto_url' => 'https://gdlogin.greendeahan.com/wp-content/uploads/2024/07/interlock1.jpg',
            'tampilan_besar' => fake()->boolean(30),
            'urutan' => fake()->numberBetween(1, 100),
            'status_aktif' => true,
        ];
    }
}
