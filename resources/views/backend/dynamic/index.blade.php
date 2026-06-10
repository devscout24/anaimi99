@extends('backend.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <style>
        .dynamic-hero {
            background: linear-gradient(135deg,#0f172a 0%,#1e293b 60%);
            color: #fff;
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 18px;
            box-shadow: 0 12px 30px rgba(2,6,23,0.12);
        }

        .dynamic-card {
            border-radius: 14px;
            border: 0;
            box-shadow: 0 8px 24px rgba(15,23,42,0.06);
            overflow: hidden;
        }

        .dynamic-card .card-header {
            background: #fff;
            border-bottom: 1px solid #eef2f7;
            padding: 12px 16px;
        }

        .dynamic-table thead th {
            background: #fbfdff;
            font-weight: 700;
            color: #0f172a;
        }

        .dynamic-actions {
            display:flex;gap:.5rem;align-items:center;
        }

        @media (max-width: 767px) {
            .dynamic-hero { text-align: left; }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-3">
        <div class="dynamic-hero d-flex justify-content-between align-items-center flex-column flex-md-row gap-2">
            <div>
                <h4 class="mb-1">{{ __('admin.dynamic_content') }}</h4>
                
            </div>
            <div class="dynamic-actions">
                <a href="{{ route('admin.dynamic.create') }}" class="btn btn-light">{{ __('admin.add_new') }}</a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card dynamic-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('admin.list') }}</h5>
                    </div>
                    <div class="card-body p-3">
                        <div class="table-responsive">
                            <table id="dynamic-table" class="table table-bordered align-middle w-100 dynamic-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('admin.title') }}</th>
                                        <th>{{ __('admin.description') }}</th>
                                        <th style="width:140px">{{ __('admin.action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(function() {
            let table = $('#dynamic-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.dynamic.index') }}",
                columns: [
                    { data: 'title', name: 'title' },
                    { data: 'description', name: 'description' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
                order: [[0, 'desc']],
                lengthMenu: [10, 25, 50],
                language: {processing: '<span class="spinner-border spinner-border-sm me-2"></span>{{ __('admin.loading') }}'}
            });

            // Improve layout after draw
            $('#dynamic-table').on('draw.dt', function() {
                $('#dynamic-table thead th').addClass('text-nowrap');
            });
        });
    </script>
@endpush
