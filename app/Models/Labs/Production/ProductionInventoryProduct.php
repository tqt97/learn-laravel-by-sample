<?php

namespace App\Models\Labs\Production;

use Database\Factories\Labs\Production\ProductionInventoryProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductionInventoryProduct extends Model
{
    use HasFactory;

    protected $table = 'production_inventory_products';

    protected $fillable = [
        'name',
        'stock_on_hand',
        'reserved_stock',
        'sold_stock',
    ];

    protected static function newFactory(): ProductionInventoryProductFactory
    {
        return ProductionInventoryProductFactory::new();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ProductionInventoryOrder::class, 'product_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ProductionInventoryMovement::class, 'product_id');
    }

    public function availableStock(): int
    {
        return $this->stock_on_hand - $this->reserved_stock;
    }
}
