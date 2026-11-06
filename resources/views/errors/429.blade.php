@extends('layouts.app')

@section('title', '429 Rate Limit Exceeded — CLINICSYNC')

@section('content')
<section class="py-5" style="min-height: 75vh; display: flex; align-items: center;">
    <div class="container text-center py-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-4 mb-3" style="background-color: #fee2e2; width: 88px; height: 88px;">
            <i class="bi bi-hourglass-split fs-1 text-danger"></i>
        </div>
        <h1 class="display-5 fw-extrabold text-dark mb-2">429 — Too Many Requests</h1>
        <p class="lead text-muted mx-auto mb-4" style="max-width: 560px;">
            {{ $exception->getMessage() ?: 'You have submitted too many requests in a short time frame. Please pause for a moment before retrying.' }}
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="{{ route('home') }}" class="btn btn-teal rounded-pill px-4">
                <i class="bi bi-house-door me-1"></i> Return to Homepage
            </a>
            <a href="javascript:location.reload();" class="btn btn-outline-teal rounded-pill px-4">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Page
            </a>
        </div>
    </div>
</section>
@endsection
