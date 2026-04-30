<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommissionSetting;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPaymentController extends Controller
{
    use ApiResponse;

    /**
     * List all payments with filters
     */
    public function index(Request $request)
    {
        try {
            $query = Payment::with(['booking', 'customer', 'barber', 'salon']);

            // Filter by booking_type
            if ($request->booking_type) {
                $query->where('booking_type', $request->booking_type);
            }

            // Filter by payment_type
            if ($request->payment_type) {
                $query->where('payment_type', $request->payment_type);
            }

            // Filter by payment_status
            if ($request->payment_status) {
                $query->where('payment_status', $request->payment_status);
            }

            // Filter by date range
            if ($request->from_date) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->to_date) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Filter by salon_id
            if ($request->salon_id) {
                $query->where('salon_id', $request->salon_id);
            }

            // Filter by barber_id
            if ($request->barber_id) {
                $query->where('barber_id', $request->barber_id);
            }

            $payments = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

            return $this->success($payments, 'Payments fetched successfully');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Show single payment detail
     */
    public function show($id)
    {
        try {
            $payment = Payment::with(['booking', 'customer', 'barber', 'salon'])->find($id);

            if (!$payment) {
                return $this->error('Payment not found');
            }

            return $this->success($payment, 'Payment details fetched successfully');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get all commission settings
     */
    public function getCommissionSettings()
    {
        try {
            $settings = CommissionSetting::all();

            return $this->success($settings, 'Commission settings fetched successfully');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Create or update a commission setting
     */
    public function setCommissionSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:salon,home_barber',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', $validator->errors());
        }

        try {
            $setting = CommissionSetting::updateOrCreate(
                ['type' => $request->type],
                [
                    'commission_rate' => $request->commission_rate,
                    'description' => $request->description,
                ]
            );

            return $this->success($setting, 'Commission setting saved successfully');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Update payment status (e.g. mark cod/onsite as paid)
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', $validator->errors());
        }

        try {
            $payment = Payment::find($id);

            if (!$payment) {
                return $this->error('Payment not found');
            }

            $payment->payment_status = $request->payment_status;

            if ($request->payment_status == 'paid') {
                $payment->paid_at = now();
            }

            $payment->save();

            // Also update booking payment_status
            if ($payment->booking) {
                $payment->booking->payment_status = $request->payment_status;
                $payment->booking->save();
            }

            return $this->success($payment, 'Payment status updated successfully');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
