@extends('backend.app')
@section('title', 'Transactions')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">All Transactions</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Date Range</label>
                        <div class="input-group">
                            <input type="date" id="start_date" class="form-control">
                            <input type="date" id="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>Provider Type</label>
                        <select id="type" class="form-control">
                            <option value="">All</option>
                            <option value="salon">Salon Only</option>
                            <option value="barber">Freelance Barber Only</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Status</label>
                        <select id="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button id="filterBtn" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered nowrap w-100" id="transactionTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Provider</th>
                                <th>Amount</th>
                                <th>Commission</th>
                                <th>Provider Earnings</th>
                                <th>Type</th>
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
    var table = $('#transactionTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.reports.transactions') }}",
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.type = $('#type').val();
                d.status = $('#status').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'booking_id', name: 'booking_id'},
            {data: 'customer_name', name: 'customer_name', orderable: false, searchable: false},
            {data: 'provider', name: 'provider', orderable: false, searchable: false},
            {data: 'amount', name: 'amount'},
            {data: 'admin_commission', name: 'admin_commission'},
            {data: 'provider_earnings', name: 'provider_earnings'},
            {data: 'payment_type', name: 'payment_type'},
            {data: 'payment_status_label', name: 'payment_status_label'},
            {data: 'created_at', name: 'created_at'},
        ]
    });

    $('#filterBtn').click(function() {
        table.ajax.reload();
    });
});
</script>
@endpush
