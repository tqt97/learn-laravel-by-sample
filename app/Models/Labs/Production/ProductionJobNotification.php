<?php

namespace App\Models\Labs\Production;

use Database\Factories\Labs\Production\ProductionJobNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionJobNotification extends Model
{
    /** @use HasFactory<ProductionJobNotificationFactory> */
    use HasFactory;

    protected $table = 'production_job_notifications';

    protected $guarded = [];
}
