<?php

namespace App\Models\Labs\Naive;

use Database\Factories\Labs\Naive\NaivePaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NaivePayment extends Model
{
    /** @use HasFactory<NaivePaymentFactory> */
    use HasFactory;

    protected $table = 'naive_payments';

    protected $guarded = [];
}
