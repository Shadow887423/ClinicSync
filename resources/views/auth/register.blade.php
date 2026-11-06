@extends('layouts.app')

@section('title', 'Patient Registration — CLINICSYNC')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <div class="card card-custom p-4 p-md-5 border-0 shadow-lg rounded-4">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3 mb-2" style="background-color: var(--teal-light); width: 64px; height: 64px;">
                            <i class="bi bi-person-plus-fill fs-2" style="color: var(--secondary-teal);"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Create Patient Account</h3>
                        <p class="text-muted small">Register to book dental appointments and receive automated SMS reminders</p>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger py-2 small mb-4">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('register') }}" method="POST">
                        @csrf
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Full Legal Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. John Doe" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Mobile Phone (for SMS Alerts) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-phone"></i></span>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+15551234567 or 09171234567" required>
                                </div>
                                <div class="form-text small" style="font-size: 0.75rem;">All appointment confirmations will be sent via SMS to this number.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="name@example.com" required>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Date of Birth</label>
                                <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">Select Gender (Optional)</option>
                                    <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                    <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Residential Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address') }}" placeholder="Street, City, Province/State">
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="At least 8 characters" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-check-circle"></i></span>
                                    <input type="password" name="password_confirmation" class="form-control" placeholder="Re-type password" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-teal w-100 rounded-pill py-2 fw-semibold">
                            <i class="bi bi-person-check me-1"></i> Complete Patient Registration
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="small text-muted mb-0">
                            Already registered? 
                            <a href="{{ route('login') }}" class="fw-bold" style="color: var(--secondary-teal);">Sign In Here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
