@extends('backend.app')
@section('title', __('admin.manage_barbers'))
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">{{ __('admin.manage_barbers') }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">{{ __('admin.dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('admin.barbers_list') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">{{ __('admin.barbers_list') }}</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap w-100" id="barberTable">
                            <thead>
                                <tr>
                                    <th>{{ __('admin.no') }}</th>
                                    <th>{{ __('admin.name') }}</th>
                                    <th>{{ __('admin.email') }}</th>
                                    <th>{{ __('admin.phone') }}</th>
                                    <th>{{ __('admin.status') }}</th>
                                    <th>{{ __('admin.action') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('admin.client_details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailsBody">
                    <!-- Dynamic Content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('admin.close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            var table = $('#barberTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.manage.barbers') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                    {data: 'name', name: 'name'},
                    {data: 'email', name: 'email'},
                    {data: 'phone', name: 'phone'},
                    {data: 'status_label', name: 'status_label'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ]
            });

            $(document).on('click', '.approveUser', function() {
                updateStatus($(this).data('id'), 'approved', @json(__('admin.approve_user_confirm')));
            });

            $(document).on('click', '.cancelUser', function() {
                updateStatus($(this).data('id'), 'cancel', @json(__('admin.block_user_confirm')));
            });

            $(document).on('click', '.viewDetails', function() {
                var id = $(this).data('id');
                $.get("{{ url('admin/manage-clients/details') }}/" + id, function(data) {
                    var html = '<p><strong>' + @json(__('admin.name')) + ':</strong> ' + data.name + '</p>' +
                               '<p><strong>' + @json(__('admin.email')) + ':</strong> ' + data.email + '</p>' +
                               '<p><strong>' + @json(__('admin.phone')) + ':</strong> ' + (data.phone ?? @json(__('admin.not_available'))) + '</p>' +
                               '<p><strong>' + @json(__('admin.address')) + ':</strong> ' + (data.address ?? @json(__('admin.not_available'))) + '</p>';
                    $('#detailsBody').html(html);
                    $('#detailsModal').modal('show');
                });
            });

            function updateStatus(id, status, message) {
                Swal.fire({
                    title: @json(__('admin.status_update')),
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: @json(__('admin.yes_update_it'))
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("{{ route('admin.manage.update-status') }}", {
                            _token: "{{ csrf_token() }}",
                            id: id,
                            status: status
                        }, function(data) {
                            table.ajax.reload();
                            Swal.fire(
                                @json(__('admin.updated')),
                                data.success,
                                'success'
                            );
                        });
                    }
                });
            }
        });
    </script>
@endpush
