<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['service_name'];

    public function salonLoyality()
    {
        return $this->hasMany(SalonLoyality::class, 'service_id');
    }

}
