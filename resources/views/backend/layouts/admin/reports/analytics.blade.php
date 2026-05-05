@extends('backend.app')
@section('title', 'Business Analytics')
@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Business Analytics</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">Reports</a></li>
                    <li class="breadcrumb-item active">Analytics</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<div class="row">
    <!-- Top Customers -->
    <div class="col-xl-4">
        <div class="card card-height-100">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Top 10 Customers</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-centered table-hover align-middle table-nowrap mb-0">
                        <thead class="text-muted table-light">
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col" class="text-center">Bookings</th>
                                <th scope="col" class="text-end">Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topCustomers as $customer)
                            <tr>
                                <td>{{ $customer->customer->name ?? 'Deleted' }}</td>
                                <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $customer->total_bookings }}</span></td>
                                <td class="text-end fw-medium">€{{ number_format($customer->total_spent, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Barbers -->
    <div class="col-xl-4">
        <div class="card card-height-100">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Top 10 Barbers</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-centered table-hover align-middle table-nowrap mb-0">
                        <thead class="text-muted table-light">
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col" class="text-center">Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topBarbers as $barber)
                            <tr>
                                <td>
                                    {{ $barber->barber->name ?? 'Deleted' }}
                                    @if($barber->barber && $barber->barber->salon)
                                        <br><small class="text-muted">({{ $barber->barber->salon->name }})</small>
                                    @endif
                                </td>
                                <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $barber->total_bookings }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Salon Performance -->
    <div class="col-xl-4">
        <div class="card card-height-100">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Salon Performance</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-centered table-hover align-middle table-nowrap mb-0">
                        <thead class="text-muted table-light">
                            <tr>
                                <th scope="col">Salon</th>
                                <th scope="col" class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salonPerformance as $salon)
                            <tr>
                                <td>{{ $salon->salon->name ?? 'Deleted' }}</td>
                                <td class="text-end fw-medium text-success">€{{ number_format($salon->total_revenue, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
