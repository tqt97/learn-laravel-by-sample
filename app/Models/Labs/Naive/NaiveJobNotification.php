<?php

namespace App\Models\Labs\Naive;

use Database\Factories\Labs\Naive\NaiveJobNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NaiveJobNotification extends Model
{
    /** @use HasFactory<NaiveJobNotificationFactory> */
    use HasFactory;

    protected $table = 'naive_job_notifications';

    protected $guarded = [];
}
