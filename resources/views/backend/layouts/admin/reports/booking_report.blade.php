@extends('backend.app')
@section('title', 'Booking Report')
@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Booking Report</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">Reports</a></li>
                    <li class="breadcrumb-item active">Bookings</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-0">
                <div class="row g-4 align-items-center">
                    <div class="col-sm-auto">
                        <div>
                            <h4 class="card-title mb-0">Booking List</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border border-dashed border-start-0 border-end-0">
                <div class="row g-3">
                    <div class="col-xxl-3 col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text">From</span>
                            <input type="date" id="start_date" class="form-control">
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text">To</span>
                            <input type="date" id="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="col-xxl-2 col-sm-4">
                        <select id="data" class="form-control">
                            <option value="">Status (All)</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-xxl-1 col-sm-4">
                        <button id="filter" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-nowrap align-middle" id="booking-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Provider</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function() {
        let table = $('#booking-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.reports.bookings') }}",
                data: function (d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.status = $('#data').val();
                }
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'invoice_no', name: 'invoice_no'},
                {data: 'customer_name', name: 'customer_name'},
                {data: 'provider_name', name: 'provider_name'},
                {
                    data: 'total_price',
                    name: 'total_price',
                    render: function(data) {
                        return `€${data}`;
                    }
                },
                {data: 'status', name: 'status'},
                {data: 'created_at', name: 'created_at'},
            ]
        });

        $('#filter').click(function(){
            table.draw();
        });
    });
</script>
@endpush
