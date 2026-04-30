<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TempPasswordMail;
use App\Models\User;
use App\Traits\ApiResponse;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SalonBarberAddController extends Controller
{

 use ApiResponse;
    public function addSalonBarber(Request $request)
    {

       $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->first());
        }




       try{

      $user = Auth::guard('api')->user();
      $salonId = Auth::guard('api')->id();

      if (!$user || !$salonId) {
          return $this->validationError('Unauthorized. Please login as salon first.');
      }

       if(!$user || $user->role !== 'salon') {
            return $this->validationError('Unauthorized. Only salon users can add barbers.');
        }

        $password = Str::password(12);
        $barber = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
          'salon_id'=> $salonId,
            'password' => bcrypt($password),
            'role' => 'salon_barbar',
        ]);

        return $this->success('Barber added to salon successfully', ['barber_id' => $barber->id]);
       }
       catch(\Exception $e){
        return $this->error($e->getMessage());
       }
       catch(\Exception $e){
        return $this->error($e->getMessage());
       }
    }

    public function SalonBarberlist()
    {
        try{
            if  (!Auth::check() || Auth::user()->role !== 'salon') {
                return $this->validationError('Unauthorized. Only salon users can access this resource.');
            }
            $barbers = User::where('salon_id', Auth::id())->where('role', 'salon_barbar')->get();

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


        // $todaysacceptBooking

        // $todayscompletebooking



        $data = [
            'id' => $barber->id,
            'name' => $barber->name,
            'email' => $barber->email,
            'phone' => $barber->phone,
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
