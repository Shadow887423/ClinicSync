@extends('layouts.dashboard')

@section('title', 'Appointment Details — ' . $appointment->appointment_number)
@section('page-title', 'Appointment Details')

@section('content')
<div class="mb-3">
    <a href="{{ route('patient.appointments.index') }}" class="text-decoration-none small text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to My Appointments
    </a>
</div>

<div class="row g-4">
    <!-- Main Appointment Information -->
    <div class="col-lg-8">
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4 pb-3 border-bottom">
                <div>
                    <span class="badge bg-light text-dark border mb-1">Appointment Reference</span>
                    <h4 class="fw-bold text-dark mb-0"><code>{{ $appointment->appointment_number }}</code></h4>
                </div>
                <div>
                    <span class="badge {{ $appointment->status_badge_class }} fs-6 px-3 py-2">
                        Status: {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                    </span>
                </div>
            </div>

            <!-- Schedule Card -->
            <div class="p-3 mb-4 rounded-3 bg-light border">
                <div class="row g-3">
                    <div class="col-md-6 border-end">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Scheduled Date & Time</div>
                        <div class="h4 fw-bold text-dark mb-1">
                            {{ \Carbon\Carbon::parse($appointment->effective_date)->format('l, F d, Y') }}
                        </div>
                        <div class="text-primary fw-bold fs-5">
                            <i class="bi bi-clock me-1"></i>
                            {{ \Carbon\Carbon::parse($appointment->effective_time)->format('g:i A') }}
                            @if($appointment->status === 'confirmed')
                                <span class="badge bg-success ms-1 small">Confirmed</span>
                            @elseif($appointment->status === 'pending')
                                <span class="badge bg-warning text-dark ms-1 small">Pending Staff Approval</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6 ps-md-4">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Assigned Dentist</div>
                        @if($appointment->dentist)
                            <h5 class="fw-bold text-dark mb-1">{{ $appointment->dentist->name }}</h5>
                            <div class="text-teal small fw-semibold mb-1" style="color: var(--secondary-teal);">{{ $appointment->dentist->specialization }}</div>
                            <div class="text-muted small">License: {{ $appointment->dentist->license_number ?? 'Accredited' }}</div>
                        @else
                            <div class="text-warning fw-semibold">Pending Clinic Assignment</div>
                            <div class="text-muted small">A qualified dentist will be assigned upon staff confirmation.</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Service & Notes -->
            <div class="mb-4">
                <h6 class="fw-bold text-dark mb-2">Service / Procedure</h6>
                <div class="p-3 bg-white border rounded-3 fw-semibold text-dark">
                    <i class="bi bi-shield-check text-success me-2"></i> {{ $appointment->service_type }}
                </div>
            </div>

            @if($appointment->patient_notes)
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-1">Your Booking Notes / Symptoms</h6>
                    <div class="p-3 bg-light rounded-3 text-muted small">
                        {{ $appointment->patient_notes }}
                    </div>
                </div>
            @endif

            @if($appointment->staff_notes)
                <div class="mb-4">
                    <h6 class="fw-bold text-dark mb-1">Clinic Staff Remarks</h6>
                    <div class="p-3 bg-info bg-opacity-10 border border-info border-opacity-25 rounded-3 text-dark small">
                        <i class="bi bi-info-circle-fill text-info me-1"></i> {{ $appointment->staff_notes }}
                    </div>
                </div>
            @endif

            @if($appointment->rejection_reason)
                <div class="mb-4">
                    <h6 class="fw-bold text-danger mb-1">Reason for Rejection</h6>
                    <div class="p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-3 text-danger small">
                        {{ $appointment->rejection_reason }}
                    </div>
                </div>
            @endif

            @if($appointment->cancellation_reason)
                <div class="mb-4">
                    <h6 class="fw-bold text-secondary mb-1">Reason for Cancellation</h6>
                    <div class="p-3 bg-secondary bg-opacity-10 border border-secondary border-opacity-25 rounded-3 text-secondary small">
                        {{ $appointment->cancellation_reason }}
                    </div>
                </div>
            @endif

            @if($appointment->reschedule_reason)
                <div class="mb-4">
                    <h6 class="fw-bold text-info mb-1">Reschedule Request Reason</h6>
                    <div class="p-3 bg-light border rounded-3 text-dark small">
                        {{ $appointment->reschedule_reason }}
                    </div>
                </div>
            @endif

            <!-- Action Buttons for Eligible Appointments -->
            <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                @if($appointment->canBeRescheduled())
                    <a href="{{ route('patient.appointments.reschedule', $appointment) }}" class="btn btn-teal rounded-pill px-4">
                        <i class="bi bi-arrow-repeat me-1"></i> Request Reschedule
                    </a>
                @endif

                @if($appointment->canBeCancelled())
                    <button type="button" class="btn btn-outline-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        <i class="bi bi-x-circle me-1"></i> Cancel Appointment
                    </button>
                @endif

                <a href="{{ route('patient.appointments.request') }}" class="btn btn-light border rounded-pill px-4 ms-auto">
                    Book Another
                </a>
            </div>
        </div>

        <!-- Activity Audit Log Timeline -->
        <div class="card card-custom p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2 text-primary"></i> Appointment Timeline & Activity History</h5>
            
            <div class="timeline position-relative ps-4 border-start border-2 border-teal" style="margin-left: 10px;">
                @forelse($appointment->activityLogs as $log)
                    <div class="mb-4 position-relative">
                        <div class="position-absolute rounded-circle bg-teal" style="width: 12px; height: 12px; left: -23px; top: 4px; background-color: var(--secondary-teal);"></div>
                        <div class="fw-bold small text-dark mb-0">
                            {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                            @if($log->new_status)
                                <span class="badge bg-light text-dark border ms-1">{{ ucfirst($log->new_status) }}</span>
                            @endif
                        </div>
                        <p class="text-muted small mb-1">{{ $log->details }}</p>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            <i class="bi bi-clock me-1"></i> {{ $log->created_at->format('M d, Y \a\t g:i A') }} 
                            by {{ $log->user ? $log->user->name : 'System' }}
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No activity logged yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Right Column: SMS Logs for this Appointment -->
    <div class="col-lg-4">
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-dark mb-2">
                <i class="bi bi-chat-heart text-teal me-2" style="color: var(--secondary-teal);"></i> SMS Notification Log
            </h5>
            <p class="text-muted small mb-3">All text messages triggered by CLINICSYNC for this appointment:</p>

            @forelse($appointment->smsNotifications as $sms)
                <div class="p-3 mb-3 rounded-3 border bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge {{ $sms->status_badge_class }}">{{ ucfirst($sms->notification_type) }}</span>
                        <span class="text-muted" style="font-size: 0.72rem;">{{ $sms->created_at->format('M d, g:i A') }}</span>
                    </div>
                    <div class="small text-dark mb-2 bg-white p-2 rounded border">
                        "{{ $sms->message }}"
                    </div>
                    <div class="text-muted small" style="font-size: 0.72rem;">
                        <div><i class="bi bi-phone me-1"></i> Recipient: {{ $sms->recipient_phone }}</div>
                        <div><i class="bi bi-check2-all text-success me-1"></i> Gateway: {{ ucfirst($sms->gateway) }}</div>
                        @if($sms->gateway_reference)
                            <div><i class="bi bi-hash me-1"></i> Ref: <code>{{ $sms->gateway_reference }}</code></div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-chat-square-dots fs-3 opacity-50 d-block mb-2"></i>
                    No SMS notifications recorded for this appointment yet. An SMS will be triggered when staff confirms the request.
                </div>
            @endforelse
        </div>

        <div class="card card-custom p-4 bg-light border">
            <h6 class="fw-bold mb-2 text-dark">Need Assistance?</h6>
            <p class="small text-muted mb-3">If you need immediate help or dental emergency assistance, contact our front desk receptionist.</p>
            <div class="small fw-semibold text-dark"><i class="bi bi-telephone-fill me-1 text-primary"></i> +1 (555) 123-CLINIC</div>
        </div>
    </div>
</div>

<!-- Cancel Modal Confirmation -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('patient.appointments.cancel', $appointment) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Confirm Cancellation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to cancel your appointment <strong>{{ $appointment->appointment_number }}</strong>?</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Please state the reason for cancellation <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="e.g. Sudden travel, personal conflict, recovered from issue..." required></textarea>
                    </div>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Cancelling will release this chair slot for other patients. An SMS cancellation notice will be recorded.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Keep Appointment</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
