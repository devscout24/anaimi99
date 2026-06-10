@extends('backend.app')
@section('title', __('admin.business_analytics'))
@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ __('admin.business_analytics') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ __('admin.reports') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('admin.analytics') }}</li>
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
                <h4 class="card-title mb-0 flex-grow-1">{{ __('admin.top_10_customers') }}</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-centered table-hover align-middle table-nowrap mb-0">
                        <thead class="text-muted table-light">
                            <tr>
                                <th scope="col">{{ __('admin.name') }}</th>
                                <th scope="col" class="text-center">{{ __('admin.bookings') }}</th>
                                <th scope="col" class="text-end">{{ __('admin.spent') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topCustomers as $customer)
                            <tr>
                                <td>{{ $customer->customer->name ?? __('admin.deleted') }}</td>
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
                <h4 class="card-title mb-0 flex-grow-1">{{ __('admin.top_10_barbers') }}</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-centered table-hover align-middle table-nowrap mb-0">
                        <thead class="text-muted table-light">
                            <tr>
                                <th scope="col">{{ __('admin.name') }}</th>
                                <th scope="col" class="text-center">{{ __('admin.bookings') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topBarbers as $barber)
                            <tr>
                                <td>
                                    {{ $barber->barber->name ?? __('admin.deleted') }}
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
                <h4 class="card-title mb-0 flex-grow-1">{{ __('admin.salon_performance') }}</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-centered table-hover align-middle table-nowrap mb-0">
                        <thead class="text-muted table-light">
                            <tr>
                                <th scope="col">{{ __('admin.salon') }}</th>
                                <th scope="col" class="text-end">{{ __('admin.revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salonPerformance as $salon)
                            <tr>
                                <td>{{ $salon->salon->name ?? __('admin.deleted') }}</td>
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
