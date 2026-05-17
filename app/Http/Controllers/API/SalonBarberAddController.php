<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\TempPasswordMail;
use App\Models\Booking;
use App\Models\User;
use App\Traits\ApiResponse;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
class SalonBarberAddController extends Controller
{

 use ApiResponse;
 public function addSalonBarber(Request $request)
{
    $validator = Validator::make($request->all(), [

        'salon_barber_id' => 'nullable|exists:users,id',

        'name' => 'sometimes|string|max:255',
        'email' => 'nullable|email',
        'phone' => 'sometimes|string|max:20',
        'availability' => 'nullable',

        'profile_image' => 'nullable|image',

    ]);

    if ($validator->fails()) {

        return $this->validationError($validator->errors()->first());
    }

    try {

        $user = Auth::guard('api')->user();
        $salonId = Auth::guard('api')->id();

        if (!$user || !$salonId) {

            return $this->validationError('Unauthorized. Please login as salon first.');
        }

        if ($user->role !== 'salon') {

            return $this->validationError('Unauthorized. Only salon users can add barbers.');
        }

        $barber = null;

        /*
        |--------------------------------------------------------------------------
        | UPDATE MODE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('salon_barber_id')) {

            $barber = User::where('id', $request->salon_barber_id)
                ->where('salon_id', $salonId)
                ->where('role', 'salon_barbar')
                ->first();

            if (!$barber) {

                return $this->validationError('Barber not found.');
            }

            // Email unique check
            if ($request->filled('email')) {

                $emailExists = User::where('email', $request->email)
                    ->where('id', '!=', $barber->id)
                    ->exists();

                if ($emailExists) {

                    return $this->validationError('Email already exists.');
                }

                $barber->email = $request->email;
            }

        } else {

            /*
            |--------------------------------------------------------------------------
            | CREATE MODE
            |--------------------------------------------------------------------------
            */

            if (!$request->filled('name')) {

                return $this->validationError('Name is required.');
            }

            if (!$request->filled('phone')) {

                return $this->validationError('Phone is required.');
            }

            if ($request->filled('email')) {

                $emailExists = User::where('email', $request->email)->exists();

                if ($emailExists) {

                    return $this->validationError('Email already exists.');
                }
            }

            $barber = new User();

            $barber->password = bcrypt(Str::password(12));
            $barber->role = 'salon_barbar';
            $barber->salon_id = $salonId;

            if ($request->filled('email')) {

                $barber->email = $request->email;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Upload Image
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('profile_image')) {

            $image = $request->file('profile_image');

            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            $path = 'uploads/profile_photo/';

            $image->move(public_path($path), $imageName);

            $barber->profile_image = $path . $imageName;
        }

        /*
        |--------------------------------------------------------------------------
        | Update Only Sent Data
        |--------------------------------------------------------------------------
        */

        if ($request->filled('name')) {

            $barber->name = $request->name;
        }

        if ($request->filled('phone')) {

            $barber->phone = $request->phone;
        }

        if ($request->has('availability')) {

            $barber->availability = $request->availability;
        }

        $barber->save();

        return $this->success(

            [
                'barber_id' => $barber->id
            ],

            $request->filled('salon_barber_id')
                ? 'Barber updated successfully'
                : 'Barber added successfully'
        );

    } catch (\Exception $e) {

        return $this->error($e->getMessage());
    }
}
    public function SalonBarberlist()
    {
        try{
            if  (!Auth::check() || Auth::user()->role !== 'salon') {
                return $this->validationError('Unauthorized. Only salon users can access this resource.');
            }
            $barbers = User::where('salon_id', Auth::id())->where('role', 'salon_barbar')->get()->map(function ($barber) {
                return [
                    'id' => $barber->id,
                    'name' => $barber->name,
                    'email' => $barber->email,
                    'phone' => $barber->phone,
                    'profile_image' => $barber->profile_image ? asset($barber->profile_image) : null,
                    'availability'=>$barber->availability,
                ];
            })->values();

            return $this->success($barbers, 'Barber list retrieved successfully');
        }
        catch(\Exception $e){
            return $this->error($e->getMessage());
        }
    }



   public function deleteSalonBarber($id)
    {
        try {
                if  (!Auth::check() || Auth::user()->role !== 'salon') {
                    return $this->validationError('Unauthorized. Only salon users can access this resource.');
                }



                if(Auth::id() == $id){
                    return $this->validationError('You cannot delete yourself as a barber.');
                }
                $barber = User::where('id', $id)->where('salon_id', Auth::id())->where('role', 'salon_barbar')->first();

               if(Auth::id()!=$barber->salon_id){
                    return $this->validationError('You cannot delete yourself as a barber.');
                }
            if (!$barber) {
                return $this->validationError('Barber not found or does not belong to your salon.');
            }

            $barber->delete();

            return $this->success('Barber removed from salon successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }


public function getBarberDetails($id)
{
    try {

        if (!Auth::check() || Auth::user()->role !== 'salon') {
            return $this->validationError(
                'Unauthorized. Only salon users can access this resource.'
            );
        }

        $barber = User::where('id', $id)
            ->where('salon_id', Auth::id())
            ->where('role', 'salon_barbar')
            ->first();

        if (!$barber) {
            return $this->validationError(
                'Barber not found or does not belong to your salon.'
            );
        }


         $todaysacceptBooking=Booking::query()->where('salon_id', Auth::id())->where('barber_id', $id)->where('status', 'accepted')->whereDate('created_at', Carbon::today())->count();

         $todayscompletebooking=Booking::query()->where('salon_id', Auth::id())->where('barber_id', $id)->where('status', 'completed')->whereDate('created_at', Carbon::today())->count();



        $data = [
            'id' => $barber->id,
            'name' => $barber->name,
            'email' => $barber->email,
            'phone' => $barber->phone,
            'todays_accept_booking'=>$todaysacceptBooking,
            'todays_complete_booking'=>$todayscompletebooking,
            'salon_id' => $barber->salon_id,
            'profile_image' => $barber->profile_image
                ? asset($barber->profile_image)
                : null,
                'availability'=>$barber->availability,

        ];

        return $this->success(
            $data,
            'Barber details retrieved successfully'
        );

    } catch (\Exception $e) {
        return $this->error($e->getMessage());
    }
}


    /**
     * Send a temporary password email to a salon barber.
     * Route: GET send/temporary/password/email/{id}
     */
    public function SendTempPassBarbar($id)
    {
        try {

            if (!Auth::check() || Auth::user()->role !== 'salon') {
                return $this->validationError('Unauthorized. Only salon users can access this resource.');
            }

            // Find the barber that belongs to this salon
            $barber = User::where('id', $id)
                ->where('salon_id', Auth::id())
                ->where('role', 'salon_barbar')
                ->first();

            if (!$barber) {
                return $this->validationError('Barber not found or does not belong to your salon.');
            }

            // Generate a new temporary password
            $tempPassword = Str::password(12);

            // Save the hashed password
            $barber->password = bcrypt($tempPassword);
            $barber->salon_barbar_status=true;
            $barber->save();

            // Send the temporary password via email
            Mail::to($barber->email)->send(new TempPasswordMail($barber, $tempPassword));

            return $this->success(
                ['barber_id' => $barber->id, 'email' => $barber->email],
                'Temporary password sent to barber email successfully.'
            );

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }




}