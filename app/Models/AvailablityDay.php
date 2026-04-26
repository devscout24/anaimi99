<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailablityDay extends Model
{
    public const PROVIDER_TYPES = ['home_barbar', 'salon', 'salon_barbar'];

    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    protected $table = 'availability_days';

    protected $fillable = [
        'provider_id',
        'provider_type',
        'day_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'provider_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
