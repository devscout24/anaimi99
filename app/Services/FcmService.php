<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send FCM push notification to a user.
     */
    public static function sendNotification($userId, $title, $body, $data = [])
    {
        $user = User::find($userId);
        if (!$user || !$user->fcm_token) {
            return false;
        }

        $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
        $serverKey = env('FCM_SERVER_KEY');

        if (!$serverKey) {
            Log::error('FCM Server Key not found in .env');
            return false;
        }

        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
        ];

        $extraData = array_merge($data, [
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ]);

        $fcmFields = [
            'to' => $user->fcm_token,
            'priority' => 'high',
            'notification' => $notification,
            'data' => $extraData,
        ];

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmFields));
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
