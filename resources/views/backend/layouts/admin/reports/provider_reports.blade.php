@extends('backend.app')
@section('title', __('admin.provider_financial_reports'))
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">{{ __('admin.provider_reports_title') }}</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>{{ __('admin.date_range') }}</label>
                        <div class="input-group">
                            <input type="date" id="start_date" class="form-control">
                            <input type="date" id="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>{{ __('admin.role') }}</label>
                        <select id="role" class="form-control">
                            <option value="">{{ __('admin.all') }}</option>
                            <option value="salon">{{ __('admin.salon') }}</option>
                            <option value="home_barbar">{{ __('admin.home_barber') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>{{ __('admin.status') }}</label>
                        <select id="status_filter" class="form-control">
                            <option value="paid">{{ __('admin.paid_default') }}</option>
                            <option value="pending">{{ __('admin.pending') }}</option>
                            <option value="">{{ __('admin.all') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button id="filterBtn" class="btn btn-primary w-100">{{ __('admin.filter') }}</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered nowrap w-100" id="reportTable">
                        <thead>
                            <tr>
                                <th>{{ __('admin.no') }}</th>
                                <th>{{ __('admin.name') }}</th>
                                <th>{{ __('admin.role') }}</th>
                                <th>{{ __('admin.online_pmt') }}</th>
                                <th>{{ __('admin.cod') }}</th>
                                <th>{{ __('admin.onsite') }}</th>
                                <th>{{ __('admin.custom') }}</th>
                                <th>{{ __('admin.total_revenue') }}</th>
                                <th>{{ __('admin.admin_commission_short') }}</th>
                                <th>{{ __('admin.net_earnings') }}</th>
                                <th>{{ __('admin.action') }}</th>
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
