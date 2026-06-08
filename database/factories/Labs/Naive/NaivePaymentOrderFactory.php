<?php

namespace Database\Factories\Labs\Naive;

use App\Models\Labs\Naive\NaivePaymentOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NaivePaymentOrder>
 */
class NaivePaymentOrderFactory extends Factory
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
        ];
    }
}
