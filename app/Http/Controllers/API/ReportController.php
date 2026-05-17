<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponse;

    /**
     * Get salon report data.
     *
     * Query Params:
     * - filter: today | this_week | this_month (default: this_month)
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::guard("api")->user();



            if (!$user || $user->role !== 'salon') {
                return $this->error('Unauthorized. Only salons can access this report.', 403);
            }

            $filter = $request->filter ?? 'this_month';
            $startDate = null;
            $endDate = Carbon::today()->endOfDay();



            switch ($filter) {
                case 'today':
                    $startDate = Carbon::today()->startOfDay();
                    break;
                case 'this_week':
                    $startDate = Carbon::now()->startOfWeek()->startOfDay();
                    break;
                case 'this_month':
                default:
                    $startDate = Carbon::now()->startOfMonth()->startOfDay();
                    break;
            }

            // Base query for bookings for this salon
            $query = Booking::where('salon_id', $user->id)
                ->whereIn('status', ['accepted', 'confirmed', 'completed'])
                ->whereBetween('booking_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            $bookings = (clone $query)->get();

            // 1. Top Level Stats
            $totalAppointments = $bookings->count();
            $totalRevenue = $bookings->sum('total_price');
            $totalAdminCommission = $bookings->sum('admin_commission');
            $netAmountReceived = $bookings->sum('provider_earnings');

            // Fallback calculation if provider_earnings is not filled in database
            if ($netAmountReceived == 0 && $totalRevenue > 0) {
                $netAmountReceived = $totalRevenue - $totalAdminCommission;
            }

            // 2. Revenue Distribution
            // Assuming we take the average commission rate or the rate from setting
            $avgCommissionRate = $bookings->avg('commission_rate') ?? 0;

            $revenueDistribution = [
                'total_generated' => [
                    'label' => 'Total generated',
                    'value' => (float)$totalRevenue,
                    'formatted' => '€' . number_format($totalRevenue, 2),
                ],
                'admin_commission' => [
                    'label' => "Tremley Commission (" . round($avgCommissionRate) . "%)",
                    'value' => -(float)$totalAdminCommission,
                    'formatted' => '-€' . number_format($totalAdminCommission, 2),
                ],
                'net_amount' => [
                    'label' => 'Net amount received',
                    'value' => (float)$netAmountReceived,
                    'formatted' => '€' . number_format($netAmountReceived, 2),
                ],
            ];

            // 3. Revenue Evolution (Chart Data)
            $revenueEvolution = $this->getRevenueEvolution($user->id, $startDate, $endDate);

            // 4. Barber Performance
            $barberPerformance = $this->getBarberPerformance($user->id, $startDate, $endDate);

            $data = [
                'stats' => [
                    'appointments' => [
                        'value' => $totalAppointments,
                        'label' => 'Appointment',
                    ],
                    'commission' => [
                        'value' => (float)$netAmountReceived, // Based on screenshot "Commission" seems to be what they earn
                        'formatted' => '€' . round($netAmountReceived),
                        'label' => 'Commission',
                    ],
                    'revenue' => [
                        'value' => (float)$totalRevenue,
                        'formatted' => '€' . round($totalRevenue),
                        'label' => 'Revenue',
                    ],
                ],
                'revenue_distribution' => $revenueDistribution,
                'revenue_evolution' => $revenueEvolution,
                'barber_performance' => $barberPerformance,
            ];

            return $this->success($data, 'Salon report fetched successfully.');

        } catch (\Throwable $th) {
            return $this->error('Something went wrong', $th->getMessage());
        }
    }

    /**
     * Get daily revenue for the period.
     */
    private function getRevenueEvolution($salonId, $startDate, $endDate)
    {
        $dailyRevenue = Booking::select(
                DB::raw('DATE(booking_date) as date'),
                DB::raw('SUM(total_price) as daily_total')
            )
            ->where('salon_id', $salonId)
            ->whereIn('status', ['accepted', 'confirmed', 'completed'])
            ->whereBetween('booking_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('daily_total', 'date');

        $chartData = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $dayLabel = $current->format('D'); // Lun, Mar, etc (using English labels for now, can be translated)

            $chartData[] = [
                'date' => $dateStr,
                'label' => $dayLabel,
                'value' => (float)($dailyRevenue[$dateStr] ?? 0),
            ];
            $current->addDay();
        }

        return $chartData;
    }

    /**
     * Get performance stats for each barber in the salon.
     */
    private function getBarberPerformance($salonId, $startDate, $endDate)
    {
        // Get all barbers associated with this salon (assuming barber_id is used for assignment)
        $performance = Booking::with('barber')
            ->select(
                'barber_id',
                DB::raw('COUNT(*) as booking_count'),
                DB::raw('SUM(total_price) as total_revenue')
            )
            ->where('salon_id', $salonId)
            ->whereIn('status', ['accepted', 'confirmed', 'completed'])
            ->whereBetween('booking_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->whereNotNull('barber_id')
            ->groupBy('barber_id')
            ->get();

        return $performance->map(function ($stat) {
            return [
                'barber_id' => $stat->barber_id,
                'name' => $stat->barber->name ?? 'Unknown',
                'avatar' => $stat->barber->profile_image ? asset($stat->barber->profile_image) : null,
                'booking_count' => $stat->booking_count,
                'booking_count_text' => $stat->booking_count . ' haircuts',
                'total_revenue' => (float)$stat->total_revenue,
                'total_revenue_formatted' => '€' . round($stat->total_revenue),
            ];
        });
    }
}

