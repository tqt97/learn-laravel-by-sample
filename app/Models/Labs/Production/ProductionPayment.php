<?php

namespace App\Models\Labs\Production;

use Database\Factories\Labs\Production\ProductionPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionPayment extends Model
{
    /** @use HasFactory<ProductionPaymentFactory> */
    use HasFactory;

    protected $table = 'production_payments';

    protected $guarded = [];
}
