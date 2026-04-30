<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Traits\BookingDispatchTrait;
use Illuminate\Console\Command;

class BookingCheckAsapTimeout extends Command
{
    use BookingDispatchTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:booking-check-asap-timeout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for ASAP bookings that have not been accepted within 3 minutes and assign to next barber.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeoutLimit = now()->subMinutes(3);

        $bookings = Booking::where('booking_type', 'as_soon_possible')
            ->where('status', 'search_barber')
            ->where('last_assigned_at', '<=', $timeoutLimit)
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No timed out ASAP bookings found.');
            return;
        }

        foreach ($bookings as $booking) {
            $this->info("Processing timeout for Booking ID: {$booking->id}");

            $nextBarber = $this->reassignAsapBooking($booking);

            if ($nextBarber) {
                $this->info("Reassigned to next barber ID: {$nextBarber->id}");
            } else {
                $this->warn("No more available barbers for Booking ID: {$booking->id}. Booking cancelled.");
            }
        }
    }
}
