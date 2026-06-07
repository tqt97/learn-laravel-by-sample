<?php

namespace Database\Factories\Labs\Production;

use App\Models\Labs\Production\ProductionInventoryProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionInventoryProduct>
 */
final class ProductionInventoryProductFactory extends Factory
{
    protected $model = ProductionInventoryProduct::class;

    public function definition(): array
    {
        return [
            'name' => 'Production Product',
            'stock_on_hand' => 1,
            'reserved_stock' => 0,
            'sold_stock' => 0,
        ];
    }

    public function oneStock(): static
    {
        return $this->state([
            'name' => 'Production Product',
            'stock_on_hand' => 1,
            'reserved_stock' => 0,
            'sold_stock' => 0,
        ]);
    }

    public function soldOut(): static
    {
        return $this->state([
            'stock_on_hand' => 1,
            'reserved_stock' => 1,
            'sold_stock' => 0,
        ]);
    }

    public function withStock(int $stock): static
    {
        return $this->state([
            'stock_on_hand' => $stock,
            'reserved_stock' => 0,
            'sold_stock' => 0,
        ]);
    }
}
