<?php

namespace Database\Factories\Labs\Naive;

use App\Models\Labs\Naive\NaiveBookingRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NaiveBookingRoom>
 */
class NaiveBookingRoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Naive Meeting Room',
        ];
    }
}
