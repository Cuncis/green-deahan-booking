<?php

namespace Database\Factories;

use App\Models\Artikel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Artikel>
 */
class ArtikelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $judul = fake()->sentence(6);

        return [
            'judul' => $judul,
            'slug' => Str::slug($judul).'-'.fake()->unique()->numberBetween(1, 100000),
            'kategori' => fake()->randomElement(['Panduan Bisnis', 'Tips Perawatan', 'Futsal', 'Material', 'Badminton', 'Mini Soccer', 'Padel']),
            'ringkasan' => fake()->paragraph(),
            'konten' => '<p>'.fake()->paragraphs(5, true).'</p>',
            'foto_url' => 'https://cdn.libradigital.id/site-assets/interlock1.jpg',
            'status_aktif' => true,
            'tanggal_terbit' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
        ];
    }
}
