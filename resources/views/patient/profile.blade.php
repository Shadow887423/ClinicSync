@extends('layouts.dashboard')

@section('title', 'Patient Profile')
@section('page-title', 'Patient Profile & SMS Contact')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-custom p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-3" style="width: 64px; height: 64px; background-color: var(--primary-navy) !important;">
                    {{ strtoupper(substr($patient->name, 0, 1)) }}
                </div>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">{{ $patient->name }}</h4>
                    <div class="text-muted small">Registered Patient &bull; {{ $patient->email }}</div>
                </div>
            </div>

            <form action="{{ route('patient.profile.update') }}" method="POST">
                @csrf
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Full Legal Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $patient->name) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            Mobile Phone for SMS Notifications <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-phone"></i></span>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $patient->phone) }}" placeholder="+15551234567" required>
                        </div>
                        <div class="form-text small" style="font-size: 0.75rem;">
                            <i class="bi bi-shield-check text-success"></i> Automated SMS reminders and confirmations will be sent to this phone number.
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email Address (Login Username)</label>
                        <input type="email" class="form-control bg-light" value="{{ $patient->email }}" disabled readonly>
                        <div class="form-text small" style="font-size: 0.75rem;">To change your login email, contact reception.</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Date of Birth</label>
                        <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $patient->birth_date ? $patient->birth_date->format('Y-m-d') : '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">Select</option>
                            <option value="Male" {{ old('gender', $patient->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender', $patient->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender', $patient->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Residential Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Street, City, State/Province">{{ old('address', $patient->address) }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-teal rounded-pill px-4">
                        <i class="bi bi-check2-circle me-1"></i> Update Profile Information
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
