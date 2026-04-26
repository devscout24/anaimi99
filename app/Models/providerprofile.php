<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class providerprofile extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'business_name',
        'representative_name',
        'since',
        'about',
        'street_number',
        'vat_number',
        'experience',
        'postal_code',
        'salon_address',
        'available'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}