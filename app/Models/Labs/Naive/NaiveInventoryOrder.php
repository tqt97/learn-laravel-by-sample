<?php

namespace App\Models\Labs\Naive;

use Database\Factories\Labs\Naive\NaiveInventoryOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NaiveInventoryOrder extends Model
{
    use HasFactory;

    protected $table = 'naive_inventory_orders';

    protected $fillable = [
        'product_id',
        'quantity',
        'status',
    ];

    protected static function newFactory(): NaiveInventoryOrderFactory
    {
        return NaiveInventoryOrderFactory::new();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(NaiveInventoryProduct::class, 'product_id');
    }
}
