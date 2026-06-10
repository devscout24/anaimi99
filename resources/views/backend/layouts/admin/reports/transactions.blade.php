@extends('backend.app')
@section('title', __('admin.all_transactions'))

@section('content')

<div class="container-fluid">

    <!-- ================= FILTER CARD ================= -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">

            <div class="row g-3">

                <!-- Date Range -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold">{{ __('admin.date_from') }}</label>
                    <input type="date" id="start_date" class="form-control shadow-sm">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">{{ __('admin.date_to') }}</label>
                    <input type="date" id="end_date" class="form-control shadow-sm">
                </div>

                <!-- Provider Type -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold">{{ __('admin.provider_type') }}</label>
                    <select id="type" class="form-select shadow-sm">
                        <option value="">{{ __('admin.all') }}</option>
                        <option value="salon">{{ __('admin.salon') }}</option>
                        <option value="barber">{{ __('admin.home_barber') }}</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold">{{ __('admin.status') }}</label>
                    <select id="data" class="form-select shadow-sm">
                        <option value="">{{ __('admin.all') }}</option>
                        <option value="paid">{{ __('admin.paid') }}</option>
                        <option value="pending">{{ __('admin.pending') }}</option>
                        <option value="failed">{{ __('admin.failed') }}</option>
                    </select>
                </div>







                <!-- Button -->
                <div class="col-md-2 d-flex align-items-end">
                    <button id="filterBtn" class="btn btn-primary w-100 shadow-sm">
                        🔍 Filter
                    </button>
                </div>

            </div>

        </div>
    </div>

    <!-- ================= TABLE CARD ================= -->
    <div class="card shadow-sm border-0">

        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">💰 All Transactions</h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="transactionTable">

                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>{{ __('admin.booking') }}</th>
                            <th>{{ __('admin.customer') }}</th>
                            <th>{{ __('admin.provider') }}</th>
                            <th>{{ __('admin.amount') }}</th>
                            <th>{{ __('admin.commission') }}</th>
                            <th>{{ __('admin.earnings') }}</th>
                            <th>{{ __('admin.type') }}</th>
                            <th>{{ __('admin.status') }}</th>
                            <th>{{ __('admin.date') }}</th>
                        </tr>
                    </thead>

                </table>
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
        responsive: true,
        ajax: {
            url: "{{ route('admin.reports.transactions') }}",
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.type = $('#type').val();
                d.status = $('#data').val();
            }
        },

        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},

            {data: 'booking_id'},

            {data: 'customer_name'},

            {data: 'provider'},

            {
                data: 'amount',
                render: function(data) {
                    return `<span class="badge bg-success-subtle text-success fs-13">€${data}</span>`;
                }
            },

            {
                data: 'admin_commission',
                render: function(data) {
                    return `<span class="badge bg-info-subtle text-info fs-13">€${data}</span>`;
                }
            },

            {
                data: 'provider_earnings',
                render: function(data) {
                    return `<span class="badge bg-primary-subtle text-primary fs-13">€${data}</span>`;
                }
            },

            {
                data: 'payment_type',
                render: function(data) {
                    let color = 'secondary';
                    if(data === 'online') color = 'primary';
                    if(data === 'cod') color = 'warning';
                    if(data === 'custom') color = 'info';

                    return `<span class="badge badge-label bg-${color}"><i class="ri-bill-line label-icon"></i> ${data.toUpperCase()}</span>`;
                }
            },

            {
                data: 'payment_status_label',
                render: function(data) {
                    let color = 'secondary';
                    let icon = 'ri-checkbox-circle-line';

                    if (data === 'Paid') { color = 'success'; icon = 'ri-check-double-line'; }
                    if (data === 'Pending') { color = 'warning'; icon = 'ri-timer-2-line'; }
                    if (data === 'Failed') { color = 'danger'; icon = 'ri-error-warning-line'; }

                    return `<span class="badge badge-soft-${color} text-uppercase"><i class="${icon} align-bottom me-1"></i> ${data}</span>`;
                }
            },

            {data: 'created_at'}

        ]
    });

    $('#filterBtn').click(function() {
        table.ajax.reload();
    });

});
</script>
@endpush
