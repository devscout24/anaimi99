<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyalityAdd extends Model
{
    protected $fillable=[
        'customer_id',
        'salon_id',
        'barber_id',
        'booking_id',
        'per_booking_loyality_point',
        'remaining_loyality_point',
        'costing_loyality_point',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function salon()
    {
        return $this->belongsTo(User::class, 'salon_id');
    }

    public function barber()
    {
        return $this->belongsTo(User::class, 'barber_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
