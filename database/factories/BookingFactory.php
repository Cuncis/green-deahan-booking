<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\JadwalSlot;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hargaNormal = fake()->numberBetween(50000, 300000);

        return [
            'tenant_id' => Tenant::factory(),
            'slot_id' => JadwalSlot::factory(),
            'customer_id' => Customer::factory(),
            'kode_booking' => Booking::generateKodeBooking(),
            'harga_normal' => $hargaNormal,
            'diskon_jumlah' => 0,
            'total_bayar' => $hargaNormal,
            'tipe_pembayaran' => 'lunas',
            'status_booking' => 'menunggu',
        ];
    }
}
