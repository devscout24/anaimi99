<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ScheduleDay;
use App\Models\ScheduleTimeManage;
use App\Models\SlotBlockBarber;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    use ApiResponse;

    private function resolveScheduleOwner(): array
    {
        $user = Auth::user();

        if ($user && $user->role === 'salon_barbar' && !empty($user->salon_id)) {
            $owner = User::find($user->salon_id);

            if ($owner) {
                return [
                    'owner_id' => $owner->id,
                    'owner_name' => $owner->name,
                    'is_salon_fallback' => true,
                ];
            }
        }

        return [
            'owner_id' => Auth::id(),
            'owner_name' => $user?->name,
            'is_salon_fallback' => false,
        ];
    }

    private function resolveTargetBarber(?int $barberId = null): ?User
    {
        if ($barberId) {
            return User::find($barberId);
        }

        return Auth::user();
    }

    private function resolveScheduleOwnerForBarber(?User $barber = null): array
    {
        if ($barber && $barber->role === 'salon_barbar' && !empty($barber->salon_id)) {
            $salon = User::find($barber->salon_id);

            if ($salon) {
                return [
                    'owner_id' => $salon->id,
                    'owner_name' => $salon->name,
                    'owner_type' => 'salon',
                ];
            }
        }

        if ($barber) {
            return [
                'owner_id' => $barber->id,
                'owner_name' => $barber->name,
                'owner_type' => $barber->role === 'salon' ? 'salon' : 'barber',
            ];
        }

        $authUser = Auth::user();

        return [
            'owner_id' => Auth::id(),
            'owner_name' => $authUser?->name,
            'owner_type' => $authUser?->role === 'salon' ? 'salon' : 'barber',
        ];
    }


    public function addSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'buffer_time' => 'nullable|integer|min:0',
            'break_time' => 'nullable|integer|min:0',
            'schedule_duration' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        DB::beginTransaction();

        try {
            $existingSchedule = ScheduleDay::where('provider_id', Auth::id())->first();

            if ($existingSchedule) {
                return $this->success([], 'Schedule already created. After that you can only block or unblock slots.');
            }

            $startTime = Carbon::createFromFormat('H:i:s', $request->start_time);
            $endTime = Carbon::createFromFormat('H:i:s', $request->end_time);
            $duration = (int) $request->schedule_duration;
            $buffer = (int) ($request->buffer_time ?? 0);

            $scheduleDay = ScheduleDay::updateOrCreate(
                ['provider_id' => Auth::id()],
                [
                    'start_time' => $startTime->format('H:i:s'),
                    'end_time' => $endTime->format('H:i:s'),
                    'buffer_time' => $buffer,
                    'break_time' => (int) ($request->break_time ?? 0),
                    'schedule_duration' => $duration,
                ]
            );

            ScheduleTimeManage::where('provider_id', Auth::id())
                ->where('schedule_day_id', $scheduleDay->id)
                ->delete();

            $current = $startTime->copy();

            while ($current->copy()->addMinutes($duration)->lte($endTime)) {

                ScheduleTimeManage::create([
                    'provider_id' => Auth::id(),
                    'schedule_day_id' => $scheduleDay->id,
                    'scheduled_start_time' => $current->format('H:i:s'),
                    'scheduled_end_time' => $current->copy()
                        ->addMinutes($duration)
                        ->format('H:i:s'),
                ]);

                $current->addMinutes($duration + $buffer);
            }

            DB::commit();

            return $this->success([
                'message' => 'Schedule and slots created successfully'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return $this->error($e->getMessage());
        }
    }

    // public function getSchedule(Request $request)
    // {
    //     try {
    //         $schedule = ScheduleDay::with(['scheduleTimeManages'])
    //             ->where('provider_id', Auth::id())
    //             ->latest()
    //             ->first();

    //         return $this->success($schedule);
    //     } catch (\Exception $e) {
    //         return $this->error($e->getMessage());
    //     }
    // }


    public function  blockSlot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slot_id' => 'required|array',
            'slot_id.*' => 'required|exists:schedule_time_manages,id',
            'block_date' => 'required|date',
            'barber_id' => 'nullable|exists:users,id',
            'salon_id' => 'nullable|exists:users,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            $status = $request->status ?? 'active';

            foreach ($request->slot_id as $slotId) {
                SlotBlockBarber::updateOrCreate([
                    'slot_id' => $slotId,
                    'block_date' => $request->block_date,
                    'barber_id' => $request->barber_id,
                    'salon_id' => $request->salon_id,
                ], [
                    'salon_id' => $request->salon_id,
                    'barber_id' => $request->barber_id,
                    'block_date' => $request->block_date,
                    'slot_id' => $slotId,
                    'status' => $status,
                ]);
            }

            return $this->success([
                'message' => $status === 'active' ? 'Slot blocked successfully' : 'Slot unblocked successfully'
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }


   public function barberSalonSlots(Request $request, $id = null)
{
    try {

        $targetBarber = $this->resolveTargetBarber(
            $request->barber_id
                ? (int)$request->barber_id
                : ($id ? (int)$id : null)
        );

        if (!$targetBarber) {
            return $this->validationError([], 'Barber not found.');
        }

        $owner = $this->resolveScheduleOwnerForBarber($targetBarber);

        $schedule = ScheduleDay::with('scheduleTimeManages')
            ->where('provider_id', $owner['owner_id'])
            ->latest()
            ->first();

        if (!$schedule) {
            return $this->validationError([],'No schedule found.');
        }


        /*
        BLOCK QUERY
        */
        $blockedQuery = SlotBlockBarber::where('status','active');


        if ($targetBarber->role === 'salon') {

            $blockedQuery->where('salon_id', $targetBarber->id);

        } elseif ($targetBarber->role === 'salon_barbar') {

            $blockedQuery->where(function ($q) use ($targetBarber) {

                $q->where('barber_id', $targetBarber->id);

                if ($targetBarber->salon_id) {
                    $q->orWhere('salon_id', $targetBarber->salon_id);
                }

            });

        } else {

            $blockedQuery->where('barber_id', $targetBarber->id);
        }


        /*
        IMPORTANT FIX: ensure integer cast
        */
        $blockedSlotIds = $blockedQuery
            ->pluck('slot_id')
            ->map(fn($id) => (int)$id)
            ->toArray();


        /*
        SLOT MAP
        */
        $slots = $schedule->scheduleTimeManages->map(function ($slot) use ($blockedSlotIds) {

            return [
                'slot_id' => $slot->id,
                'scheduled_start_time' => $slot->scheduled_start_time,
                'scheduled_end_time' => $slot->scheduled_end_time,

                // FIX: no strict compare issue
                'is_blocked' => in_array((int)$slot->id, $blockedSlotIds),

            ];
        });


        $responseData = [
            'schedule_owner_name' => $owner['owner_name'],
            'schedule_owner_type' => $owner['owner_type'],
            'blocked_slot_ids' => $blockedSlotIds,
            'slots' => $slots,
        ];

        if ($targetBarber->role === 'salon') {
            $responseData['salon_id'] = $targetBarber->id;
            $responseData['salon_name'] = $targetBarber->name;
        } else {
            $responseData['barber_id'] = $targetBarber->id;
            $responseData['barber_name'] = $targetBarber->name;
        }

        return $this->success($responseData);

    } catch (\Exception $e) {
        return $this->error([],$e->getMessage());
    }
}


public function unblockSlot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slot_id' => 'required|array',
            'slot_id.*' => 'required|exists:schedule_time_manages,id',
            'block_date' => 'required|date',
            'barber_id' => 'nullable|exists:users,id',
            'salon_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            foreach ($request->slot_id as $slotId) {
                SlotBlockBarber::where('slot_id', $slotId)
                    ->where('block_date', $request->block_date)
                    ->where(function ($q) use ($request) {
                        if ($request->barber_id) {
                            $q->where('barber_id', $request->barber_id);
                        }
                        if ($request->salon_id) {
                            $q->orWhere('salon_id', $request->salon_id);
                        }
                    })
                    ->delete();
            }

            return $this->success([
                'message' => 'Slot unblocked successfully'
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }






    public function deleteSchedule($id)
    {
        try {
            $schedule = ScheduleDay::where('provider_id', Auth::id())->findOrFail($id);

            ScheduleTimeManage::where('schedule_day_id', $schedule->id)->delete();
            $schedule->delete();

            return $this->success([
                'message' => 'Schedule deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
