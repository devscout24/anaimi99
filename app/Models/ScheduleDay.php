<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleDay extends Model
{


    protected $fillable = [
        'provider_id',
        'buffer_time',
        'break_time',
        'schedule_duration',
        'start_time',
        'end_time',
        'provider_type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'provider_id' => 'integer',
            'buffer_time' => 'integer',
            'break_time' => 'integer',
            'schedule_duration' => 'integer',
        ];
    }

    public function scheduleTimeManages()
    {
        return $this->hasMany(ScheduleTimeManage::class, 'schedule_day_id');
    }

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
