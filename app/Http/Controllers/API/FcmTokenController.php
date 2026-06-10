<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;


use App\Models\FcmToken;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FcmTokenController extends Controller
{
    use ApiResponse;

    /**
     * Store or update an FCM token for the authenticated user.
     * This allows a single user to have multiple device tokens.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $user = Auth::guard('api')->user();

            // Update the legacy fcm_token column in the users table for backward compatibility
            $user->fcm_token = $request->fcm_token;
            $user->save();

            // Store in the dedicated fcm_tokens table
            // We use updateOrCreate with user_id and token to avoid duplicates for the same user-token pair
            FcmToken::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'token'   => $request->fcm_token,
                ],
                [
                    'device_id' => $request->device_id,
                ]
            );

            return $this->success(null, 'FCM token saved successfully.');
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * Remove an FCM token (e.g., on logout from a specific device).
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $user = Auth::guard('api')->user();

            FcmToken::where('user_id', $user->id)
                ->where('token', $request->fcm_token)
                ->delete();

            return $this->success(null, 'FCM token removed successfully.');
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }
}
