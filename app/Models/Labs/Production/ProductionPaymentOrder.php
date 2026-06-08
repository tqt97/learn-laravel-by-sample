<?php

namespace App\Models\Labs\Production;

use Database\Factories\Labs\Production\ProductionPaymentOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPaymentOrder extends Model
{
    /** @use HasFactory<ProductionPaymentOrderFactory> */
    use HasFactory;

    protected $table = 'production_payment_orders';

    protected $guarded = [];
}
