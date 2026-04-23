@extends('backend.app')

@section('title', 'Stripe Settings')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                <h4 class="mb-sm-0">Stripe Settings</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Stripe Settings</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h4 class="card-title mb-0 flex-grow-1">Update Stripe Settings</h4>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.stripe-settings.update') }}">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="stripe_public_key" class="form-label">Stripe Public Key</label>
                            <input type="text" name="stripe_public_key" id="stripe_public_key" class="form-control @error('stripe_public_key') is-invalid @enderror"
                                value="{{ env('STRIPE_PUBLIC_KEY') }}" placeholder="Enter Stripe Public Key" required>
                            @error('stripe_public_key')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="stripe_secret_key" class="form-label">Stripe Secret Key</label>
                            <input type="text" name="stripe_secret_key" id="stripe_secret_key" class="form-control @error('stripe_secret_key') is-invalid @enderror"
                                value="{{ env('STRIPE_SECRET_KEY') }}" placeholder="Enter Stripe Secret Key" required>
                            @error('stripe_secret_key')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Update Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
