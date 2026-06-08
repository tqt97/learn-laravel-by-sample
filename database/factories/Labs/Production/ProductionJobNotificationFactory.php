<?php

namespace Database\Factories\Labs\Production;

use App\Models\Labs\Production\ProductionJobNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionJobNotification>
 */
class ProductionJobNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient' => 'user@example.com',
            'sent_count' => 0,
        ];
    }
}
