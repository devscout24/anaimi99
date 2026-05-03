@extends('backend.app')

@section('title', 'Loyalty Points Setting')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Loyalty Points Setting</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Loyalty Points</li>
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
                    <h4 class="card-title mb-0">Global Per Booking Loyalty</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.loyalty-setting.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="per_booking_loyality" class="form-label">Loyalty Points (per booking)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="per_booking_loyality" id="per_booking_loyality" 
                                    class="form-control @error('per_booking_loyality') is-invalid @enderror" 
                                    value="{{ old('per_booking_loyality', $setting->per_booking_loyality ?? 0) }}" required>
                                <span class="input-group-text">Points</span>
                            </div>
                            @error('per_booking_loyality')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Update Setting</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
