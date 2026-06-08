<?php

namespace Database\Factories\Labs\Production;

use App\Models\Labs\Production\ProductionPaymentOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionPaymentOrder>
 */
class ProductionPaymentOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => 100000,
            'status' => 'pending',
            'paid_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'amount' => 100000,
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }
}
