<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($booking) {
            $latest = self::latest('id')->first();
            $nextId = $latest ? $latest->id + 1 : 1;
            $booking->invoice_no = 'INV-' . (1000 + $nextId);
        });
    }

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

    public function bookingLoyality(){
         return $this->hasOne(LoyalityAdd::class,'booking_id');
    }

    public function salon(){
        return $this->belongsTo(User::class,'salon_id');
    }

}
