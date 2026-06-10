@extends('backend.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="container-fluid">
    <div class="alert alert-success d-none" id="success-msg"></div>
    <div class="alert alert-danger d-none" id="error-box"></div>

    <div class="row g-3 align-items-start">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.availability_days_list') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered w-100" id="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('admin.day') }}</th>
                                    <th>{{ __('admin.status') }}</th>
                                    <th>{{ __('admin.action') }}</th>
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
                    <h5 class="mb-0">{{ __('admin.add_availability_day') }}</h5>
                </div>
                <div class="card-body">
                    <form id="form">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="day_name">{{ __('admin.day_name') }}</label>
                            <select class="form-select" id="day_name" name="day_name" required>
                                <option value="">{{ __('admin.select_day') }}</option>
                                @foreach (\App\Models\AvailablityDay::DAYS as $day)
                                    <option value="{{ $day }}" {{ old('day_name') === $day ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">{{ __('admin.active') }}</label>
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
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
$(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    });

    let table = $('#table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.availability-days.index') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'day_name' },
            { data: 'status', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false },
        ]
    });

    function success(msg) {
        $('#success-msg').removeClass('d-none').html(msg);
        $('#error-box').addClass('d-none').html('');
        setTimeout(() => $('#success-msg').addClass('d-none'), 3000);
    }

    function error(errors, fallback = @json(__('admin.something_went_wrong'))) {
        let html = '';

        if (errors) {
            $.each(errors, function (k, v) {
                html += '<div>' + v[0] + '</div>';
            });
        } else {
            html = '<div>' + fallback + '</div>';
        }

        $('#error-box').removeClass('d-none').html(html);
    }

    $('#form').submit(function (e) {
        e.preventDefault();

        $('#error-box').addClass('d-none').html('');

        $.ajax({
            url: "{{ route('admin.availability-days.store') }}",
            method: 'POST',
            data: {
                day_name: $('#day_name').val(),
                is_active: $('#is_active').is(':checked') ? 1 : 0,
            },
            success: function (res) {
                table.ajax.reload(null, false);
                success(res.message);
            },
            error: function (xhr) {
                error(xhr.responseJSON?.errors, xhr.responseJSON?.message);
            }
        });
    });

    $(document).on('click', '.js-delete', function () {
        let id = $(this).data('id');

        if (confirm(@json(__('admin.delete_availability_day_confirm')))) {
            $.ajax({
                url: "{{ route('admin.availability-days.destroy', ':id') }}".replace(':id', id),
                method: 'DELETE',
                success: function (res) {
                    table.ajax.reload(null, false);
                    success(res.message);
                },
                error: function (xhr) {
                    error(xhr.responseJSON?.errors, xhr.responseJSON?.message);
                }
            });
        }
    });

    $(document).on('click', '.js-toggle-status', function () {
        let id = $(this).data('id');

        $.ajax({
            url: "{{ route('admin.availability-days.status', ':id') }}".replace(':id', id),
            method: 'PATCH',
            success: function (res) {
                table.ajax.reload(null, false);
                success(res.message);
            },
            error: function (xhr) {
                error(xhr.responseJSON?.errors, xhr.responseJSON?.message);
            }
        });
    });

});
</script>
@endpush
