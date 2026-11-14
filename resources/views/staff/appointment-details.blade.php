@extends('layouts.dashboard')

@section('title', 'Triage Appointment — ' . $appointment->appointment_number)
@section('page-title', 'Appointment Triage Console')

@section('content')
<div class="mb-3">
    <a href="{{ route('staff.appointments.index') }}" class="text-decoration-none small text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to Appointments List
    </a>
</div>

<div class="row g-4">
    <!-- Left Column: Details & Actions -->
    <div class="col-lg-8">
        <!-- Overview Card -->
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4 pb-3 border-bottom">
                <div>
                    <span class="badge bg-light text-dark border mb-1">Appointment Reference</span>
                    <h4 class="fw-bold text-dark mb-0"><code>{{ $appointment->appointment_number }}</code></h4>
                </div>
                <div>
                    <span class="badge {{ $appointment->status_badge_class }} fs-6 px-3 py-2">
                        {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}
                    </span>
                </div>
            </div>

            <!-- Schedule Comparison -->
            <div class="p-3 mb-4 rounded-3 bg-light border">
                <div class="row g-3">
                    <div class="col-md-6 border-end">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Requested Schedule</div>
                        <div class="fw-bold text-dark">
                            <i class="bi bi-calendar3 me-1 text-primary"></i>
                            {{ \Carbon\Carbon::parse($appointment->requested_date)->format('l, F d, Y') }}
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-clock me-1"></i>
                            {{ \Carbon\Carbon::parse($appointment->requested_time)->format('g:i A') }}
                        </div>
                    </div>

                    <div class="col-md-6 ps-md-4">
                        <div class="text-muted small fw-bold text-uppercase mb-1">Confirmed Schedule</div>
                        @if($appointment->confirmed_date)
                            <div class="fw-bold text-success">
                                <i class="bi bi-check-circle-fill me-1"></i>
                                {{ \Carbon\Carbon::parse($appointment->confirmed_date)->format('l, F d, Y') }}
                            </div>
                            <div class="text-success small fw-semibold">
                                <i class="bi bi-clock me-1"></i>
                                {{ \Carbon\Carbon::parse($appointment->confirmed_time)->format('g:i A') }}
                            </div>
                        @else
                            <div class="text-warning small fst-italic">Awaiting Confirmation</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small text-muted fw-bold mb-1">Requested Service</label>
                    <div class="p-2 border rounded bg-white fw-semibold">{{ $appointment->service_type }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-muted fw-bold mb-1">Assigned Dentist</label>
                    <div class="p-2 border rounded bg-white">
                        @if($appointment->dentist)
                            <span class="fw-semibold">{{ $appointment->dentist->name }}</span>
                            <span class="text-muted small">({{ $appointment->dentist->specialization }})</span>
                        @else
                            <span class="text-warning fw-semibold">Unassigned</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($appointment->patient_notes)
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold mb-1">Patient Notes</label>
                    <div class="p-3 bg-light rounded text-dark small border">{{ $appointment->patient_notes }}</div>
                </div>
            @endif

            @if($appointment->staff_notes)
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold mb-1">Clinic Staff Notes</label>
                    <div class="p-3 bg-info bg-opacity-10 rounded text-dark small border border-info border-opacity-25">{{ $appointment->staff_notes }}</div>
                </div>
            @endif

            @if($appointment->reschedule_reason)
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold mb-1">Reschedule Request</label>
                    <div class="p-3 bg-warning bg-opacity-10 rounded text-dark small border border-warning border-opacity-25">
                        <div class="fw-bold text-dark">Reason: {{ $appointment->reschedule_reason }}</div>
                        @if($appointment->proposed_date)
                            <div class="small text-muted mt-1">
                                Patient Preferred: {{ \Carbon\Carbon::parse($appointment->proposed_date)->format('M d, Y') }} 
                                @if($appointment->proposed_time) at {{ \Carbon\Carbon::parse($appointment->proposed_time)->format('g:i A') }} @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($appointment->rejection_reason)
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold mb-1 text-danger">Rejection Reason</label>
                    <div class="p-3 bg-danger bg-opacity-10 rounded text-danger small border border-danger border-opacity-25">{{ $appointment->rejection_reason }}</div>
                </div>
            @endif

            @if($appointment->cancellation_reason)
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold mb-1 text-secondary">Cancellation Reason</label>
                    <div class="p-3 bg-secondary bg-opacity-10 rounded text-secondary small border border-secondary border-opacity-25">{{ $appointment->cancellation_reason }}</div>
                </div>
            @endif

            <!-- Triage Actions -->
            <div class="d-flex flex-wrap gap-2 pt-3 border-top mt-3">
                @if(in_array($appointment->status, ['pending', 'reschedule_requested']))
                    <button type="button" class="btn btn-teal rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#confirmModal">
                        <i class="bi bi-check2-circle me-1"></i> Confirm & Send SMS
                    </button>
                    <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle me-1"></i> Reject Request
                    </button>
                @endif

                @if($appointment->status === 'confirmed')
                    <form action="{{ route('staff.appointments.complete', $appointment) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary rounded-pill px-4" onclick="return confirm('Mark this appointment as completed?');">
                            <i class="bi bi-clipboard2-check me-1"></i> Mark Completed
                        </button>
                    </form>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modifyModal">
                        <i class="bi bi-pencil-square me-1"></i> Modify Schedule
                    </button>
                @endif

                @if(!in_array($appointment->status, ['completed', 'cancelled', 'rejected']))
                    <button type="button" class="btn btn-outline-danger rounded-pill px-3 ms-auto" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        <i class="bi bi-x-octagon me-1"></i> Cancel Appointment
                    </button>
                @endif
            </div>
        </div>

        <!-- Activity History & Complete Record Lifecycle -->
        <div class="card card-custom p-4 mb-4 border shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-clock-history text-primary me-2"></i> Activity History & Record Lifecycle
                    </h5>
                    <span class="text-muted small">Chronological audit trail documenting creation, field changes, triage, and status transitions</span>
                </div>
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('staff.audit.record', ['type' => 'appointment', 'id' => $appointment->id]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        <i class="bi bi-shield-check me-1"></i> Full Lifecycle View
                    </a>
                @endif
            </div>

            @if($appointment->auditLogs->isNotEmpty())
                @include('partials.audit-timeline', ['timeline' => $appointment->auditLogs])
            @else
                <div class="timeline position-relative ps-4 border-start border-2 border-primary" style="margin-left: 10px;">
                    @forelse($appointment->activityLogs as $log)
                        <div class="mb-4 position-relative">
                            <div class="position-absolute rounded-circle bg-primary" style="width: 12px; height: 12px; left: -23px; top: 4px;"></div>
                            <div class="fw-bold small text-dark mb-0">
                                {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                @if($log->new_status)
                                    <span class="badge bg-light text-dark border ms-1">{{ ucfirst($log->new_status) }}</span>
                                @endif
                            </div>
                            <p class="text-muted small mb-1">{{ $log->details }}</p>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                {{ $log->created_at->format('M d, Y \a\t g:i A') }} by {{ $log->user ? $log->user->name : 'System' }}
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No activity logged.</p>
                    @endforelse
                </div>
            @endif
        </div>
    </div>

    <!-- Right Column: Patient Contact Card & SMS Logs -->
    <div class="col-lg-4">
        <!-- Patient Contact Card -->
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-lines-fill text-teal me-2" style="color: var(--secondary-teal);"></i> Patient Contact Details</h5>
            @if($appointment->patient)
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-teal text-white d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 48px; height: 48px; background-color: var(--secondary-teal);">
                        {{ strtoupper(substr($appointment->patient->name, 0, 1)) }}
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">{{ $appointment->patient->name }}</h6>
                        <div class="text-muted small">{{ $appointment->patient->email }}</div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 mb-3 small">
                    <div class="mb-2">
                        <span class="text-muted d-block">Verified Mobile (SMS Target):</span>
                        <span class="fw-bold text-dark fs-6"><i class="bi bi-phone me-1 text-primary"></i> {{ $appointment->patient->phone ?: 'Not provided' }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted d-block">Gender & Birth Date:</span>
                        <span class="text-dark">{{ $appointment->patient->gender ?: 'N/A' }} &bull; {{ $appointment->patient->birth_date ? $appointment->patient->birth_date->format('M d, Y') : 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-muted d-block">Residential Address:</span>
                        <span class="text-dark">{{ $appointment->patient->address ?: 'N/A' }}</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- SMS Notifications for this Appointment -->
        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-chat-heart text-teal me-2" style="color: var(--secondary-teal);"></i> SMS Delivery Logs</h5>
                <span class="badge bg-teal" style="background-color: var(--secondary-teal);">{{ $appointment->smsNotifications->count() }} Sent</span>
            </div>

            @forelse($appointment->smsNotifications as $sms)
                <div class="p-3 mb-3 rounded-3 border bg-light small">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge {{ $sms->status_badge_class }}">{{ ucfirst($sms->notification_type) }}</span>
                        <span class="text-muted" style="font-size: 0.72rem;">{{ $sms->created_at->format('M d, g:i A') }}</span>
                    </div>
                    <div class="p-2 bg-white rounded border mb-2 text-dark font-monospace" style="font-size: 0.78rem;">
                        {{ $sms->message }}
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="font-size: 0.72rem;">
                        <span class="text-muted">Gateway: {{ ucfirst($sms->gateway) }}</span>
                        <span>Ref: <code>{{ $sms->gateway_reference ?? 'Pending' }}</code></span>
                    </div>

                    @if($sms->status === 'failed')
                        <div class="mt-2 text-danger small">
                            <strong>Error:</strong> {{ $sms->error_message }}
                        </div>
                        <form action="{{ route('staff.notifications.retry', $sms) }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100 rounded-pill">
                                <i class="bi bi-arrow-repeat me-1"></i> Retry SMS Dispatch
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-chat-dots fs-3 opacity-50 d-block mb-2"></i>
                    No SMS messages dispatched yet. Confirming this appointment will automatically trigger an SMS.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.appointments.confirm', $appointment) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i> Confirm Appointment & Trigger SMS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Assign Dentist <span class="text-danger">*</span></label>
                        <select name="dentist_id" class="form-select" required>
                            <option value="">-- Choose Dentist --</option>
                            @foreach($dentists as $d)
                                <option value="{{ $d->id }}" {{ ($appointment->dentist_id == $d->id) ? 'selected' : '' }}>
                                    {{ $d->name }} ({{ $d->specialization }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Confirmed Date <span class="text-danger">*</span></label>
                            <input type="date" name="confirmed_date" class="form-control" value="{{ $appointment->requested_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Confirmed Time <span class="text-danger">*</span></label>
                            <input type="time" name="confirmed_time" class="form-control" value="{{ \Carbon\Carbon::parse($appointment->requested_time)->format('H:i') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Staff Remarks / Instructions</label>
                        <textarea name="staff_notes" class="form-control" rows="2" placeholder="Instructions for patient (e.g. Please bring insurance card, arrive 10 min early)">{{ $appointment->staff_notes }}</textarea>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-broadcast me-1"></i> Confirming will immediately dispatch an SMS confirmation to <strong>{{ $appointment->patient->phone }}</strong>.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">Confirm & Send SMS</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modify Modal -->
<div class="modal fade" id="modifyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.appointments.modify', $appointment) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-1"></i> Modify Appointment Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Dentist</label>
                        <select name="dentist_id" class="form-select" required>
                            @foreach($dentists as $d)
                                <option value="{{ $d->id }}" {{ ($appointment->dentist_id == $d->id) ? 'selected' : '' }}>
                                    {{ $d->name }} ({{ $d->specialization }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">New Date</label>
                            <input type="date" name="date" class="form-control" value="{{ $appointment->effective_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">New Time</label>
                            <input type="time" name="time" class="form-control" value="{{ \Carbon\Carbon::parse($appointment->effective_time)->format('H:i') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Reason for Modification</label>
                        <textarea name="staff_notes" class="form-control" rows="2" placeholder="Reason for changing schedule"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.appointments.reject', $appointment) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-1"></i> Reject Appointment Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Explain to patient why this slot cannot be accommodated..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.appointments.cancel', $appointment) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-x-octagon me-1"></i> Cancel Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="Provide cancellation reason..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
