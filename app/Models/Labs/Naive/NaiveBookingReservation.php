<?php

namespace App\Models\Labs\Naive;

use Database\Factories\Labs\Naive\NaiveBookingReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NaiveBookingReservation extends Model
{
    /** @use HasFactory<NaiveBookingReservationFactory> */
    use HasFactory;

    protected $table = 'naive_booking_reservations';

    protected $guarded = [];
}
