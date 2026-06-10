@extends('backend.app')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">{{ __('admin.system_settings') }}</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('admin.dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('admin.settings') }}</li>
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
                    <h4 class="card-title mb-0 flex-grow-1">{{ __('admin.update_system_settings') }}</h4>
                </div><!-- end card header -->

                <form action="{{ route('admin.system-settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="row gy-4">

                            {{-- Logo --}}
                            <div class="col-xxl-4 col-md-6">
                                <div>
                                    <label for="logo" class="form-label">{{ __('admin.logo') }}</label>
                                    <input type="file" name="logo" id="logo" class="form-control dropify" data-allowed-file-extensions="jpg jpeg png svg"
                                        data-default-file="{{ $setting && $setting->logo ? asset($setting->logo) : '' }}">
                                    <input type="hidden" name="remove_logo" id="remove_logo_hidden" value="0">
                                    @error('logo')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Mini Logo --}}
                            <div class="col-xxl-4 col-md-6">
                                <div>
                                    <label for="mini_logo" class="form-label">{{ __('admin.mini_logo') }}</label>
                                    <input type="file" name="mini_logo" id="mini_logo" class="form-control dropify" data-allowed-file-extensions="jpg jpeg png svg"
                                        data-default-file="{{ $setting && $setting->mini_logo ? asset($setting->mini_logo) : '' }}">
                                    <input type="hidden" name="remove_mini_logo" id="remove_mini_logo_hidden" value="0">
                                    @error('mini_logo')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Favicon --}}
                            <div class="col-xxl-4 col-md-6">
                                <div>
                                    <label for="favicon" class="form-label">{{ __('admin.favicon') }}</label>
                                    <input type="file" name="favicon" id="favicon" class="form-control dropify" data-allowed-file-extensions="jpg jpeg png svg ico"
                                        data-default-file="{{ $setting && $setting->favicon ? asset($setting->favicon) : '' }}">
                                    <input type="hidden" name="remove_favicon" id="remove_favicon_hidden" value="0">
                                    @error('favicon')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- System Title --}}
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label for="system_title" class="form-label">{{ __('admin.system_title') }}</label>
                                    <input type="text" name="system_title" id="system_title" class="form-control" placeholder="{{ __('admin.enter_system_title') }}"
                                        value="{{ old('system_title', $setting->system_title ?? '') }}">
                                    @error('system_title')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Company Name --}}
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label for="company_name" class="form-label">{{ __('admin.company_name') }}</label>
                                    <input type="text" name="company_name" id="company_name" class="form-control" placeholder="{{ __('admin.enter_company_name') }}"
                                        value="{{ old('company_name', $setting->company_name ?? '') }}">
                                    @error('company_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Tag Line --}}
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label for="tag_line" class="form-label">{{ __('admin.tag_line') }}</label>
                                    <input type="text" name="tag_line" id="tag_line" class="form-control" placeholder="{{ __('admin.enter_tag_line') }}" value="{{ old('tag_line', $setting->tag_line ?? '') }}">
                                    @error('tag_line')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Phone Number --}}
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label for="phone_number" class="form-label">{{ __('admin.phone_number') }}</label>
                                    <input type="text" name="phone_number" id="phone_number" class="form-control" placeholder="{{ __('admin.enter_phone_number') }}"
                                        value="{{ old('phone_number', $setting->phone_number ?? '') }}">
                                    @error('phone_number')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- WhatsApp Number --}}
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label for="whatsapp_number" class="form-label">{{ __('admin.whatsapp_number') }}</label>
                                    <input type="text" name="whatsapp_number" id="whatsapp_number" class="form-control" placeholder="{{ __('admin.enter_whatsapp_number') }}"
                                        value="{{ old('whatsapp_number', $setting->whatsapp_number ?? '') }}">
                                    @error('whatsapp_number')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Email --}}
                            <div class="col-xxl-6 col-md-6">
                                <div>
                                    <label for="email" class="form-label">{{ __('admin.email') }}</label>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="{{ __('admin.enter_email_address') }}" value="{{ old('email', $setting->email ?? '') }}">
                                    @error('email')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Copyright Text --}}
                            <div class="col-xxl-12 col-md-12">
                                <div>
                                    <label for="copyright_text" class="form-label">{{ __('admin.copyright_text') }}</label>
                                    <input type="text" name="copyright_text" id="copyright_text" class="form-control" placeholder="{{ __('admin.enter_copyright_text') }}"
                                        value="{{ old('copyright_text', $setting->copyright_text ?? '') }}">
                                    @error('copyright_text')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <div class="col-xxl-12 col-md-12">
                                <button type="submit" class="btn btn-primary">{{ __('admin.update_settings') }}</button>
                            </div>

                        </div>
                    </div>
                </form>

            </div>
        </div>
        <!--end col-->
    </div>
    <!--end row-->
@endsection

{{-- Push the script --}}
@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Dropify
            $('.dropify').dropify({
                messages: {
                    'default': @json(__('admin.drag_or_click')),
                    'replace': @json(__('admin.drag_to_replace')),
                    'remove': @json(__('admin.remove')),
                    'error': @json(__('admin.dropify_error'))
                }
            });

            // Detect Dropify remove
            $('.dropify').on('dropify.beforeClear', function(event, element) {
                let name = $(this).attr('name'); // logo, mini_logo, favicon
                $('#remove_' + name + '_hidden').val(1);
            });
        });
    </script>
@endpush
