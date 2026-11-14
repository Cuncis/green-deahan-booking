<?php

namespace Database\Factories;

use App\Models\KunjunganHalaman;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KunjunganHalaman>
 */
class KunjunganHalamanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => fake()->randomElement(['/', '/tentang-kami', '/galeri', '/blog', '/kontak']),
            'tanggal' => now(KunjunganHalaman::ZONA_WAKTU)->toDateString(),
            'pengunjung_hash' => hash('sha256', fake()->uuid()),
            'perangkat' => fake()->randomElement(['mobile', 'desktop', 'tablet']),
            'sumber_host' => fake()->optional()->randomElement(['www.google.com', 'l.instagram.com', 'wa.me']),
            'negara' => null,
            'wilayah' => null,
        ];
    }
}
