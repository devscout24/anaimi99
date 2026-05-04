<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CommissionSetting;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalonInvoiceController extends Controller
{
    use ApiResponse;

    /**
     * List all single booking invoices for the authenticated salon.
     */
    public function index()
    {
        try {
            $user = Auth::guard('api')->user();
            if (!$user || $user->role !== 'salon') {
                return $this->error('Unauthorized', 403);
            }

            $bookings = Booking::where('salon_id', $user->id)
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

            return $this->success($bookings, 'Salon invoices fetched successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get detailed information for a specific single booking invoice.
     */
    public function details($id)
    {
        try {
            $user = Auth::guard('api')->user();
            if (!$user || $user->role !== 'salon') {
                return $this->error('Unauthorized', 403);
            }

            $booking = Booking::with(['customer', 'items.service'])
                ->where('salon_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$booking) {
                return $this->error('Invoice not found');
            }

            $totalGenerated = (float)$booking->total_price;

            // Commission breakdown for this single booking
            $onlineCommission = ($booking->payment_type === 'online') ? (float)$booking->admin_commission : 0;
            $codCommission = (in_array($booking->payment_type, ['cod', 'onsite']) && $booking->booking_type !== 'custom') ? (float)$booking->admin_commission : 0;
            $customCommission = ($booking->booking_type === 'custom') ? (float)$booking->admin_commission : 0;

            $totalAdminCommission = $onlineCommission + $codCommission + $customCommission;
            $netAmountReceived = $totalGenerated - $totalAdminCommission;

            $serviceNames = $booking->items->map(fn($i) => $i->service->service_name ?? '')->implode(' + ');

            // Get Time and Duration
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

            // Get commission rate from Admin Panel settings
            $setting = CommissionSetting::where('type', 'salon')->first();
            $commissionRate = $setting ? (float)$setting->commission_rate : ($booking->commission_rate ?? 0);

            // Calculate admin commission based on current rate
            $adminCommission = round($totalGenerated * $commissionRate / 100, 2);
            $amountReceived = $totalGenerated - $adminCommission;

            $data = [
                'customer' => [
                    'name' => $booking->customer->name ?? 'Unknown',
                    'phone' => $booking->customer->phone ?? 'N/A',
                    'image' => $booking->customer->profile_image ? asset($booking->customer->profile_image) : null,
                ],
                'invoice_no' => '#' . ($booking->invoice_no ?? 'INV-' . (1000 + $booking->id)),
                'date' => $booking->created_at->format('F d, Y'),
                'status' => ucfirst($booking->payment_status),

                'service_card' => [
                    'items' => $booking->items->map(function($item) {
                        return [
                            'service_name' => ($item->service->service_name ?? 'Service') . ($item->quantity > 1 ? ' x ' . $item->quantity : ''),
                            'quantity' => $item->quantity,
                            'price' => number_format($item->total, 0) . ' €',
                        ];
                    }),
                    'location' => $booking->booking_type === 'home_barber' ? 'At home' : ($user->salon_address ?? 'At Salon'),
                    'type' => ucfirst(str_replace('_', ' ', $booking->booking_type)),
                    'time' => $formattedTime,
                    'duration' => $duration . ' minutes',
                ],

                'summary' => [
                    [
                        'label' => 'Service Amount',
                        'value' => number_format($totalGenerated, 0) . ' €',
                    ],
                    [
                        'label' => 'Platform Fee (' . $commissionRate . '%)',
                        'value' => '-' . number_format($adminCommission, 0) . ' €',
                    ],
                    [
                        'label' => 'Amount Received',
                        'value' => number_format($amountReceived, 0) . ' €',
                        'is_total' => true
                    ]
                ]
            ];

            return $this->success($data, 'Salon invoice details fetched successfully');
        }
        catch(Exception $e){
            return $this->error($e->getMessage());
        }
    }

    public function periodIndex()
    {
        try {
            $user = Auth::guard('api')->user();
            if (!$user || $user->role !== 'salon') {
                return $this->error('Unauthorized', 403);
            }

            $salonId = $user->id;
            $periods = [];
            $now = Carbon::now();

            for ($i = 0; $i < 12; $i++) {
                $isSecondHalf = $now->day > 15;
                $targetHalf = ($isSecondHalf) ? ($i % 2 == 0) : ($i % 2 != 0);
                $targetMonth = (clone $now)->subMonths(floor(($i + ($isSecondHalf ? 0 : 1)) / 2));
                
                if ($targetHalf) {
                    $start = (clone $targetMonth)->startOfMonth()->addDays(15);
                    $end = (clone $targetMonth)->endOfMonth();
                } else {
                    $start = (clone $targetMonth)->startOfMonth();
                    $end = (clone $targetMonth)->startOfMonth()->addDays(14);
                }

                if ($start->isFuture()) continue;

                $bookings = Booking::where('salon_id', $salonId)
                    ->where('status', 'completed')
                    ->whereBetween('booking_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->get();

                if ($bookings->count() > 0) {
                    $totalAmount = $bookings->sum('total_price');
                    $periods[] = [
                        'id' => $start->format('Y-m-d') . '_' . $end->format('Y-m-d'),
                        'invoice_no' => '#INV-' . $start->format('ymd'),
                        'date' => $start->format('F d') . ' - ' . $end->format('d, Y'),
                        'time' => 'Consolidated Period',
                        'total_amount' => (float)$totalAmount,
                        'formatted_amount' => number_format($totalAmount, 0) . ' €',
                        'status' => 'Paid',
                    ];
                }
            }

            return $this->success($periods, 'Salon period invoices fetched successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function periodDetails($id)
    {
        try {
            $user = Auth::guard('api')->user();
            if (!$user || $user->role !== 'salon') {
                return $this->error('Unauthorized', 403);
            }

            $dates = explode('_', $id);
            if (count($dates) !== 2) return $this->error('Invalid period');
            
            $startDate = $dates[0];
            $endDate = $dates[1];

            $bookings = Booking::with(['customer', 'items.service'])
                ->where('salon_id', $user->id)
                ->where('status', 'completed')
                ->whereBetween('booking_date', [$startDate, $endDate])
                ->get();

            $totalGenerated = $bookings->sum('total_price');
            $setting = CommissionSetting::where('type', 'salon')->first();
            $commissionRate = $setting ? (float)$setting->commission_rate : 20;

            $onlineComm = round($bookings->where('payment_type', 'online')->sum('total_price') * $commissionRate / 100, 2);
            $codComm = round($bookings->whereIn('payment_type', ['cod', 'onsite'])->where('booking_type', '!=', 'custom')->sum('total_price') * $commissionRate / 100, 2);
            $customComm = round($bookings->where('booking_type', 'custom')->sum('total_price') * $commissionRate / 100, 2);

            $netAmount = $totalGenerated - ($onlineComm + $codComm + $customComm);

            $services = $bookings->map(function ($booking) {
                // Get Actual Slot Time
                $startTimeSlot = $booking->slots->sortBy(function($slot) {
                    return optional($slot->scheduleTime)->scheduled_start_time;
                })->first();
                
                $timeStr = '';
                if ($startTimeSlot && $startTimeSlot->scheduleTime) {
                    $timeStr = ', ' . Carbon::parse($startTimeSlot->scheduleTime->scheduled_start_time)->format('g:i A');
                }

                return [
                    'id' => $booking->id,
                    'customer_name' => $booking->customer->name ?? 'Unknown',
                    'service_name' => $booking->items->map(fn($i) => ($i->service->service_name ?? '') . ($i->quantity > 1 ? ' x ' . $i->quantity : ''))->implode(' + '),
                    'price' => (float)$booking->total_price,
                    'formatted_price' => '€' . number_format($booking->total_price, 2),
                    'date' => Carbon::parse($booking->booking_date)->format('F d') . $timeStr,
                ];
            });

            return $this->success([
                'invoice_no' => '#INV-' . Carbon::parse($startDate)->format('ymd'),
                'period' => Carbon::parse($startDate)->format('M d') . ' - ' . Carbon::parse($endDate)->format('d, Y'),
                'summary' => [
                    ['label' => 'Total Generated', 'value' => number_format($totalGenerated, 2) . ' €'],
                    ['label' => 'Online Payment Commission (' . $commissionRate . '%)', 'value' => '-' . number_format($onlineComm, 2) . ' €'],
                    ['label' => 'COD Commission (' . $commissionRate . '%)', 'value' => '-' . number_format($codComm, 2) . ' €'],
                    ['label' => 'Custom Order Commission (' . $commissionRate . '%)', 'value' => '-' . number_format($customComm, 2) . ' €'],
                    ['label' => 'Net Amount Received', 'value' => number_format($netAmount, 2) . ' €', 'is_total' => true],
                ],
                'services' => $services
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function periodDownload($id)
    {
        try {
            $user = Auth::guard('api')->user();
            $dates = explode('_', $id);
            $startDate = $dates[0];
            $endDate = $dates[1];

            $bookings = Booking::with(['customer', 'items.service'])
                ->where('salon_id', $user->id)
                ->where('status', 'completed')
                ->whereBetween('booking_date', [$startDate, $endDate])
                ->get();

            $totalGenerated = $bookings->sum('total_price');
            $setting = CommissionSetting::where('type', 'salon')->first();
            $commissionRate = $setting ? (float)$setting->commission_rate : 20;

            $onlineComm = round($bookings->where('payment_type', 'online')->sum('total_price') * $commissionRate / 100, 2);
            $codComm = round($bookings->whereIn('payment_type', ['cod', 'onsite'])->where('booking_type', '!=', 'custom')->sum('total_price') * $commissionRate / 100, 2);
            $customComm = round($bookings->where('booking_type', 'custom')->sum('total_price') * $commissionRate / 100, 2);
            
            $netAmount = $totalGenerated - ($onlineComm + $codComm + $customComm);

            return view('invoices.salon_period_template', [
                'bookings' => $bookings,
                'period' => Carbon::parse($startDate)->format('M d') . ' - ' . Carbon::parse($endDate)->format('d, Y'),
                'totalGenerated' => $totalGenerated,
                'onlineComm' => $onlineComm,
                'codComm' => $codComm,
                'customComm' => $customComm,
                'netAmount' => $netAmount,
                'rate' => $commissionRate,
                'salon' => $user,
                'invoice_no' => '#INV-' . Carbon::parse($startDate)->format('ymd')
            ]);
        } catch (\Exception $e) {
            return abort(404);
        }
    }

    /**
     * Download the single booking invoice.
     */
    public function download($id)
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user || $user->role !== 'salon') {
                return abort(403, 'Unauthorized');
            }


            $booking = Booking::with(['customer', 'items.service'])
                ->where('salon_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$booking) {
                return abort(404, 'Invoice not found');
            }

            // Calculate details for the template
            $totalGenerated = (float)$booking->total_price;

            // Get commission rate from Admin Panel settings
            $setting = CommissionSetting::where('type', 'salon')->first();
            $commissionRate = $setting ? (float)$setting->commission_rate : ($booking->commission_rate ?? 0);

            // Calculate admin commission based on current rate
            $adminCommission = round($totalGenerated * $commissionRate / 100, 2);
            $amountReceived = $totalGenerated - $adminCommission;

            return view('invoices.salon_template', [
                'booking' => $booking,
                'salon' => $user,
                'totalGenerated' => $totalGenerated,
                'commissionRate' => $commissionRate,
                'adminCommission' => $adminCommission,
                'amountReceived' => $amountReceived
            ]);

        } catch (\Exception $e) {
            return abort(404, 'Invoice not found');
        }
    }
}
