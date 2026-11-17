<?php

namespace Database\Factories;

use App\Models\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wilayah>
 */
class WilayahFactory extends Factory
{
    /**
     * Provinsi secara bawaan. Untuk tingkat lain pakai state kelurahan() atau isi kode dan induk sendiri.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kode = (string) fake()->unique()->numberBetween(11, 99);

        return [
            'kode' => $kode,
            'nama' => fake()->words(2, true),
            'level' => Wilayah::LEVEL_PROVINSI,
            'induk' => null,
            'kodepos' => null,
        ];
    }

    public function kelurahan(string $kode, string $kodepos = '12345'): static
    {
        return $this->state(fn () => [
            'kode' => $kode,
            'level' => Wilayah::LEVEL_KELURAHAN,
            'induk' => Wilayah::indukDari($kode),
            'kodepos' => $kodepos,
        ]);
    }
}
