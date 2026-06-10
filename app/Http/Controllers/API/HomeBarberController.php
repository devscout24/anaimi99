<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeBarberController extends Controller
{
    use ApiResponse;

    public function homeBarberSearchList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'start_time' => 'required|date_format:H:i',
            'consume_time' => 'required|numeric|min:1',
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {

            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $date = $request->date;
            $consumeTime = (int) $request->consume_time;

            $startTime = Carbon::createFromFormat('H:i', $request->start_time);
            $endTime = (clone $startTime)->addMinutes($consumeTime);

            $formattedStartTime = $startTime->format('H:i:s');
            $formattedEndTime = $endTime->format('H:i:s');

            $radius = 50;

            /**
             * ==============================
             * 1. GET ALL BARBERS FIRST
             * ==============================
             */
            $barbers = User::selectRaw("
                    users.*,
                    (
                        6371 * ACOS(
                            COS(RADIANS(?)) *
                            COS(RADIANS(latitude)) *
                            COS(RADIANS(longitude) - RADIANS(?)) +
                            SIN(RADIANS(?)) *
                            SIN(RADIANS(latitude))
                        )
                    ) AS distance
                ", [$latitude, $longitude, $latitude])
                ->where('role', 'home_barbar')
                ->where('block_status', 'unblock')
                ->where('availability', 1)
                ->where('status', 'approved')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->havingRaw("distance <= ?", [$radius])
                ->orderBy('distance', 'asc')
                ->get();

            $availableBarbers = [];

            /**
             * ==============================
             * 2. CHECK EACH BARBER SLOT CAPACITY
             * ==============================
             */
            foreach ($barbers as $barber) {

                // 1. booked slots total minutes
                $bookedMinutes = DB::table('booking_time_manges')
                    ->join('schedule_time_manages', 'booking_time_manges.schedule_id', '=', 'schedule_time_manages.id')
                    ->where('booking_time_manges.barber_id', $barber->id)
                    ->where('booking_time_manges.date', $date)
                    ->where('booking_time_manges.status', 'active')
                    ->sum(DB::raw("
                        TIMESTAMPDIFF(MINUTE,
                            schedule_time_manages.scheduled_start_time,
                            schedule_time_manages.scheduled_end_time
                        )
                    "));

                // 2. blocked slots total minutes
                $blockedMinutes = DB::table('slot_block_barbers')
                    ->join('schedule_time_manages', 'slot_block_barbers.slot_id', '=', 'schedule_time_manages.id')
                    ->where('slot_block_barbers.barber_id', $barber->id)
                    ->where('slot_block_barbers.block_date', $date)
                    ->where('slot_block_barbers.status', 'active')
                    ->sum(DB::raw("
                        TIMESTAMPDIFF(MINUTE,
                            schedule_time_manages.scheduled_start_time,
                            schedule_time_manages.scheduled_end_time
                        )
                    "));

                // 3. total busy time
                $busyMinutes = $bookedMinutes + $blockedMinutes;

                // 4. total working slots time (assume full day schedule)
                $totalMinutes = DB::table('schedule_time_manages')
                    ->where('provider_id', $barber->id)
                    ->where('status', 'active')
                    ->sum(DB::raw("
                        TIMESTAMPDIFF(MINUTE, scheduled_start_time, scheduled_end_time)
                    "));

                // 5. free time
                $freeMinutes = $totalMinutes - $busyMinutes;

                // 6. MAIN RULE:
                // barber only show if free time >= consume time
                if ($freeMinutes >= $consumeTime) {
                    $barber->free_minutes = $freeMinutes;
                      $barber->profile_image = $barber->profile_image
                        ? asset($barber->profile_image)
                        : null;

                    $availableBarbers[] = $barber;
                }
            }

            return $this->success($availableBarbers, 'Available home barbers fetched successfully.');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}