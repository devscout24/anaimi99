<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlotBlockBarber extends Model
{
    protected $fillable = [
        'salon_id',
        'barber_id',
        'block_date',
        'slot_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'salon_id' => 'integer',
            'barber_id' => 'integer',
            'block_date' => 'datetime:Y-m-d H:i:s',
            'slot_id' => 'integer',
            'status' => 'string',
        ];
    }

    public function scheduleTimeManage()
    {
        return $this->belongsTo(ScheduleTimeManage::class, 'slot_id');
    }

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
