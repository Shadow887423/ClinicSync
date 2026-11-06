@extends('layouts.app')

@section('title', '500 System Error — CLINICSYNC')

@section('content')
<section class="py-5" style="min-height: 75vh; display: flex; align-items: center;">
    <div class="container text-center py-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-4 mb-3" style="background-color: #fef3c7; width: 88px; height: 88px;">
            <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
        </div>
        <h1 class="display-5 fw-extrabold text-dark mb-2">500 — Server Incident</h1>
        <p class="lead text-muted mx-auto mb-4" style="max-width: 560px;">
            An unexpected error occurred while processing your request. The incident has been recorded in the secure audit logs for review by our clinical systems administrator.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('home') }}" class="btn btn-teal rounded-pill px-4">
                <i class="bi bi-house-door me-1"></i> Return to Homepage
            </a>
            <a href="{{ route('contact') }}" class="btn btn-outline-teal rounded-pill px-4">
                <i class="bi bi-telephone me-1"></i> Clinic Emergency Line
            </a>
        </div>
    </div>
</section>
@endsection
