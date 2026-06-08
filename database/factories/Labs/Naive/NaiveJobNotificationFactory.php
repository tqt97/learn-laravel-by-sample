<?php

namespace Database\Factories\Labs\Naive;

use App\Models\Labs\Naive\NaiveJobNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NaiveJobNotification>
 */
class NaiveJobNotificationFactory extends Factory
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
