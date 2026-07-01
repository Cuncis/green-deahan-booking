<?php

namespace Database\Factories;

use App\Models\Staf;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staf>
 */
class StafFactory extends Factory
{
    protected $model = Staf::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement(['owner', 'manager', 'staff']),
            'status_aktif' => true,
        ];
    }
}
