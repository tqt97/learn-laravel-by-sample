<?php

namespace App\Models\Labs\Naive;

use Database\Factories\Labs\Naive\NaiveBookingRoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NaiveBookingRoom extends Model
{
    /** @use HasFactory<NaiveBookingRoomFactory> */
    use HasFactory;

    protected $table = 'naive_booking_rooms';

    protected $guarded = [];
}
