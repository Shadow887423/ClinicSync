@extends('layouts.dashboard')

@section('title', 'Appointment Details — ' . $appointment->appointment_number)
@section('page-title', 'Clinical Appointment View')

@section('content')
<div class="mb-3">
    <a href="{{ route('dentist.schedule') }}" class="text-decoration-none small text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to Schedule
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                <div>
                    <span class="badge bg-light text-dark border mb-1">Appointment Reference</span>
                    <h4 class="fw-bold text-dark mb-0"><code>{{ $appointment->appointment_number }}</code></h4>
                </div>
                <span class="badge {{ $appointment->status_badge_class }} fs-6 px-3 py-2">
                    {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                </span>
            </div>

            <!-- Schedule Card -->
            <div class="p-3 bg-light rounded-3 border mb-4">
                <div class="row g-3">
                    <div class="col-md-6 border-end">
                        <div class="text-muted small fw-bold text-uppercase">Scheduled Date</div>
                        <div class="h5 fw-bold text-dark mb-0">
                            {{ \Carbon\Carbon::parse($appointment->effective_date)->format('l, F d, Y') }}
                        </div>
                    </div>
                    <div class="col-md-6 ps-md-4">
                        <div class="text-muted small fw-bold text-uppercase">Allocated Chair Time</div>
                        <div class="h5 fw-bold text-primary mb-0">
                            <i class="bi bi-clock me-1"></i>
                            {{ \Carbon\Carbon::parse($appointment->effective_time)->format('g:i A') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small text-muted fw-bold mb-1">Procedure / Service Type</label>
                <div class="p-3 bg-white border rounded-3 fw-semibold text-dark">
                    <i class="bi bi-shield-check text-success me-2"></i> {{ $appointment->service_type }}
                </div>
            </div>

            @if($appointment->patient_notes)
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold mb-1">Patient Symptoms / Notes</label>
                    <div class="p-3 bg-light rounded-3 text-dark small border">
                        {{ $appointment->patient_notes }}
                    </div>
                </div>
            @endif

            @if($appointment->staff_notes)
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold mb-1">Reception / Staff Instructions</label>
                    <div class="p-3 bg-info bg-opacity-10 rounded-3 text-dark small border border-info border-opacity-25">
                        {{ $appointment->staff_notes }}
                    </div>
                </div>
            @endif

            @if($appointment->status === 'confirmed')
                <div class="pt-3 border-top mt-4">
                    <form action="{{ route('dentist.appointments.complete', $appointment) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold" onclick="return confirm('Mark this dental procedure as completed?');">
                            <i class="bi bi-clipboard2-check me-1"></i> Mark Procedure Completed
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Activity Timeline -->
        <div class="card card-custom p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-primary me-2"></i> Activity History</h5>
            <div class="timeline position-relative ps-4 border-start border-2 border-teal" style="margin-left: 10px;">
                @forelse($appointment->activityLogs as $log)
                    <div class="mb-3 position-relative">
                        <div class="position-absolute rounded-circle bg-teal" style="width: 10px; height: 10px; left: -21px; top: 5px; background-color: var(--secondary-teal);"></div>
                        <div class="fw-bold small text-dark">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</div>
                        <p class="text-muted small mb-0">{{ $log->details }}</p>
                        <div class="text-muted" style="font-size: 0.72rem;">{{ $log->created_at->format('M d, Y \a\t g:i A') }}</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No activity logged.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Right Column: Patient Summary -->
    <div class="col-lg-4">
        <div class="card card-custom p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-fill text-primary me-2"></i> Patient Information</h5>
            @if($appointment->patient)
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 48px; height: 48px; background-color: var(--primary-navy) !important;">
                        {{ strtoupper(substr($appointment->patient->name, 0, 1)) }}
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">{{ $appointment->patient->name }}</h6>
                        <div class="text-muted small">Registered Patient</div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 small">
                    <div class="mb-2">
                        <span class="text-muted d-block">Gender:</span>
                        <span class="fw-semibold text-dark">{{ $appointment->patient->gender ?: 'Not specified' }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted d-block">Date of Birth:</span>
                        <span class="fw-semibold text-dark">{{ $appointment->patient->birth_date ? $appointment->patient->birth_date->format('M d, Y') : 'Not specified' }}</span>
                    </div>
                    <div>
                        <span class="text-muted d-block">Contact Phone:</span>
                        <span class="fw-semibold text-dark">{{ $appointment->patient->phone ?: 'Not provided' }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
