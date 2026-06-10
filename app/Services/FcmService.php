<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    /**
     * Send FCM push notification to a user.
     */
    public static function sendNotification($userId, $title, $body, $data = [])
    {
        $user = User::with('fcmTokens')->find($userId);
        if (!$user) {
            return false;
        }

        // Get all tokens from the new table
        $tokens = $user->fcmTokens->pluck('token')->toArray();

        // Fallback to old fcm_token column if no tokens in new table
        if (empty($tokens) && $user->fcm_token) {
            $tokens[] = $user->fcm_token;
        }

        if (empty($tokens)) {
            return false;
        }

        try {
            $messaging = app('firebase.messaging');

            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data);

            $report = $messaging->sendMulticast($message, $tokens);

            if ($report->failures()->count() > 0) {
                foreach ($report->failures()->getItems() as $failure) {
                    Log::warning('FCM individual failure: ' . $failure->error()->getMessage());
                }
            }

            Log::info('FCM Send summary: Successful: ' . $report->successes()->count() . ', Failed: ' . $report->failures()->count());

            return true;
        } catch (\Exception $e) {
            Log::error('Firebase Messaging Error: ' . $e->getMessage());
            return false;
        }
    }
}
