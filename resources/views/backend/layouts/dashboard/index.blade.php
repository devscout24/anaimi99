@extends('backend.app')
@section('title', 'Admin Dashboard')

@section('content')
    <div class="row">
        <div class="col">

            <div class="h-100">
                <div class="row mb-3 pb-1">
                    <div class="col-12">
                        <div class="d-flex align-items-lg-center flex-lg-row flex-column">
                            <div class="flex-grow-1">
                                <h4 class="fs-16 mb-1">Welcome Back, {{ auth()->user()->name }}!</h4>
                                <p class="text-muted mb-0">Here's what's happening with your booking platform today.</p>
                            </div>
                            <div class="mt-3 mt-lg-0">
                                <div class="row g-3 mb-0 align-items-center">
                                    <div class="col-auto">
                                        <a href="{{ route('admin.reports.bookings') }}" class="btn btn-soft-success material-shadow-none">
                                            <i class="ri-eye-line align-middle me-1"></i> View All Bookings
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div><!-- end card header -->
                    </div>
                </div>
                <!--end row-->

                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0"> Total Admin Revenue</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-4">
                                    <div>
                                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">€{{ number_format($totalRevenue, 2) }} </h4>
                                        <a href="{{ route('admin.reports.revenue') }}" class="text-decoration-underline text-success">View revenue report</a>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle rounded fs-3">
                                            <i class="bx bx-dollar-circle text-success"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Bookings</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-4">
                                    <div>
                                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ number_format($totalBookings) }}</h4>
                                        <a href="{{ route('admin.reports.bookings') }}" class="text-decoration-underline text-info">View bookings</a>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle rounded fs-3">
                                            <i class="bx bx-shopping-bag text-info"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Customers</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-4">
                                    <div>
                                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ number_format($totalCustomers) }} </h4>
                                        <span class="text-muted">Registered Users</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle rounded fs-3">
                                            <i class="bx bx-user-circle text-warning"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Active Providers</p>
                                    </div>

                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-4">
                                    <div>
                                        <h4 class="fs-22 fw-semibold ff-secondary mb-4">{{ $totalSalons + $totalBarbers }} </h4>
                                        <span class="text-muted">S: {{ $totalSalons }} | B: {{ $totalBarbers }}</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle rounded fs-3">
                                            <i class="bx bx-wallet text-primary"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> <!-- end row-->

                <div class="row">
                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Recent Bookings</h4>
                                <div class="flex-shrink-0">
                                    <a href="{{ route('admin.reports.bookings') }}" class="btn btn-soft-info btn-sm">All Bookings</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive table-card">
                                    <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                                        <thead class="text-muted table-light">
                                            <tr>
                                                <th scope="col">Invoice</th>
                                                <th scope="col">Customer</th>
                                                <th scope="col">Provider</th>
                                                <th scope="col">Amount</th>
                                                <th scope="col">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentBookings as $booking)
                                            <tr>
                                                <td><span class="fw-medium text-primary">#{{ $booking->invoice_no }}</span></td>
                                                <td>{{ $booking->customer->name ?? 'N/A' }}</td>
                                                <td>
                                                    @if($booking->salon)
                                                        {{ $booking->salon->name }} <span class="badge bg-info-subtle text-info">S</span>
                                                    @else
                                                        {{ $booking->barber->name ?? 'N/A' }} <span class="badge bg-warning-subtle text-warning">B</span>
                                                    @endif
                                                </td>
                                                <td>€{{ number_format($booking->total_price, 2) }}</td>
                                                <td>
                                                    @php
                                                        $statusClass = match($booking->status) {
                                                            'completed' => 'success',
                                                            'pending' => 'warning',
                                                            'cancelled' => 'danger',
                                                            default => 'info'
                                                        };
                                                    @endphp
                                                    <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }} text-uppercase">{{ $booking->status }}</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Recent Transactions</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive table-card">
                                    <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                                        <tbody>
                                            @foreach($recentTransactions as $payment)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="fs-14 mb-1">{{ $payment->customer->name ?? 'N/A' }}</h6>
                                                            <p class="text-muted mb-0">{{ $payment->created_at->diffForHumans() }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <h6 class="text-success mb-1">+€{{ number_format($payment->admin_commission, 2) }}</h6>
                                                    <p class="text-muted mb-0">Comm.</p>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 text-center">
                                    <a href="{{ route('admin.reports.transactions') }}" class="btn btn-soft-primary btn-sm">View All Transactions</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- end .h-100-->

        </div> <!-- end col -->

    </div>
@endsection
