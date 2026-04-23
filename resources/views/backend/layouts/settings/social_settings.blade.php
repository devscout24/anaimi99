@extends('backend.app')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Social Settings</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Social Settings</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title mb-0 flex-grow-1">Update Social Settings</h4>
                </div>

                <form action="{{ route('admin.social-settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="card-body">
                        <div class="row gy-4">

                            <!-- FACEBOOK -->
                            <div class="col-md-6">
                                <label class="form-label">Facebook Link</label>
                                <input type="text" name="facebook_link" class="form-control" value="{{ old('facebook_link', $setting->facebook_link ?? '') }}" placeholder="Enter Facebook link">
                                @error('facebook_link')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Facebook Icon</label>
                                <input type="file" name="facebook_icon" class="form-control dropify" data-default-file="{{ $setting && $setting->facebook_icon ? asset($setting->facebook_icon) : '' }}"
                                    data-allowed-file-extensions="jpg jpeg png svg">
                                @error('facebook_icon')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- INSTAGRAM -->
                            <div class="col-md-6">
                                <label class="form-label">Instagram Link</label>
                                <input type="text" name="instagram_link" class="form-control" value="{{ old('instagram_link', $setting->instagram_link ?? '') }}" placeholder="Enter Instagram link">
                                @error('instagram_link')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Instagram Icon</label>
                                <input type="file" name="instagram_icon" class="form-control dropify"
                                    data-default-file="{{ $setting && $setting->instagram_icon ? asset($setting->instagram_icon) : '' }}" data-allowed-file-extensions="jpg jpeg png svg">
                                @error('instagram_icon')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- TWITTER -->
                            <div class="col-md-6">
                                <label class="form-label">Twitter (X) Link</label>
                                <input type="text" name="twitter_link" class="form-control" value="{{ old('twitter_link', $setting->twitter_link ?? '') }}" placeholder="Enter Twitter link">
                                @error('twitter_link')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Twitter Icon</label>
                                <input type="file" name="twitter_icon" class="form-control dropify" data-default-file="{{ $setting && $setting->twitter_icon ? asset($setting->twitter_icon) : '' }}"
                                    data-allowed-file-extensions="jpg jpeg png svg">
                                @error('twitter_icon')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- TIKTOK -->
                            <div class="col-md-6">
                                <label class="form-label">TikTok Link</label>
                                <input type="text" name="tiktok_link" class="form-control" value="{{ old('tiktok_link', $setting->tiktok_link ?? '') }}" placeholder="Enter TikTok link">
                                @error('tiktok_link')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">TikTok Icon</label>
                                <input type="file" name="tiktok_icon" class="form-control dropify" data-default-file="{{ $setting && $setting->tiktok_icon ? asset($setting->tiktok_icon) : '' }}"
                                    data-allowed-file-extensions="jpg jpeg png svg">
                                @error('tiktok_icon')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- WHATSAPP -->
                            <div class="col-md-6">
                                <label class="form-label">WhatsApp Link</label>
                                <input type="text" name="whatsapp_link" class="form-control" value="{{ old('whatsapp_link', $setting->whatsapp_link ?? '') }}" placeholder="Enter WhatsApp link">
                                @error('whatsapp_link')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">WhatsApp Icon</label>
                                <input type="file" name="whatsapp_icon" class="form-control dropify" data-default-file="{{ $setting && $setting->whatsapp_icon ? asset($setting->whatsapp_icon) : '' }}"
                                    data-allowed-file-extensions="jpg jpeg png svg">
                                @error('whatsapp_icon')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- LINKEDIN -->
                            <div class="col-md-6">
                                <label class="form-label">LinkedIn Link</label>
                                <input type="text" name="linkedin_link" class="form-control" value="{{ old('linkedin_link', $setting->linkedin_link ?? '') }}" placeholder="Enter LinkedIn link">
                                @error('linkedin_link')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">LinkedIn Icon</label>
                                <input type="file" name="linkedin_icon" class="form-control dropify"
                                    data-default-file="{{ $setting && $setting->linkedin_icon ? asset($setting->linkedin_icon) : '' }}" data-allowed-file-extensions="jpg jpeg png svg">
                                @error('linkedin_icon')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- TELEGRAM -->
                            <div class="col-md-6">
                                <label class="form-label">Telegram Link</label>
                                <input type="text" name="telegram_link" class="form-control" value="{{ old('telegram_link', $setting->telegram_link ?? '') }}" placeholder="Enter Telegram link">
                                @error('telegram_link')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Telegram Icon</label>
                                <input type="file" name="telegram_icon" class="form-control dropify"
                                    data-default-file="{{ $setting && $setting->telegram_icon ? asset($setting->telegram_icon) : '' }}" data-allowed-file-extensions="jpg jpeg png svg">
                                @error('telegram_icon')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- YOUTUBE -->
                            <div class="col-md-6">
                                <label class="form-label">YouTube Link</label>
                                <input type="text" name="youtube_link" class="form-control" value="{{ old('youtube_link', $setting->youtube_link ?? '') }}" placeholder="Enter YouTube link">
                                @error('youtube_link')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">YouTube Icon</label>
                                <input type="file" name="youtube_icon" class="form-control dropify"
                                    data-default-file="{{ $setting && $setting->youtube_icon ? asset($setting->youtube_icon) : '' }}" data-allowed-file-extensions="jpg jpeg png svg">
                                @error('youtube_icon')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- SUBMIT BUTTON -->
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Update Settings</button>
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
            $('.dropify').dropify({
                messages: {
                    'default': 'Drag or click',
                    'replace': 'Drag to replace',
                    'remove': 'Remove',
                    'error': 'Something went wrong.'
                }
            });
        });
    </script>
@endpush
