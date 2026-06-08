<?php

namespace App\Models\Labs\Production;

use Database\Factories\Labs\Production\ProductionBookingRoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionBookingRoom extends Model
{
    /** @use HasFactory<ProductionBookingRoomFactory> */
    use HasFactory;

    protected $table = 'production_booking_rooms';

    protected $guarded = [];
}
