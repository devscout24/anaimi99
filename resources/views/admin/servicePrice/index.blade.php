@extends('backend.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
    <div>

        <div class="alert alert-success d-none" id="success-msg"></div>

        <div class="row g-3 align-items-start">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Service Prices</h5>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered w-100" id="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Service</th>
                                        <th>Type</th>
                                        <th>Time</th>
                                        <th>Price</th>
                                        <th>Discount</th>
                                        <th>Created By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0" id="form-title">Add Service Price</h5>
                    </div>

                    <div class="card-body">
                        <div class="alert alert-danger d-none" id="error-box"></div>

                        <form id="form">
                            @csrf
                            <input type="hidden" id="id">

                            <div class="mb-3">
                                <label for="service_id" class="form-label">Service</label>
                                <select id="service_id" class="form-select">
                                    <option value="" selected disabled>Select service</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->service_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="created_for_type" class="form-label">Created For</label>
                                <select id="created_for_type" class="form-select">
                                    <option value="" selected disabled>Select type</option>
                                    <option value="salon">Salon</option>
                                    <option value="barber">Barber</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="time_duration" class="form-label">Time Duration</label>
                                <input type="text" id="time_duration" class="form-control" autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" step="0.01" min="0" id="price" class="form-control"
                                    autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label for="discount" class="form-label">Discount</label>
                                <input type="number" step="0.01" min="0" id="discount" class="form-control"
                                    autocomplete="off">
                            </div>

                            <div class="gap-2 d-flex">
                                <button class="btn btn-primary" id="submitBtn" type="submit">Save</button>
                                <button class="btn btn-light d-none" id="cancelBtn" type="button">Cancel</button>
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
                    html = '<div>Something went wrong</div>';
                }

                $('#error-box').removeClass('d-none').html(html);
            }

            function resetForm() {
                $('#form')[0].reset();
                $('#id').val('');
                $('#form-title').text('Add Service Price');
                $('#submitBtn').text('Save');
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

            $('#form').submit(function(e) {
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

                    $('#form-title').text('Edit Service Price');
                    $('#submitBtn').text('Update');
                    $('#cancelBtn').removeClass('d-none');
                    $('#error-box').addClass('d-none').html('');
                });
            });

            $(document).on('click', '.js-delete', function() {
                let id = $(this).data('id');

                if (confirm('Delete this service price?')) {
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
