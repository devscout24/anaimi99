@extends('backend.app')
@section('title', __('admin.loyalty_points_report'))
@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ __('admin.loyalty_points_report') }}</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ __('admin.reports') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('admin.loyalty') }}</li>
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
                        <h4 class="card-title mb-0">{{ __('admin.points_history') }}</h4>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-nowrap align-middle" id="loyalty-table">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>#</th>
                                <th>{{ __('admin.customer') }}</th>
                                <th>{{ __('admin.provider') }}</th>
                                <th>{{ __('admin.points_awarded') }}</th>
                                <th>{{ __('admin.invoice_ref') }}</th>
                                <th>{{ __('admin.date') }}</th>
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
        $('#loyalty-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('admin.reports.loyalty') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'user_name', name: 'user_name'},
                {data: 'provider_name', name: 'provider_name'},
                {
                    data: 'point',
                    name: 'point',
                    render: function(data) {
                        return `<span class="badge bg-success-subtle text-success fs-12"><i class="ri-star-fill me-1"></i>${data} {{ __('admin.points') }}</span>`;
                    }
                },
                {data: 'invoice', name: 'invoice'},
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('en-GB', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        }) : @json(__('admin.not_available'));
                    }
                },
            ]
        });
    });
</script>
@endpush
