<?php

namespace Database\Factories\Labs\Production;

use App\Models\Labs\Production\ProductionBookingRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionBookingRoom>
 */
class ProductionBookingRoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Production Meeting Room',
        ];
    }
}
