<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingAssignHistory extends Model
{
    protected $guarded = [];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function barber()
    {
        return $this->belongsTo(User::class, 'barber_id');
    }
}
