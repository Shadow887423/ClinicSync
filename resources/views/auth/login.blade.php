@extends('layouts.app')

@section('title', 'Sign In — CLINICSYNC')

@section('content')
<section class="py-5" style="min-height: 80vh; display: flex; align-items: center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-8">
                <div class="card card-custom p-4 p-md-5 border-0 shadow-lg rounded-4">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3 mb-2" style="background-color: var(--teal-light); width: 64px; height: 64px;">
                            <i class="bi bi-shield-lock-fill fs-2" style="color: var(--secondary-teal);"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Welcome Back</h3>
                        <p class="text-muted small">Sign in to your CLINICSYNC portal</p>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger py-2 small mb-3">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST" id="loginForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="name@example.com">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold mb-0">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label small text-muted" for="remember">Keep me logged in</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-teal w-100 rounded-pill py-2 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Portal
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="small text-muted mb-0">
                            Don't have a patient account yet? 
                            <a href="{{ route('register') }}" class="fw-bold" style="color: var(--secondary-teal);">Register Online</a>
                        </p>
                    </div>

                    <!-- Academic Demonstration Accounts Box -->
                    <div class="mt-4 pt-3 bg-light rounded-3 p-3 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-bold text-dark"><i class="bi bi-person-badge-fill me-1 text-primary"></i> Quick Demo Accounts:</span>
                            <span class="badge bg-secondary" style="font-size: 0.65rem;">One-Click Fill</span>
                        </div>
                        <div class="d-grid gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary text-start py-1 px-2 demo-btn" data-email="patient@clinicsync.com" data-pass="password123">
                                <span class="badge bg-success me-1">Patient</span> John Doe
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary text-start py-1 px-2 demo-btn" data-email="staff@clinicsync.com" data-pass="password123">
                                <span class="badge bg-info text-dark me-1">Staff</span> Receptionist Emily
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary text-start py-1 px-2 demo-btn" data-email="dr.thorne@clinicsync.com" data-pass="password123">
                                <span class="badge bg-primary me-1">Dentist</span> Dr. Aris Thorne
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary text-start py-1 px-2 demo-btn" data-email="admin@clinicsync.com" data-pass="password123">
                                <span class="badge bg-danger me-1">Admin</span> Dr. Arthur Bennett
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
    document.querySelectorAll('.demo-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('email').value = this.dataset.email;
            document.getElementById('password').value = this.dataset.pass;
        });
    });
</script>
@endsection
