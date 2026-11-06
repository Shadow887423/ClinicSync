@extends('layouts.app')

@section('title', '404 Page Not Found — CLINICSYNC')

@section('content')
<section class="py-5" style="min-height: 75vh; display: flex; align-items: center;">
    <div class="container text-center py-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-4 mb-3" style="background-color: #e0f2fe; width: 88px; height: 88px;">
            <i class="bi bi-compass fs-1 text-primary"></i>
        </div>
        <h1 class="display-5 fw-extrabold text-dark mb-2">404 — Page Not Found</h1>
        <p class="lead text-muted mx-auto mb-4" style="max-width: 560px;">
            The clinic resource, portal URL, or appointment page you requested does not exist or may have been relocated.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('home') }}" class="btn btn-teal rounded-pill px-4">
                <i class="bi bi-house-door me-1"></i> Return to Homepage
            </a>
            <a href="{{ route('contact') }}" class="btn btn-outline-teal rounded-pill px-4">
                <i class="bi bi-telephone me-1"></i> Contact Reception
            </a>
        </div>
    </div>
</section>
@endsection
