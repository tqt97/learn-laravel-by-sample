<?php

namespace App\Models\Labs\Production;

use Database\Factories\Labs\Production\ProductionIdempotencyKeyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionIdempotencyKey extends Model
{
    /** @use HasFactory<ProductionIdempotencyKeyFactory> */
    use HasFactory;

    protected $table = 'production_idempotency_keys';

    protected $guarded = [];
}
