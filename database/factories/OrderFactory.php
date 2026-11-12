<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Order::buatKode(),
            'customer_name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->streetAddress(),
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'postal_code' => '12345',
            'subtotal' => 100000,
            'shipping_cost' => 15000,
            'total' => 115000,
            'status' => OrderStatus::PendingPayment,
            'mayar_payment_id' => fake()->uuid(),
            'mayar_payment_link' => 'https://testingmayar.myr.id/pl/'.fake()->lexify('??????'),
        ];
    }
}
