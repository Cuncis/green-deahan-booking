<?php

namespace Database\Factories;

use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalSlot>
 */
class JadwalSlotFactory extends Factory
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
            'lapangan_id' => Lapangan::factory(),
            'tanggal' => now()->addDay()->toDateString(),
            'jam_mulai' => '10:00',
            'jam_selesai' => '11:00',
            'harga' => fake()->numberBetween(50000, 300000),
            'status' => 'kosong',
            'hold_sampai' => null,
        ];
    }
}
