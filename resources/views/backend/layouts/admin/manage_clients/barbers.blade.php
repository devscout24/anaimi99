@extends('backend.app')
@section('title', 'Manage Barbers')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Manage Barbers</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                        <li class="breadcrumb-item active">Barbers List</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Barbers List</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap w-100" id="barberTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Action</th>
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
                    <h5 class="modal-title">Client Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailsBody">
                    <!-- Dynamic Content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                updateStatus($(this).data('id'), 'approved', 'Are you sure you want to approve/unblock this user?');
            });

            $(document).on('click', '.cancelUser', function() {
                updateStatus($(this).data('id'), 'cancel', 'Are you sure you want to block/cancel this user?');
            });

            $(document).on('click', '.viewDetails', function() {
                var id = $(this).data('id');
                $.get("{{ url('admin/manage-clients/details') }}/" + id, function(data) {
                    var html = '<p><strong>Name:</strong> ' + data.name + '</p>' +
                               '<p><strong>Email:</strong> ' + data.email + '</p>' +
                               '<p><strong>Phone:</strong> ' + (data.phone ?? 'N/A') + '</p>' +
                               '<p><strong>Address:</strong> ' + (data.address ?? 'N/A') + '</p>';
                    $('#detailsBody').html(html);
                    $('#detailsModal').modal('show');
                });
            });

            function updateStatus(id, status, message) {
                Swal.fire({
                    title: 'Status Update',
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, update it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("{{ route('admin.manage.update-status') }}", {
                            _token: "{{ csrf_token() }}",
                            id: id,
                            status: status
                        }, function(data) {
                            table.ajax.reload();
                            Swal.fire(
                                'Updated!',
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
