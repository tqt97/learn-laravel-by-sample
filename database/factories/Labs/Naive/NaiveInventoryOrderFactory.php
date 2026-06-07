<?php

namespace Database\Factories\Labs\Naive;

use App\Models\Labs\Naive\NaiveInventoryOrder;
use App\Models\Labs\Naive\NaiveInventoryProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NaiveInventoryOrder>
 */
final class NaiveInventoryOrderFactory extends Factory
{
    protected $model = NaiveInventoryOrder::class;

    public function definition(): array
    {
        return [
            'product_id' => NaiveInventoryProduct::factory(),
            'quantity' => 1,
            'status' => 'created',
        ];
    }

    public function forProduct(NaiveInventoryProduct $product): static
    {
        return $this->state([
            'product_id' => $product->id,
        ]);
    }
}
