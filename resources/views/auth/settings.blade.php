@extends('layouts.dashboard')

@section('title', 'Security Settings')
@section('page-title', 'Account Security & Password')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card card-custom p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock-fill text-primary me-2"></i> Update Password</h5>
            <p class="text-muted small mb-4">Ensure your account uses a strong password to protect confidential health and scheduling details.</p>

            <form action="{{ route('settings.update') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required placeholder="Enter existing password">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">New Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Minimum 8 characters">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required placeholder="Confirm new password">
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-teal rounded-pill px-4">
                        <i class="bi bi-check2-circle me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
