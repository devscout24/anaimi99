@extends('backend.app')
@section('title', 'Provider Financial Reports')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Provider Reports (Earnings & Commission)</h4>
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
                        <label>Role</label>
                        <select id="role" class="form-control">
                            <option value="">All</option>
                            <option value="salon">Salon</option>
                            <option value="home_barbar">Home Barber</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Status</label>
                        <select id="status_filter" class="form-control">
                            <option value="paid">Paid (Default)</option>
                            <option value="pending">Pending</option>
                            <option value="">All</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button id="filterBtn" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered nowrap w-100" id="reportTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Online Pmt</th>
                                <th>COD</th>
                                <th>Onsite</th>
                                <th>Custom</th>
                                <th>Total Revenue</th>
                                <th>Admin Comm.</th>
                                <th>Net Earnings</th>
                                <th>Action</th>
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
    var table = $('#reportTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.reports.providers') }}",
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.role = $('#role').val();
                d.status = $('#status_filter').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'name', name: 'name'},
            {data: 'role', name: 'role'},
            {data: 'total_online', name: 'total_online', orderable: false, searchable: false},
            {data: 'total_cod', name: 'total_cod', orderable: false, searchable: false},
            {data: 'total_onsite', name: 'total_onsite', orderable: false, searchable: false},
            {data: 'total_custom', name: 'total_custom', orderable: false, searchable: false},
            {data: 'total_amount', name: 'total_amount', orderable: false, searchable: false},
            {data: 'total_commission', name: 'total_commission', orderable: false, searchable: false},
            {data: 'provider_earnings', name: 'provider_earnings', orderable: false, searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    $('#filterBtn').click(function() {
        table.ajax.reload();
    });

    $(document).on('click', '.downloadReport', function() {
        var id = $(this).data('id');
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        var url = "{{ route('admin.reports.download-pdf', ':id') }}";
        url = url.replace(':id', id);

        window.location.href = url + "?start_date=" + startDate + "&end_date=" + endDate;
    });
});
</script>
@endpush
