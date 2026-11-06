@extends('layouts.app')

@section('title', '403 Forbidden — Security Protection — CLINICSYNC')

@section('content')
<section class="py-5" style="min-height: 75vh; display: flex; align-items: center;">
    <div class="container text-center py-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-4 mb-3" style="background-color: #fee2e2; width: 88px; height: 88px;">
            <i class="bi bi-shield-x fs-1 text-danger"></i>
        </div>
        <h1 class="display-5 fw-extrabold text-dark mb-2">403 — Access Denied</h1>
        <p class="lead text-muted mx-auto mb-4" style="max-width: 560px;">
            {{ $exception->getMessage() ?: 'Your request has been restricted by CLINICSYNC cyber defense filters or insufficient account privileges.' }}
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('home') }}" class="btn btn-teal rounded-pill px-4">
                <i class="bi bi-house-door me-1"></i> Return to Homepage
            </a>
            @auth
                <a href="{{ Auth::user()->isPatient() ? route('patient.dashboard') : (Auth::user()->isDentist() ? route('dentist.dashboard') : route('staff.dashboard')) }}" class="btn btn-outline-teal rounded-pill px-4">
                    <i class="bi bi-speedometer2 me-1"></i> My Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-teal rounded-pill px-4">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </a>
            @endauth
        </div>
    </div>
</section>
@endsection
