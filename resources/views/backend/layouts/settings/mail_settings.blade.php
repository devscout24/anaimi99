@extends('backend.app')

@section('title', 'Mail Setting')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Mail Settings</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Mail Settings</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Update Mail Settings</h4>
                </div><!-- end card header -->

                <div class="card-body">
                    <form class="forms-sample" action="{{ route('admin.mail-settings.update') }}" method="POST">
                        @csrf

                        <div class="form-group row mb-3">
                            <div class="col">
                                <label class="form-lable">MAIL MAILER</label>
                                <input type="text" class="form-control form-control-md border-left-0 @error('mail_mailer') is-invalid @enderror" placeholder="MAIL MAILER" name="mail_mailer"
                                    value="{{ env('MAIL_MAILER') }}" required>
                                @error('mail_mailer')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="col">
                                <label class="form-lable">MAIL HOST</label>
                                <input type="text" class="form-control form-control-md border-left-0 @error('mail_host') is-invalid @enderror" placeholder="MAIL HOST" name="mail_host"
                                    value="{{ env('MAIL_HOST') }}" required>
                                @error('mail_host')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <div class="col">
                                <label class="form-lable">MAIL PORT</label>
                                <input type="text" class="form-control form-control-md border-left-0 @error('mail_port') is-invalid @enderror" placeholder="MAIL PORT" name="mail_port"
                                    value="{{ env('MAIL_PORT') }}" required>
                                @error('mail_port')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="col">
                                <label class="form-lable">MAIL USERNAME</label>
                                <input type="text" class="form-control form-control-md border-left-0 @error('mail_username') is-invalid @enderror" placeholder="MAIL USERNAME" name="mail_username"
                                    value="{{ env('MAIL_USERNAME') }}" required>
                                @error('mail_username')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <div class="col">
                                <label class="form-lable">MAIL PASSWORD</label>
                                <input type="text" class="form-control form-control-md border-left-0 @error('mail_password') is-invalid @enderror" placeholder="MAIL PASSWORD" name="mail_password"
                                    value="{{ env('MAIL_PASSWORD') }}" required>
                                @error('mail_password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="col">
                                <label class="form-lable">MAIL ENCRYPTION</label>
                                <input type="text" class="form-control form-control-md border-left-0 @error('mail_encryption') is-invalid @enderror" placeholder="MAIL ENCRYPTION" name="mail_encryption"
                                    value="{{ env('MAIL_ENCRYPTION') }}">
                                @error('mail_encryption')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <div class="col-6">
                                <label class="form-lable">MAIL FROM ADDRESS</label>
                                <input type="text" class="form-control form-control-md border-left-0 @error('mail_from_address') is-invalid @enderror" placeholder="MAIL FROM ADDRESS"
                                    name="mail_from_address" value="{{ env('MAIL_FROM_ADDRESS') }}" required>
                                @error('mail_from_address')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary me-2">Update Settings</button>
                    </form>
                </div>
            </div>
        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection

@push('scripts')
@endpush
