<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['service_name', 'salon_id'];

     public function salon()
    {
        return $this->belongsTo(User::class, 'salon_id');
    }

    public function salonLoyality()
    {
        return $this->hasMany(SalonLoyality::class, 'service_id');
    }

    public function servicePrice()
    {
        return $this->hasOne(ServicePrice::class, 'service_id');
    }

}
