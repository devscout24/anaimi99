<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    use ApiResponse;
          public function userProfileGet(){
                try {

                    $user = Auth::guard('api')->user()
                        ->load(['provider_profiles', 'imageGallery']);

                    $data = [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone'=>$user->phone,
                            'role'=>$user->role,
                            'postal_code'=>$user->provider_profiles->postal_code?? null,
                            'profile_image' => $user->profile_image
                                ? asset($user->profile_image)
                                : null,

                            'profile' => $user->provider_profiles?? null,

                            'gallery_images' => collect($user->imageGallery ?? [])
                                ->map(function ($image) {
                                    return [
                                        'id' => $image->id,
                                        'image' => asset($image->image),
                                    ];
                                })->values(),
                        ]
                    ];

        return $this->success($data);

    } catch (\Exception $e) {
        return $this->error($e->getMessage());
    }
}


 public function update(){
    try{
        $user = Auth::guard('api')->user();

        $user->name = request('name', $user->name)?? $user->name;
        $user->phone = request('phone', $user->phone)?? $user->phone;
        $user->save();

        if($user->role=='home_barbar' || $user->role=='salon_barbar' || $user->role=='salon'){
            $profile = $user->provider_profiles;
            if($profile){
                $profile->business_name = request('business_name', $profile->business_name)?? $profile->business_name;
                $profile->representative_name = request('representative_name', $profile->representative_name)?? $profile->representative_name;
                $profile->since = request('since', $profile->since)?? $profile->since;
                $profile->about = request('about', $profile->about)?? $profile->about;
                $profile->street_number = request('street_number', $profile->street_number)?? $profile->street_number;
                $profile->vat_number = request('vat_number', $profile->vat_number)?? $profile->vat_number;
                $profile->experience = request('experience', $profile->experience)?? $profile->experience;
                $profile->postal_code = request('postal_code', $profile->postal_code)?? $profile->postal_code;
                $profile->salon_address = request('salon_address', $profile->salon_address)?? $profile->salon_address;
                $available = request('available');
                if (!is_null($available)) {
                    // Convert the value to boolean
                    if (is_string($available)) {
                        // Handle string representations of boolean
                        if (strtolower($available) === 'true') {
                            $available = true;
                        } elseif (strtolower($available) === 'false') {
                            $available = false;
                        } else {
                            return response()->json(['error' => 'Invalid value for available. Use true or false.'], 400);
                        }
                    } else {
                        // For non-string values, cast to boolean
                        $available = (bool)$available;
                    }
                    // Update the profile's availability
                    $profile->available = $available;
                }

                $profile->save();
            }
        }



        if (request()->hasFile('profile_image')) {
            $image = request()->file('profile_image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/profile_images'), $imageName);
            $user->profile_image = 'uploads/profile_images/' . $imageName;
            $user->save();
        }


        if (request()->hasFile('gallery_images')) {
            $galleryImages = request()->file('gallery_images');
            foreach ($galleryImages as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/gallery_images'), $imageName);
                $user->imageGallery()->create([
                    'image' => 'uploads/gallery_images/' . $imageName,
                ]);
            }
        }

        return $this->success($user, 'Profile updated successfully.');
    }
   catch (\Exception $e) {
        return $this->error($e->getMessage());
    }
 }


 public function userGaleryImageDelete($id){
    try {
        $imageId = $id;
        $user = Auth::guard('api')->user();
        $image = $user->imageGallery()->where('id', $imageId)->first();

        if (!$image) {
            return response()->json(['error' => 'Image not found.'], 404);
        }

        // Delete the image file from the server
        if (file_exists(public_path($image->image))) {
            unlink(public_path($image->image));
        }

        // Delete the image record from the database
        $image->delete();

          return $this->success([], 'Image deleted successfully.');
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
 }

public function availableControll(Request $request)
{
    try {

        $user = Auth::guard('api')->user();
        $salon_barber=User::where('id',$request->salon_barber_id)->whereIn('role',['salon_barber'])->first();
        $profile = $user->provider_profiles;


        if (!$profile) {
            return response()->json(['error' => 'Profile not found.'], 404);
        }

        $available = request('available');

        if (is_null($available)) {
            return response()->json(['error' => 'Available parameter is required.'], 400);
        }

        // 🔥 Normalize value (BEST SAFE WAY)
        if (is_string($available)) {
            $available = strtolower($available);

            if (in_array($available, ['true', '1', 'yes', 'on'])) {
                $available = true;
            } elseif (in_array($available, ['false', '0', 'no', 'off'])) {
                $available = false;
            } else {
                return response()->json([
                    'error' => 'Invalid value for available. Use true/false or 1/0.'
                ], 400);
            }
        } else {
            $available = (bool) $available;
        }

        // 🔥 update both (user + profile)
         if($salon_barber){
            $salon_barber->availability = $available;
            $salon_barber->save();

            $salon_barber_profile = $salon_barber->provider_profiles;
            if ($salon_barber_profile) {
                $salon_barber_profile->available = $available;
                $salon_barber_profile->save();
            }
        }
        else{


          $user->availability = $available;
        $user->save();

        $profile->available = $available;
        $profile->save();
        }


        return $this->success([
            'availability' => $available
        ], 'Availability updated successfully.');

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}



public function updateLocation()
{
    try {
        $user = Auth::guard('api')->user();
        $profile = $user->provider_profiles;

        if (!$profile) {
            return response()->json(['error' => 'Profile not found.'], 404);
        }

        $latitude = request('latitude');
        $longitude = request('longitude');


        $user->latitude = $latitude;
        $user->longitude = $longitude;
        $user->save();


        return $this->success([
            'latitude' => $latitude,
            'longitude' => $longitude
        ], 'Location updated successfully.');

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }

}


public function userBusinessDetailsGet()
{
    try {
        $user = Auth::guard('api')->user()
            ->load(['provider_profiles']);

        if (!$user->provider_profiles) {
            return response()->json(['error' => 'Profile not found.'], 404);
        }

        $data = [
            'business_name' => $user->provider_profiles->business_name,
            'strret_number' => $user->provider_profiles->street_number,
            'vat_number' => $user->provider_profiles->vat_number,
        ];

        return $this->success($data);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


public function userBusinessDetailsUpdate()
{
    try {
        $user = Auth::guard('api')->user();
        $profile = $user->provider_profiles;

        if (!$profile) {
            return response()->json(['error' => 'Profile not found.'], 404);
        }

        $profile->business_name = request('business_name', $profile->business_name) ?? $profile->business_name;
        $profile->street_number = request('street_number', $profile->street_number) ?? $profile->street_number;
        $profile->vat_number = request('vat_number', $profile->vat_number) ?? $profile->vat_number;
        $profile->save();

        return $this->success($profile, 'Business details updated successfully.');

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}
