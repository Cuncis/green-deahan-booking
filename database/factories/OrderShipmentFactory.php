<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderShipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderShipment>
 */
class OrderShipmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'courier' => fake()->randomElement(['JNE', 'J&T', 'SiCepat', 'AnterAja']),
            'tracking_number' => strtoupper(fake()->bothify('??########')),
            'shipped_from' => null,
        ];
    }
}
