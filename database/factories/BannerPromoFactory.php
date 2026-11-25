<?php

namespace Database\Factories;

use App\Models\BannerPromo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BannerPromo>
 */
class BannerPromoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul' => fake()->sentence(3),
            'foto_url' => 'http://greendeahan.com/storage/banner/contoh.jpg',
            'tautan' => '/toko',
            'label_tombol' => null,
            'tampilkan_tombol' => true,
            'mulai_pada' => null,
            'berakhir_pada' => null,
            'target' => 'semua',
            'frekuensi' => 'sekali',
            'tunda_detik' => 2,
            'status_aktif' => true,
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(['status_aktif' => false]);
    }
}
