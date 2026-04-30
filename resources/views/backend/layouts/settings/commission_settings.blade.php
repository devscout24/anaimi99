@extends('backend.app')

@section('title', 'Commission Settings')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Commission Settings</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Commission Settings</li>
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

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title mb-0 flex-grow-1">Update Commission Rates</h4>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.commission-settings.update') }}">
                        @csrf

                        {{-- Salon Commission --}}
                        <div class="card border mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="ri-store-2-line me-2"></i>Salon Commission</h5>
                                <small class="text-muted">Applies to: Salon Auto, Salon Barber, Custom bookings</small>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-3">
                                    <label for="salon_commission_rate" class="form-label">Commission Rate (%)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="salon_commission_rate" id="salon_commission_rate"
                                            class="form-control @error('salon_commission_rate') is-invalid @enderror"
                                            value="{{ old('salon_commission_rate', $salonCommission->commission_rate ?? 0) }}"
                                            placeholder="e.g. 15" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('salon_commission_rate')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group mb-0">
                                    <label for="salon_description" class="form-label">Description (optional)</label>
                                    <input type="text" name="salon_description" id="salon_description"
                                        class="form-control"
                                        value="{{ old('salon_description', $salonCommission->description ?? '') }}"
                                        placeholder="e.g. Standard salon commission rate">
                                </div>
                            </div>
                        </div>

                        {{-- Home Barber Commission --}}
                        <div class="card border mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="ri-scissors-line me-2"></i>Home Barber Commission</h5>
                                <small class="text-muted">Applies to: Home Barber, As Soon As Possible bookings</small>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-3">
                                    <label for="home_barber_commission_rate" class="form-label">Commission Rate (%)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="home_barber_commission_rate" id="home_barber_commission_rate"
                                            class="form-control @error('home_barber_commission_rate') is-invalid @enderror"
                                            value="{{ old('home_barber_commission_rate', $homeBarberCommission->commission_rate ?? 0) }}"
                                            placeholder="e.g. 10" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('home_barber_commission_rate')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group mb-0">
                                    <label for="home_barber_description" class="form-label">Description (optional)</label>
                                    <input type="text" name="home_barber_description" id="home_barber_description"
                                        class="form-control"
                                        value="{{ old('home_barber_description', $homeBarberCommission->description ?? '') }}"
                                        placeholder="e.g. Standard home barber commission rate">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Update Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
