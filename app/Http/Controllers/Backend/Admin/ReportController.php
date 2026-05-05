<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function transactions(Request $request)
    {
        if ($request->ajax()) {
            $data = Payment::query()->with(['customer', 'barber', 'salon'])
                ->select('payments.*');

            if ($request->type) {
                if ($request->type == 'salon') {
                    $data->whereNotNull('salon_id');
                } elseif ($request->type == 'barber') {
                    $data->whereNull('salon_id')->whereNotNull('barber_id');
                }
            }

            if ($request->status) {
                $data->where('payment_status', '=', $request->status);
            }

            if ($request->start_date && $request->end_date) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $data->whereBetween('created_at', [$startDate, $endDate]);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('provider', function ($row) {
                    if ($row->salon) {
                        return $row->salon->name . ' (Salon)';
                    }
                    return $row->barber ? $row->barber->name . ' (Barber)' : 'N/A';
                })
                ->addColumn('customer_name', function ($row) {
                    return $row->customer ? $row->customer->name : 'N/A';
                })
                ->addColumn('payment_status_label', function ($row) {
                    $class = match ($row->payment_status) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'secondary'
                    };
                    $icon = match ($row->payment_status) {
                        'paid' => 'check-circle',
                        'pending' => 'clock',
                        'failed' => 'x-circle',
                        default => 'info-circle'
                    };
                    return '<span class="badge bg-' . $class . '-subtle text-' . $class . ' text-uppercase"><i class="ri-' . $icon . '-line align-bottom me-1"></i> ' . ucfirst($row->payment_status) . '</span>';
                })
                ->rawColumns(['payment_status_label'])
                ->make(true);
        }

        return view('backend.layouts.admin.reports.transactions');
    }

    public function providerReports(Request $request)
    {
        if ($request->ajax()) {
            $query = User::query()->whereIn('role', ['salon', 'home_barbar']);

            if ($request->role) {
                $query->where('role', '=', $request->role);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('total_online', function ($user) use ($request) {
                    return $this->calcStats($user, 'online', $request);
                })
                ->addColumn('total_cod', function ($user) use ($request) {
                    return $this->calcStats($user, 'cod', $request);
                })
                ->addColumn('total_onsite', function ($user) use ($request) {
                    return $this->calcStats($user, 'onsite', $request);
                })
                ->addColumn('total_custom', function ($user) use ($request) {
                    return $this->calcStats($user, 'custom', $request);
                })
                ->addColumn('total_amount', function ($user) use ($request) {
                    return $this->calcStats($user, 'total', $request);
                })
                ->addColumn('total_commission', function ($user) use ($request) {
                    return $this->calcStats($user, 'commission', $request);
                })
                ->addColumn('provider_earnings', function ($user) use ($request) {
                    return $this->calcStats($user, 'earnings', $request);
                })
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-primary btn-sm downloadReport" data-id="' . $row->id . '">Download PDF</button>';
                })
                ->make(true);
        }

        return view('backend.layouts.admin.reports.provider_reports');
    }

    public function bookingReport(Request $request)
    {
        if ($request->ajax()) {
            $data = \App\Models\Booking::with(['customer', 'barber', 'salon'])
                ->select('bookings.*');

            if ($request->start_date && $request->end_date) {
                $data->whereBetween('created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
            }

            if ($request->status) {
                $data->where('status', $request->status);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('customer_name', fn($row) => $row->customer->name ?? 'N/A')
                ->addColumn('provider_name', function ($row) {
                    if ($row->salon) return $row->salon->name . ' (Salon)';
                    return $row->barber->name ?? 'N/A';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('d M, Y');
                })
                ->editColumn('status', function ($row) {
                    $class = match ($row->status) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'accepted' => 'info',
                        default => 'secondary'
                    };
                    $icon = match ($row->status) {
                        'completed' => 'checkbox-circle',
                        'pending' => 'history',
                        'cancelled' => 'close-circle',
                        'accepted' => 'checkbox-multiple-marked',
                        default => 'indeterminate-circle'
                    };
                    return '<span class="badge bg-' . $class . '-subtle text-' . $class . ' text-uppercase"><i class="ri-' . $icon . '-line align-bottom me-1"></i> ' . ucfirst($row->status) . '</span>';
                })
                ->rawColumns(['status'])
                ->make(true);
        }
        return view('backend.layouts.admin.reports.booking_report');
    }

    public function revenueReport(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

        $stats = Payment::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('SUM(amount) as total_revenue, SUM(admin_commission) as total_commission, SUM(provider_earnings) as total_provider_pay')
            ->first();

        // If no payments found, make sure we return an object with zero values instead of null
        if (!$stats) {
            $stats = (object)[
                'total_revenue' => 0,
                'total_commission' => 0,
                'total_provider_pay' => 0
            ];
        }

        $dailyRevenue = Payment::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(admin_commission) as commission')
            ->groupBy('date')
            ->get();

        return view('backend.layouts.admin.reports.revenue_report', compact('stats', 'dailyRevenue', 'startDate', 'endDate'));
    }

    public function analyticReports(Request $request)
    {
        // Top Customers
        $topCustomers = \App\Models\Booking::select('customer_id', DB::raw('count(*) as total_bookings'), DB::raw('sum(total_price) as total_spent'))
            ->with('customer')
            ->groupBy('customer_id')
            ->orderByDesc('total_bookings')
            ->limit(10)
            ->get();

        // Top Barbers
        $topBarbers = \App\Models\Booking::select('barber_id', DB::raw('count(*) as total_bookings'))
            ->whereNotNull('barber_id')
            ->with(['barber.salon'])
            ->groupBy('barber_id')
            ->orderByDesc('total_bookings')
            ->limit(10)
            ->get();

        // Salon Performance
        $salonPerformance = \App\Models\Booking::select('salon_id', DB::raw('count(*) as total_bookings'), DB::raw('sum(total_price) as total_revenue'))
            ->whereNotNull('salon_id')
            ->with('salon')
            ->groupBy('salon_id')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        return view('backend.layouts.admin.reports.analytics', compact('topCustomers', 'topBarbers', 'salonPerformance'));
    }

    public function loyaltyReport(Request $request)
    {
        if ($request->ajax()) {
            $data = \App\Models\LoyalityAdd::with(['customer', 'booking', 'salon', 'barber'])
                ->select('loyality_adds.*');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->customer->name ?? 'N/A')
                ->addColumn('provider_name', function ($row) {
                    if ($row->salon) return $row->salon->name . ' (Salon)';
                    return $row->barber->name ?? 'N/A';
                })
                ->addColumn('invoice', fn($row) => $row->booking->invoice_no ?? 'N/A')
                ->addColumn('point', fn($row) => $row->per_booking_loyality_point ?? 0)
                ->make(true);
        }
        return view('backend.layouts.admin.reports.loyalty_report');
    }

    private function calcStats($user, $type, $request)
    {
        $q = Payment::query()->where(function ($query) use ($user) {
            $query->where('salon_id', '=', $user->id)->orWhere('barber_id', '=', $user->id);
        });

        if ($request->status) {
            $q->where('payment_status', '=', $request->status);
        } else {
            $q->where('payment_status', '=', 'paid');
        }

        if ($request->start_date && $request->end_date) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $q->whereBetween('created_at', [$startDate, $endDate]);
        }

        switch ($type) {
            case 'online':
                return (float) $q->where('payment_type', '=', 'online')->sum('amount');
            case 'cod':
                return (float) $q->where('payment_type', '=', 'cod')->sum('amount');
            case 'onsite':
                return (float) $q->where('payment_type', '=', 'onsite')->sum('amount');
            case 'custom':
                return (float) $q->where('payment_type', '=', 'custom')->sum('amount');
            case 'total':
                return (float) $q->sum('amount');
            case 'commission':
                return (float) $q->sum('admin_commission');
            case 'earnings':
                return (float) $q->sum('provider_earnings');
            default:
                return 0;
        }
    }
}
