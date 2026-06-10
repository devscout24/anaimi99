@extends('backend.app')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">{{ __('admin.profile_settings') }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('admin.dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('admin.edit_profile') }}</li>
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
                    <h4 class="card-title mb-0 flex-grow-1">{{ __('admin.edit_profile') }}</h4>
                </div><!-- end card header -->

                <form action="{{ route('admin.profile-settings.update', $user->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="row gy-4">

                            {{-- Profile Image --}}
                            <div class="col-12">
                                <label for="avatar" class="form-label">{{ __('admin.profile_image') }}</label>
                                <input type="file" name="avatar" id="avatar" class="form-control dropify" data-default-file="{{ $user->avatar ? asset($user->avatar) : '' }}"
                                    data-allowed-file-extensions="jpg jpeg png gif">
                                <input type="hidden" name="remove_avatar" id="remove_avatar" value="0">
                                @error('avatar')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Full Name --}}
                            <div class="col-12">
                                <label for="name" class="form-label">{{ __('admin.full_name') }}</label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}">
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Username --}}
                            <div class="col-12">
                                <label for="username" class="form-label">{{ __('admin.username') }}</label>
                                <input type="text" name="username" id="username" class="form-control" value="{{ old('username', $user->username) }}">
                                @error('username')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Email --}}
                            <div class="col-12">
                                <label for="email" class="form-label">{{ __('admin.email') }}</label>
                                <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}">
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Password --}}
                            <div class="col-12">
                                <label for="password" class="form-label">{{ __('admin.password_keep_current') }}</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="{{ __('admin.enter_new_password') }}">
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Confirm Password --}}
                            <div class="col-12">
                                <label for="password_confirmation" class="form-label">{{ __('admin.confirm_password') }}</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="{{ __('admin.confirm_password') }}">
                                @error('password_confirmation')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Submit --}}
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">{{ __('admin.update_profile') }}</button>
                            </div>

                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let drEvent = $('.dropify').dropify({
                messages: {
                    'default': @json(__('admin.drag_or_click')),
                    'replace': @json(__('admin.drag_to_replace')),
                    'remove': @json(__('admin.remove')),
                    'error': @json(__('admin.dropify_error'))
                }
            });

            drEvent.on('dropify.afterClear', function() {
                $('#remove_avatar').val(1);
            });
        });
    </script>
@endpush
