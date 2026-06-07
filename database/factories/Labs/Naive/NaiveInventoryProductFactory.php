<?php

namespace Database\Factories\Labs\Naive;

use App\Models\Labs\Naive\NaiveInventoryProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NaiveInventoryProduct>
 */
final class NaiveInventoryProductFactory extends Factory
{
    protected $model = NaiveInventoryProduct::class;

    public function definition(): array
    {
        return [
            'name' => 'Naive Product',
            'stock' => 1,
        ];
    }

    public function oneStock(): static
    {
        return $this->state([
            'name' => 'Naive Product',
            'stock' => 1,
        ]);
    }

    public function soldOut(): static
    {
        return $this->state([
            'stock' => 0,
        ]);
    }

    public function withStock(int $stock): static
    {
        return $this->state([
            'stock' => $stock,
        ]);
    }
}
