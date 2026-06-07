<?php

namespace Database\Factories\Labs\Production;

use App\Models\Labs\Production\ProductionInventoryOrder;
use App\Models\Labs\Production\ProductionInventoryProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductionInventoryOrder>
 */
final class ProductionInventoryOrderFactory extends Factory
{
    protected $model = ProductionInventoryOrder::class;

    public function definition(): array
    {
        return [
            'product_id' => ProductionInventoryProduct::factory(),
            'quantity' => 1,
            'status' => 'created',
            'request_key' => (string) Str::uuid(),
        ];
    }

    public function forProduct(ProductionInventoryProduct $product): static
    {
        return $this->state([
            'product_id' => $product->id,
        ]);
    }

    public function withRequestKey(string $requestKey): static
    {
        return $this->state([
            'request_key' => $requestKey,
        ]);
    }
}
