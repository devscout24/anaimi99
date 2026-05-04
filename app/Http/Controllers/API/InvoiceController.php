<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    use ApiResponse;

    /**
     * List all invoices for the authenticated customer/barber.
     */
    public function index()
    {
        try {
            $user = Auth::guard('api')->user();
            $bookings = Booking::where('barber_id', $user->id)
                ->where('status', 'completed')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'invoice_no' => '#' . ($booking->invoice_no ?? 'INV-' . (1000 + $booking->id)),
                        'date' => $booking->created_at->format('M d, Y'),
                        'total_price' => (float)$booking->total_price,
                        'currency' => '€',
                        'payment_status' => ucfirst($booking->payment_status),
                    ];
                });

            return $this->success($bookings, 'Invoices fetched successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get detailed information for a specific invoice.
     */
    public function details($id)
    {
        try {
            $booking = Booking::with(['customer', 'barber', 'items.service', 'slots.scheduleTime'])
                ->where('id', $id)
                ->first();

            if (!$booking) {
                return $this->error('Invoice not found');
            }

            // Calculation (Using 20% Platform Fee as shown in the image)
            $totalPrice = (float)$booking->total_price;
            $platformFee = $totalPrice * 0.20;
            $amountReceived = $totalPrice - $platformFee;

            $serviceNames = $booking->items->map(function ($item) {
                return $item->service->service_name ?? 'Unknown';
            })->implode(' + ');

            $startTimeSlot = $booking->slots->sortBy(function($slot) {
                return optional($slot->scheduleTime)->scheduled_start_time;
            })->first();
            
            $formattedTime = 'N/A';
            $duration = 0;
            if ($startTimeSlot && $startTimeSlot->scheduleTime) {
                $start = Carbon::parse($startTimeSlot->scheduleTime->scheduled_start_time);
                $end = Carbon::parse($startTimeSlot->scheduleTime->scheduled_end_time);
                $formattedTime = $start->format('g:i A') . ' - ' . $end->format('g:i A');
                $duration = $start->diffInMinutes($end);
            }

            $data = [
                'invoice_no' => '#' . ($booking->invoice_no ?? 'INV-' . (1000 + $booking->id)),
                'status' => ucfirst($booking->payment_status),
                'date' => $booking->created_at->format('F d, Y'),
                'customer' => [
                    'name' => $booking->customer->name ?? 'Unknown',
                    'phone' => $booking->customer->phone ?? 'N/A',
                    'avatar' => $booking->customer->profile_image ? asset($booking->customer->profile_image) : null,
                ],
                'service_details' => [
                    'name' => $serviceNames,
                    'price' => $totalPrice,
                    'location' => '15 Rivoli Street, 75004 Paris', // Placeholder address
                    'type' => ucfirst(str_replace('_', ' ', $booking->booking_type)),
                    'time' => $formattedTime,
                    'duration' => $duration . ' minutes',
                ],
                'summary' => [
                    'service_amount' => $totalPrice,
                    'platform_fee' => (float)number_format($platformFee, 2),
                    'platform_fee_percentage' => '20%',
                    'amount_received' => (float)number_format($amountReceived, 2),
                    'currency' => '€',
                ]
            ];

            return $this->success($data, 'Invoice details fetched successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Return a view for downloading/printing the invoice.
     */
    public function download($id)
    {
        try {
            $booking = Booking::with(['customer', 'barber', 'items.service', 'slots.scheduleTime'])
                ->where('id', $id)
                ->firstOrFail();

            return view('invoices.template', compact('booking'));
        } catch (\Exception $e) {
            return abort(404, 'Invoice not found');
        }
    }
}
