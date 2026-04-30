<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function barber()
    {
        return $this->belongsTo(User::class, 'barber_id');
    }

    public function items()
    {
        return $this->hasMany(BookingItemManage::class, 'booking_id');
    }

    public function slots()
    {
        return $this->hasMany(BookingTimeMange::class, 'booking_id');
    }
}
