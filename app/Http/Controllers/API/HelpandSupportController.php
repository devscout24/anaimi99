<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HelpAndSupport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpandSupportController extends Controller
{
    public function submitHelpSupport(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'email' => 'nullable|email|max:255',
        ]);

        try {
            $user = Auth::guard('api')->user();

            $helpSupport = new HelpAndSupport();
            $helpSupport->user_id = $user->id;
            $helpSupport->subject = $request->subject;
            $helpSupport->message = $request->message;
            $helpSupport->email = $request->email;
            $helpSupport->save();

            return response()->json(['success' => true, 'message' => 'Your message has been submitted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred while submitting your message. Please try again later.'], 500);
        }
    }
}
