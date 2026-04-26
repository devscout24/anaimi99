<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageGallary extends Model
{
    
    protected $fillable = [
        'provider_profile_id',
        'image',
    ];
}
