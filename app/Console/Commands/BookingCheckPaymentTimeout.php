<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingAssignHistory;
use App\Models\Payment;
use App\Services\FcmService;
use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class BookingCheckPaymentTimeout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:booking-check-payment-timeout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for ASAP bookings in waiting_for_payment status that timed out (5 mins).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 5 minutes timeout for payment
        $timeoutLimit = now()->subMinutes(5);

        $bookings = Booking::where('booking_type', 'as_soon_possible')
            ->where('status', 'waiting_for_payment')
            ->where('last_assigned_at', '<=', $timeoutLimit)
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No timed out payments found.');
            return;
        }

        foreach ($bookings as $booking) {
            $this->info("Processing payment timeout for Booking ID: {$booking->id}");

            // Cancel the booking
            $booking->status = 'cancelled';
            $booking->save();

            // Delete Slots to free them up
            \App\Models\BookingTimeMange::where('booking_id', $booking->id)->delete();

            // Notify Customer
            FcmService::sendNotification(
                $booking->customer_id,
                'Booking Cancelled',
                'Your ASAP booking was cancelled due to payment timeout.',
                ['booking_id' => $booking->id]
            );

            // Notify Barber
            if ($booking->barber_id) {
                FcmService::sendNotification(
                    $booking->barber_id,
                    'Booking Cancelled',
                    'The customer did not pay in time. You are now free for other bookings.',
                    ['booking_id' => $booking->id]
                );
            }

            // Update assignment history
            BookingAssignHistory::where('booking_id', $booking->id)
                ->where('barber_id', $booking->barber_id)
                ->where('status', 'accepted')
                ->update(['status' => 'payment_timeout']);

            $this->info("Booking {$booking->id} cancelled successfully.");
        }
    }
}
