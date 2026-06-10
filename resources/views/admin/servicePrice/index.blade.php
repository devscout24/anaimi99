@extends('backend.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <style>
        .service-price-page {
            padding: 1rem 0 1.5rem;
        }

        .service-price-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%);
            color: #fff;
            border-radius: 18px;
            padding: 1.5rem 1.75rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
            margin-bottom: 1.25rem;
        }

        .service-price-hero h3 {
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .service-price-hero p {
            margin: .4rem 0 0;
            color: rgba(255, 255, 255, 0.78);
        }

        .service-price-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .service-price-card .card-header {
            background: #fff;
            border-bottom: 1px solid #e8eef6;
            padding: 1rem 1.25rem;
        }

        .service-price-card .card-header h5 {
            font-weight: 700;
            color: #0f172a;
        }

        .service-price-card .card-body {
            padding: 1.25rem;
        }

        .service-price-table thead th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        .service-price-table.table-bordered > :not(caption) > * > * {
            border-color: #e5edf5;
        }

        .service-price-form .form-label {
            font-weight: 600;
            color: #334155;
        }

        .service-price-form .form-control,
        .service-price-form .form-select {
            min-height: 46px;
            border-radius: 12px;
            border-color: #d9e2ec;
            box-shadow: none;
        }

        .service-price-form .form-control:focus,
        .service-price-form .form-select:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.15);
        }

        .service-price-actions {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .service-price-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .75rem;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.12);
            color: #1d4ed8;
            font-size: .875rem;
            font-weight: 600;
        }

        .service-price-alert {
            border-radius: 14px;
        }

        .service-price-soft-panel {
            background: #f8fafc;
            border: 1px solid #e8eef6;
            border-radius: 16px;
            padding: 1rem;
        }

        @media (max-width: 991.98px) {
            .service-price-hero {
                padding: 1.25rem;
            }

            .service-price-card .card-body {
                padding: 1rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="service-price-page">
        <div class="service-price-hero">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
                <div>
                    <span class="service-price-badge mb-2">{{ __('admin.admin_service_prices') }}</span>
                    <h3>{{ __('admin.manage_service_price_records') }}</h3>
                    <p>{{ __('admin.service_price_help') }}</p>
                </div>
                <div class="text-md-end">
                    <small class="d-block text-white-50">{{ __('admin.quick_actions') }}</small>
                    <a href="#service-price-form" class="btn btn-light btn-sm mt-2">{{ __('admin.jump_to_form') }}</a>
                </div>
            </div>
        </div>

        <div class="alert alert-success d-none service-price-alert" id="success-msg"></div>

        <div class="row g-3 align-items-start">
            <div class="col-12 col-lg-8">
                <div class="card service-price-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('admin.service_prices') }}</h5>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle w-100 service-price-table" id="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('admin.service') }}</th>
                                        <th>{{ __('admin.type') }}</th>
                                        <th>{{ __('admin.time') }}</th>
                                        <th>{{ __('admin.price') }}</th>
                                        <th>{{ __('admin.discount') }}</th>
                                        <th>{{ __('admin.created_by') }}</th>
                                        <th>{{ __('admin.action') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card service-price-card" id="service-price-form">
                    <div class="card-header">
                        <h5 class="mb-0" id="form-title">{{ __('admin.add_service_price') }}</h5>
                    </div>

                    <div class="card-body service-price-form">
                        <div class="alert alert-danger d-none service-price-alert" id="error-box"></div>

                        <form id="service-price-form-action">
                            @csrf
                            <input type="hidden" id="id">

                            <div class="mb-3">
                                <label for="service_id" class="form-label">{{ __('admin.service') }}</label>
                                <select id="service_id" class="form-select">
                                    <option value="" selected disabled>{{ __('admin.select_service') }}</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->service_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="created_for_type" class="form-label">{{ __('admin.created_for') }}</label>
                                <select id="created_for_type" class="form-select">
                                    <option value="" selected disabled>{{ __('admin.select_type') }}</option>
                                    <option value="salon">{{ __('admin.salon') }}</option>
                                    <option value="barber">{{ __('admin.barber') }}</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="time_duration" class="form-label">{{ __('admin.time_duration') }}</label>
                                <input type="text" id="time_duration" class="form-control" autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label">{{ __('admin.price') }}</label>
                                <input type="number" step="0.01" min="0" id="price" class="form-control"
                                    autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label for="discount" class="form-label">{{ __('admin.discount') }}</label>
                                <input type="number" step="0.01" min="0" id="discount" class="form-control"
                                    autocomplete="off">
                            </div>

                            <div class="service-price-actions">
                                <button class="btn btn-primary" id="submitBtn" type="submit">{{ __('admin.save') }}</button>
                                <button class="btn btn-light d-none" id="cancelBtn" type="button">{{ __('admin.cancel') }}</button>
                            </div>
                            
                        </form>
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

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            let table = $('#table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.service-prices.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'service_name'
                    },
                    {
                        data: 'created_for_type'
                    },
                    {
                        data: 'time_duration'
                    },
                    {
                        data: 'price'
                    },
                    {
                        data: 'discount'
                    },
                    {
                        data: 'created_by',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $('#table').on('draw.dt', function() {
                $('#table thead th').addClass('text-nowrap');
            });

            function success(msg) {
                $('#success-msg').removeClass('d-none').html(msg);
                setTimeout(() => $('#success-msg').addClass('d-none'), 3000);
            }

            function error(errors) {
                let html = '';

                if (errors) {
                    $.each(errors, function(k, v) {
                        html += '<div>' + v[0] + '</div>';
                    });
                } else {
                    html = '<div>' + @json(__('admin.something_went_wrong')) + '</div>';
                }

                $('#error-box').removeClass('d-none').html(html);
            }

            function resetForm() {
                $('#service-price-form-action')[0].reset();
                $('#id').val('');
                $('#form-title').text(@json(__('admin.add_service_price')));
                $('#submitBtn').text(@json(__('admin.save')));
                $('#cancelBtn').addClass('d-none');
                $('#error-box').addClass('d-none').html('');

                $('#service_id').val('');
                $('#created_for_type').val('');
                $('#time_duration').val('');
            }

            resetForm();

            $('#cancelBtn').on('click', function() {
                resetForm();
            });

            $('#service-price-form-action').submit(function(e) {
                e.preventDefault();

                $('#error-box').addClass('d-none').html('');

                let id = $('#id').val();
                let url = id ? "{{ route('admin.service-prices.update', ':id') }}".replace(':id', id) : "{{ route('admin.service-prices.store') }}";
                let method = id ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: {
                        service_id: $('#service_id').val(),
                        created_for_type: $('#created_for_type').val(),
                        time_duration: $('#time_duration').val(),
                        price: $('#price').val(),
                        discount: $('#discount').val()
                    },
                    success: function(res) {
                        table.ajax.reload(null, false);
                        success(res.message);
                        resetForm();
                    },
                    error: function(xhr) {
                        error(xhr.responseJSON?.errors);
                    }
                });
            });

            $(document).on('click', '.js-edit', function() {
                let id = $(this).data('id');

                $.get("{{ route('admin.service-prices.edit', ':id') }}".replace(':id', id), function(res) {
                    $('#id').val(res.id);
                    $('#service_id').val(res.service_id);
                    $('#created_for_type').val(res.created_for_type);
                    $('#time_duration').val(res.time_duration || '');
                    $('#price').val(res.price);
                    $('#discount').val(res.discount);

                    $('#form-title').text(@json(__('admin.edit_service_price')));
                    $('#submitBtn').text(@json(__('admin.update')));
                    $('#cancelBtn').removeClass('d-none');
                    $('#error-box').addClass('d-none').html('');
                });
            });

            $(document).on('click', '.js-delete', function() {
                let id = $(this).data('id');

                if (confirm(@json(__('admin.delete_service_price_confirm')))) {
                    $.ajax({
                        url: "{{ route('admin.service-prices.destroy', ':id') }}".replace(':id', id),
                        method: 'DELETE',
                        success: function(res) {
                            table.ajax.reload(null, false);
                            success(res.message);
                        }
                    });
                }
            });

        });
    </script>
@endpush
