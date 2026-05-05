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
                    $class = $row->payment_status == 'paid' ? 'success' : ($row->payment_status == 'pending' ? 'warning' : 'danger');
                    return '<span class="badge bg-' . $class . '">' . ucfirst($row->payment_status) . '</span>';
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
