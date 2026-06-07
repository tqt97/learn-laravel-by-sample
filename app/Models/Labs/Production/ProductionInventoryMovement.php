<?php

namespace App\Models\Labs\Production;

use Database\Factories\Labs\Production\ProductionInventoryMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductionInventoryMovement extends Model
{
    use HasFactory;

    protected $table = 'production_inventory_movements';

    protected $fillable = [
        'product_id',
        'type',
        'stock_delta',
        'reserved_delta',
        'sold_delta',
        'stock_on_hand_after',
        'reserved_stock_after',
        'sold_stock_after',
        'reference_type',
        'reference_id',
    ];

    protected static function newFactory(): ProductionInventoryMovementFactory
    {
        return ProductionInventoryMovementFactory::new();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductionInventoryProduct::class, 'product_id');
    }
}
