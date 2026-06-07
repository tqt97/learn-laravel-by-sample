<?php

namespace App\Models\Labs\Production;

use Database\Factories\Labs\Production\ProductionInventoryOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductionInventoryOrder extends Model
{
    use HasFactory;

    protected $table = 'production_inventory_orders';

    protected $fillable = [
        'product_id',
        'quantity',
        'status',
        'request_key',
    ];

    protected static function newFactory(): ProductionInventoryOrderFactory
    {
        return ProductionInventoryOrderFactory::new();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductionInventoryProduct::class, 'product_id');
    }
}
