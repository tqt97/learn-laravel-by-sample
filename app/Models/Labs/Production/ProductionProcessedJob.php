<?php

namespace App\Models\Labs\Production;

use Database\Factories\Labs\Production\ProductionProcessedJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionProcessedJob extends Model
{
    /** @use HasFactory<ProductionProcessedJobFactory> */
    use HasFactory;

    protected $table = 'production_processed_jobs';

    protected $guarded = [];
}
