<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\ReminderLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReminderLog>
 */
class ReminderLogFactory extends Factory
{
    protected $model = ReminderLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'booking_id' => Booking::factory(),
            'waktu_kirim' => now(),
            'status' => 'terkirim',
            'pesan' => 'Pengingat, jadwal main kamu 2 jam lagi.',
        ];
    }
}
