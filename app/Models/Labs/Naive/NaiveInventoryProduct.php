<?php

namespace App\Models\Labs\Naive;

use Database\Factories\Labs\Naive\NaiveInventoryProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NaiveInventoryProduct extends Model
{
    use HasFactory;

    protected $table = 'naive_inventory_products';

    protected $fillable = [
        'name',
        'stock',
    ];

    protected static function newFactory(): NaiveInventoryProductFactory
    {
        return NaiveInventoryProductFactory::new();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(NaiveInventoryOrder::class, 'product_id');
    }
}
