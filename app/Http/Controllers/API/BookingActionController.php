<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingAssignHistory;
use App\Models\Payment;
use App\Traits\ApiResponse;
use App\Traits\BookingDispatchTrait;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class BookingActionController extends Controller
{
    use ApiResponse, BookingDispatchTrait;

    /**
     * Barber accepts the ASAP booking.
     */
    public function acceptAsapBooking(Request $request, $id)
    {
        $barber = Auth::guard('api')->user();

        $booking = Booking::with('items.service')->where('id', $id)
            ->where('barber_id', $barber->id)
            ->whereIn('status', ['search_barber', 'pending'])
            ->first();

        if (!$booking) {
            return $this->notFound([], 'Booking not found, expired, or already processed.');
        }

        if ($booking->booking_type !== 'as_soon_possible' || $booking->status === 'pending') {
            $booking->status = 'accepted';
            $booking->save();

            BookingAssignHistory::where('booking_id', $booking->id)
                ->where('barber_id', $barber->id)
                ->where('status', 'pending')
                ->update(['status' => 'accepted']);

            FcmService::sendNotification(
                $booking->customer_id,
                'Booking Accepted',
                'Your booking has been accepted by ' . $barber->name,
                [
                    'booking_id' => (string) $booking->id,
                    'status' => 'accepted',
                ]
            );

            return $this->success($booking, 'Booking accepted successfully.');
        }

        // If online payment, set to waiting_for_payment and send link
        if ($booking->payment_type == 'online') {
            $booking->status = 'waiting_for_payment';
            $booking->last_assigned_at = now(); // Reuse this for payment timeout (5 min)
            $booking->save();

            // Create or Update Payment Record
            Payment::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'customer_id' => $booking->customer_id,
                    'barber_id' => $barber->id,
                    'amount' => $booking->total_price,
                    'admin_commission' => $booking->admin_commission,
                    'provider_earnings' => $booking->provider_earnings,
                    'commission_rate' => $booking->commission_rate,
                    'payment_type' => $booking->payment_type,
                    'booking_type' => $booking->booking_type,
                    'payment_status' => 'pending',
                ]
            );

            // Create Stripe Session
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $baseUrl = config('app.url');

            $lineItems = [];
            foreach ($booking->items as $item) {
                $serviceName = $item->service ? $item->service->service_name : 'Unknown Service';
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $serviceName . ' (ID: ' . $item->service_id . ')',
                        ],
                        'unit_amount' => intval(round((float) $item->price * 100)),
                    ],
                    'quantity' => (int) $item->quantity,
                ];
            }

            $stripeSession = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $baseUrl . '/payment/success?session_id={CHECKOUT_SESSION_ID}&booking_id=' . $booking->id,
                'cancel_url' => $baseUrl . '/payment/cancel',
                'client_reference_id' => $booking->id,
            ]);

            // Update status to waiting for payment
            $booking->status = 'waiting_for_payment';
            $booking->last_assigned_at = now(); // Reset timer for payment
            $booking->save();

            // Store session ID in payment record
            Payment::where('booking_id', $booking->id)->update([
                'transaction_id' => $stripeSession->id,
            ]);

            // Update history
            BookingAssignHistory::where('booking_id', $booking->id)
                ->where('barber_id', $barber->id)
                ->where('status', 'pending')
                ->update(['status' => 'accepted']);

            // Notify customer to pay
            FcmService::sendNotification(
                $booking->customer_id,
                'Barber Found! Please Pay',
                'Barber ' . $barber->name . ' accepted your request. Please pay within 5 minutes to confirm.',
                [
                    'booking_id' => $booking->id,
                    'payment_url' => $stripeSession->url,
                    'status' => 'waiting_for_payment'
                ]
            );

            return $this->success([
                'booking' => $booking,
                'payment_url' => $stripeSession->url
            ], 'Booking accepted. Waiting for customer payment.');
        }

        // For non-online payments (COD, On-site), proceed directly
        $booking->status = 'accepted';
        $booking->save();

        // Update history
        BookingAssignHistory::where('booking_id', $booking->id)
            ->where('barber_id', $barber->id)
            ->where('status', 'pending')
            ->update(['status' => 'accepted']);

        // Notify customer
        FcmService::sendNotification(
            $booking->customer_id,
            'Booking Accepted',
            'Your ASAP booking has been accepted by ' . $barber->name,
            ['booking_id' => $booking->id]
        );

        return $this->success($booking, 'Booking accepted successfully.');
    }

    /**
     * Barber rejects the ASAP booking.
     */
    public function rejectAsapBooking(Request $request, $id)
    {
        $barber = Auth::guard('api')->user();

        $booking = Booking::where('id', $id)
            ->where('barber_id', $barber->id)
            ->whereIn('status', ['search_barber', 'pending'])
            ->first();

        if (!$booking) {
            return $this->notFound([], 'Booking not found, expired, or already processed.');
        }

        if ($booking->booking_type !== 'as_soon_possible' || $booking->status === 'pending') {
            $booking->status = 'cancelled';
            $booking->save();

            BookingAssignHistory::where('booking_id', $booking->id)
                ->where('barber_id', $barber->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            FcmService::sendNotification(
                $booking->customer_id,
                'Booking Rejected',
                'Your booking has been rejected by ' . $barber->name,
                [
                    'booking_id' => (string) $booking->id,
                    'status' => 'cancelled',
                ]
            );

            return $this->success($booking, 'Booking rejected successfully.');
        }

        // Update history to rejected
        BookingAssignHistory::where('booking_id', $booking->id)
            ->where('barber_id', $barber->id)
            ->where('status', 'pending')
            ->update(['status' => 'rejected']);

        // Immediately try to find the next barber
        $nextBarber = $this->reassignAsapBooking($booking);

        if ($nextBarber) {
            return $this->success(['next_barber_id' => $nextBarber->id], 'Booking rejected. Assigned to next available barber.');
        } else {
            return $this->success([], 'Booking rejected. No more barbers available.');
        }
    }
}
