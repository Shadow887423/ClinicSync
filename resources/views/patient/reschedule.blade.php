@extends('layouts.dashboard')

@section('title', 'Request Rescheduling')
@section('page-title', 'Request Rescheduling')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card card-custom p-4 p-md-5">
            <div class="mb-4 pb-3 border-bottom">
                <a href="{{ route('patient.appointments.show', $appointment) }}" class="text-decoration-none small text-muted d-inline-block mb-2">
                    <i class="bi bi-arrow-left me-1"></i> Back to Appointment
                </a>
                <h4 class="fw-bold text-dark mb-1">Reschedule Appointment</h4>
                <p class="text-muted small mb-0">Ref: <code>{{ $appointment->appointment_number }}</code> &bull; Currently set for {{ \Carbon\Carbon::parse($appointment->effective_date)->format('M d, Y') }} at {{ \Carbon\Carbon::parse($appointment->effective_time)->format('g:i A') }}</p>
            </div>

            <form action="{{ route('patient.appointments.reschedule.store', $appointment) }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="form-label small fw-bold text-dark">
                        Reason for Rescheduling <span class="text-danger">*</span>
                    </label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Please explain why you need to move this appointment..." required>{{ old('reason') }}</textarea>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-dark">Preferred New Date</label>
                        <input type="date" name="preferred_date" class="form-control" min="{{ now()->format('Y-m-d') }}" value="{{ old('preferred_date') }}">
                        <div class="form-text small">Proposed alternative date</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-dark">Preferred New Time</label>
                        <input type="time" name="preferred_time" class="form-control" value="{{ old('preferred_time') }}">
                        <div class="form-text small">e.g. 10:00 AM, 02:30 PM</div>
                    </div>
                </div>

                <div class="alert alert-info small mb-4">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Submitting this form changes the appointment status to <strong>Reschedule In Review</strong>. Clinic staff will verify schedule availability and notify you via SMS once confirmed.
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('patient.appointments.show', $appointment) }}" class="btn btn-light border rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">
                        <i class="bi bi-arrow-repeat me-1"></i> Submit Reschedule Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
