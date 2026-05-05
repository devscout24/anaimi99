@extends('backend.app')
@section('title', 'Loyalty Points Report')
@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Loyalty Points Report</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">Reports</a></li>
                    <li class="breadcrumb-item active">Loyalty</li>
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
                        <h4 class="card-title mb-0">Points History</h4>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-nowrap align-middle" id="loyalty-table">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Provider</th>
                                <th>Points awarded</th>
                                <th>Invoice Ref</th>
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
                        return `<span class="badge bg-success-subtle text-success fs-12"><i class="ri-star-fill me-1"></i>${data} Points</span>`;
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
                        }) : 'N/A';
                    }
                },
            ]
        });
    });
</script>
@endpush
