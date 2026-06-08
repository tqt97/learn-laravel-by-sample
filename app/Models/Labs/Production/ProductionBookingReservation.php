<?php

namespace App\Models\Labs\Production;

use Database\Factories\Labs\Production\ProductionBookingReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionBookingReservation extends Model
{
    /** @use HasFactory<ProductionBookingReservationFactory> */
    use HasFactory;

    protected $table = 'production_booking_reservations';

    protected $guarded = [];
}
