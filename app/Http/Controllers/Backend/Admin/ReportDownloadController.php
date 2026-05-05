<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportDownloadController extends Controller
{
    public function downloadProviderPdf(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();

        $payments = Payment::where(function ($q) use ($user) {
            $q->where('salon_id', $user->id)->orWhere('barber_id', $user->id);
        })
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $stats = [
            'total_amount' => $payments->sum('amount'),
            'total_commission' => $payments->sum('admin_commission'),
            'provider_earnings' => $payments->sum('provider_earnings'),
            'online' => $payments->where('payment_type', 'online')->sum('amount'),
            'cod' => $payments->where('payment_type', 'cod')->sum('amount'),
            'onsite' => $payments->where('payment_type', 'onsite')->sum('amount'),
            'custom' => $payments->where('payment_type', 'custom')->sum('amount'),
        ];

        $pdf = Pdf::loadView('backend.layouts.admin.reports.pdf_provider', compact('user', 'payments', 'stats', 'startDate', 'endDate'));
        return $pdf->download('Report_' . $user->name . '_' . date('Y-m-d') . '.pdf');
    }
}
