<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Membership;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => Customer::factory(),
            'tier' => fake()->randomElement(['bronze', 'silver', 'gold']),
            'total_booking' => fake()->numberBetween(1, 30),
        ];
    }
}
