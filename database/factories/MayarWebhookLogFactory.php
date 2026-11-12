<?php

namespace Database\Factories;

use App\Models\MayarWebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MayarWebhookLog>
 */
class MayarWebhookLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event' => 'payment.received',
            'payment_id' => fake()->uuid(),
            'payload' => ['event' => 'payment.received'],
            'result' => 'diterima',
        ];
    }
}
