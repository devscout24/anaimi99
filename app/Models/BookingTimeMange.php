<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingTimeMange extends Model
{
    protected $guarded = [];

    public function scheduleTime()
    {
        return $this->belongsTo(ScheduleTimeManage::class, 'schedule_id');
    }
}
