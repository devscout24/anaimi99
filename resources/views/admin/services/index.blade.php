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
                    <h5 class="mb-0">Service List</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered w-100" id="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Service Name</th>
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
                    <h5 class="mb-0" id="form-title">Add Service</h5>
                </div>

                <div class="card-body">
                    <div class="alert alert-danger d-none" id="error-box"></div>

                    <form id="form">
                        @csrf
                        <input type="hidden" id="id">

                        <div class="mb-3">
                            <label for="service_name" class="form-label">Service Name</label>
                            <input type="text" id="service_name" class="form-control" autocomplete="off">
                        </div>

                        <div class="d-flex gap-2">
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
$(function () {

    // CSRF FIX (IMPORTANT)
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    });

    let table = $('#table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.services.index') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'service_name' },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    function success(msg) {
        $('#success-msg').removeClass('d-none').html(msg);
        setTimeout(() => $('#success-msg').addClass('d-none'), 3000);
    }

    function error(errors) {
        let html = '';

        if (errors) {
            $.each(errors, function (k, v) {
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
        $('#form-title').text('Add Service');
        $('#submitBtn').text('Save');
        $('#cancelBtn').addClass('d-none');
        $('#error-box').addClass('d-none').html('');
    }

    resetForm();

    $('#cancelBtn').on('click', function () {
        resetForm();
    });

    // SUBMIT
    $('#form').submit(function (e) {
        e.preventDefault();

        $('#error-box').addClass('d-none').html('');

        let id = $('#id').val();
        let url = id ? '/admin/services/update/' + id : '/admin/services/store';
        let method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: {
                service_name: $('#service_name').val()
            },
            success: function (res) {
                table.ajax.reload(null, false);
                success(res.message);
                resetForm();
            },
            error: function (xhr) {
                error(xhr.responseJSON?.errors);
            }
        });
    });

    // EDIT
    $(document).on('click', '.js-edit', function () {
        let id = $(this).data('id');

        $.get('/admin/services/edit/' + id, function (res) {
            $('#id').val(res.id);
            $('#service_name').val(res.service_name);
            $('#form-title').text('Edit Service');
            $('#submitBtn').text('Update');
            $('#cancelBtn').removeClass('d-none');
            $('#error-box').addClass('d-none').html('');
        });
    });

    // DELETE
    $(document).on('click', '.js-delete', function () {
        let id = $(this).data('id');

        if (confirm('Delete this service?')) {
            $.ajax({
                url: '/admin/services/destroy/' + id,
                method: 'DELETE',
                success: function (res) {
                    table.ajax.reload(null, false);
                    success(res.message);
                }
            });
        }
    });

});
</script>
@endpush