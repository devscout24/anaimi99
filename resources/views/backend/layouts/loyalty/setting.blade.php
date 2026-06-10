@extends('backend.app')

@section('title', __('admin.loyalty_points_setting'))

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">{{ __('admin.loyalty_points_setting') }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('admin.dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('admin.loyalty_points') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">{{ __('admin.global_per_booking_loyalty') }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.loyalty-setting.update') }}" method="POST">
                        @csrf
                        @method('PUT')



                       <div class="mb-3">
                            <label for="per_booking_loyality" class="form-label">{{ __('admin.services_for_barber_loyalty') }}</label>
                            <div class="input-group">
                               <select name="service_id" id="service_id" class="form-control">
                                    <option value="">{{ __('admin.select_a_service') }}</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}" {{  $setting->service_id == $service->id ? 'selected' : '' }}>
                                            {{ $service->service_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('service_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>










                        <div class="mb-3">
                            <label for="per_booking_loyality" class="form-label">{{ __('admin.loyalty_points_per_booking') }}</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="per_booking_loyality" id="per_booking_loyality"
                                    class="form-control @error('per_booking_loyality') is-invalid @enderror"
                                    value="{{ old('per_booking_loyality', $setting->per_booking_loyality ?? 0) }}" required>
                                <span class="input-group-text">{{ __('admin.points') }}</span>
                            </div>
                            @error('per_booking_loyality')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                         <div class="mb-3">
                            <label for="service_reach_loyality" class="form-label">{{ __('admin.service_reach_loyalty') }}</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="service_reach_loyality" id="service_reach_loyality"
                                    class="form-control @error('service_reach_loyality') is-invalid @enderror"
                                    value="{{ old('service_reach_loyality', $setting->service_reach_loyality ?? 0) }}" required>
                                <span class="input-group-text">{{ __('admin.points') }}</span>
                            </div>
                            @error('service_reach_loyality')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>


                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">{{ __('admin.update_setting') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
