<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Api\Traits\ApiResponse;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('stripe-signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe Webhook Error: Invalid Payload');
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe Webhook Error: Invalid Signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;

                $bookingId = $session->client_reference_id;

                if ($bookingId) {
                    $booking = Booking::find($bookingId);
                    if ($booking) {
                        $booking->payment_status = 'paid';
                        $booking->status = 'confirmed';
                        $booking->save();

                        // Update payments table if record exists
                        try {
                            $transactionId = $session->payment_intent ?? $session->payment_intent_id ?? null;
                            $payment = Payment::where('booking_id', $bookingId)->latest()->first();
                            if ($payment) {
                                if ($transactionId) {
                                    $payment->transaction_id = $transactionId;
                                }
                                $payment->payment_status = 'paid';
                                $payment->paid_at = now();
                                $payment->save();
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to update Payment record for booking ' . $bookingId . ': ' . $e->getMessage());
                        }

                        Log::info("Booking {$bookingId} marked as paid successfully.");
                    }
                }
                break;
            default:
                Log::info('Received unhandled event type ' . $event->type);
        }

        return response()->json(['status' => 'success'], 200);
    }
}

