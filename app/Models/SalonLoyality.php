<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalonLoyality extends Model
{
    protected $fillable = [
        'salon_id',
        'service_id',
        'loyalty_points',
    ];

    public function salon()
    {
        return $this->belongsTo(User::class, 'salon_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
