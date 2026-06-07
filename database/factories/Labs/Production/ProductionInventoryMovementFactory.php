<?php

namespace Database\Factories\Labs\Production;

use App\Models\Labs\Production\ProductionInventoryMovement;
use App\Models\Labs\Production\ProductionInventoryProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionInventoryMovement>
 */
final class ProductionInventoryMovementFactory extends Factory
{
    protected $model = ProductionInventoryMovement::class;

    public function definition(): array
    {
        return [
            'product_id' => ProductionInventoryProduct::factory(),
            'type' => 'reserve',
            'stock_delta' => 0,
            'reserved_delta' => 1,
            'sold_delta' => 0,
            'stock_on_hand_after' => 1,
            'reserved_stock_after' => 1,
            'sold_stock_after' => 0,
            'reference_type' => null,
            'reference_id' => null,
        ];
    }

    public function reserve(): static
    {
        return $this->state([
            'type' => 'reserve',
            'stock_delta' => 0,
            'reserved_delta' => 1,
            'sold_delta' => 0,
        ]);
    }
}
