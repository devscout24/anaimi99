<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleTimeManage extends Model
{

    protected $fillable = [
        'provider_id',
        'schedule_day_id',
        'scheduled_start_time',
        'scheduled_end_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'provider_id' => 'integer',
            'schedule_day_id' => 'integer',
            'scheduled_start_time' => 'datetime:H:i:s',
            'scheduled_end_time' => 'datetime:H:i:s',
            'status' => 'string',
        ];
    }

    public function scheduleDay()
    {
        return $this->belongsTo(ScheduleDay::class, 'schedule_day_id');
    }

    protected $hidden= [
        'created_at',
        'updated_at',
    ];
}
