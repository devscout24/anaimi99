<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingTimeMange;
use App\Models\Payment;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;

class PaymentStatusController extends Controller
{
    /**
     * Show payment success page.
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        $bookingId = $request->get('booking_id');

        if ($sessionId && $bookingId) {
            try {
                Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
                $session = Session::retrieve($sessionId);

                if ($session->payment_status === 'paid') {
                    $booking = Booking::find($bookingId);
                    if ($booking && $booking->status === 'waiting_for_payment') {
                        $booking->payment_status = 'paid';
                        $booking->status = $booking->booking_type === 'as_soon_possible' ? 'confirmed' : 'pending';
                        $booking->save();

                        BookingTimeMange::where('booking_id', $booking->id)
                            ->where('status', 'pending')
                            ->update(['status' => 'active']);

                        $payment = Payment::where('booking_id', $bookingId)->first();
                        if ($payment) {
                            $payment->payment_status = 'paid';
                            $payment->transaction_id = $session->payment_intent;
                            $payment->paid_at = now();
                            $payment->save();
                        }

                        Log::info("Booking {$bookingId} updated via success page verify.");
                    }
                }
            } catch (\Exception $e) {
                Log::error("Stripe Session Retrieve Error: " . $e->getMessage());
            }
        }

        return view('payment.success', compact('bookingId'));
    }

    /**
     * Show payment cancel page.
     */
    public function cancel(Request $request)
    {
        return view('payment.cancel');
    }
}
