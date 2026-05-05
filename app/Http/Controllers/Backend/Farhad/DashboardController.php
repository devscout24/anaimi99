<?php

namespace App\Http\Controllers\Backend\Farhad;

use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Stats Cards
        $totalCustomers = User::where('role', 'customer')->count();
        $totalSalons = User::where('role', 'salon')->count();
        $totalBarbers = User::where('role', 'home_barbar')->count();
        $totalBookings = Booking::count();

        // Revenue (Paid payments)
        $totalRevenue = Payment::where('payment_status', 'paid')->sum('admin_commission');

        // Recent Bookings
        $recentBookings = Booking::with(['customer', 'salon', 'barber'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Recent Transactions
        $recentTransactions = Payment::with('customer')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('backend.layouts.dashboard.index', compact(
            'totalCustomers',
            'totalSalons',
            'totalBarbers',
            'totalBookings',
            'totalRevenue',
            'recentBookings',
            'recentTransactions'
        ));
    }
}
