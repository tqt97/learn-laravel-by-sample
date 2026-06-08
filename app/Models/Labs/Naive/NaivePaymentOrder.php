<?php

namespace App\Models\Labs\Naive;

use Database\Factories\Labs\Naive\NaivePaymentOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NaivePaymentOrder extends Model
{
    /** @use HasFactory<NaivePaymentOrderFactory> */
    use HasFactory;

    protected $table = 'naive_payment_orders';

    protected $guarded = [];
}
