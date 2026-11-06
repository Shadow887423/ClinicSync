@extends('layouts.dashboard')

@section('title', 'Patient Dashboard')
@section('page-title', 'Patient Overview')

@section('content')
<!-- Welcome Header Banner -->
<div class="card card-custom p-4 mb-4 border-0 text-white shadow-sm" style="background: linear-gradient(135deg, #0f2744 0%, #0d9488 100%);">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <span class="badge bg-white bg-opacity-20 text-white mb-2">Welcome Back</span>
            <h3 class="fw-bold mb-1">{{ $patient->name }}</h3>
            <p class="mb-0 text-light opacity-75 small">
                Registered Mobile: <strong>{{ $patient->phone ?: 'Not provided' }}</strong> 
                &bull; Active SMS notification service enabled
            </p>
        </div>
        <div>
            <a href="{{ route('patient.appointments.request') }}" class="btn btn-light fw-bold text-dark rounded-pill px-4 shadow-sm">
                <i class="bi bi-calendar-plus-fill text-teal me-1" style="color: var(--secondary-teal);"></i> Book Appointment
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Upcoming Confirmed</div>
                <div class="h3 fw-bold text-success mb-0">{{ $stats['confirmed'] }}</div>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Pending Requests</div>
                <div class="h3 fw-bold text-warning mb-0">{{ $stats['pending'] }}</div>
            </div>
            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Completed Visits</div>
                <div class="h3 fw-bold text-primary mb-0">{{ $stats['completed'] }}</div>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-clipboard2-check-fill"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Total Bookings</div>
                <div class="h3 fw-bold text-dark mb-0">{{ $stats['total'] }}</div>
            </div>
            <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="bi bi-journal-medical"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Upcoming & Pending -->
    <div class="col-lg-8">
        {{-- Next Confirmed Appointment --}}
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-heart text-teal me-2" style="color: var(--secondary-teal);"></i> Next Confirmed Appointment</h5>
                @if($upcomingConfirmed)
                    <span class="badge bg-success">Confirmed & Scheduled</span>
                @endif
            </div>

            @if($upcomingConfirmed)
                <div class="p-3 rounded-3 border bg-light">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3 text-center border-end">
                            <div class="h4 fw-bold text-teal mb-0" style="color: var(--secondary-teal);">
                                {{ \Carbon\Carbon::parse($upcomingConfirmed->effective_date)->format('M d') }}
                            </div>
                            <div class="text-muted small fw-bold">{{ \Carbon\Carbon::parse($upcomingConfirmed->effective_date)->format('Y (D)') }}</div>
                            <div class="badge bg-dark mt-1">{{ \Carbon\Carbon::parse($upcomingConfirmed->effective_time)->format('g:i A') }}</div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-1">{{ $upcomingConfirmed->service_type }}</h6>
                            <p class="text-muted small mb-1">
                                <i class="bi bi-person-fill text-primary"></i> 
                                Dentist: <strong>{{ $upcomingConfirmed->dentist ? $upcomingConfirmed->dentist->name : 'Staff Assigned' }}</strong>
                            </p>
                            <p class="text-muted small mb-0">
                                Ref: <code>{{ $upcomingConfirmed->appointment_number }}</code>
                            </p>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <a href="{{ route('patient.appointments.show', $upcomingConfirmed) }}" class="btn btn-sm btn-outline-teal rounded-pill w-100 mb-1">
                                View Details
                            </a>
                            <a href="{{ route('patient.appointments.reschedule', $upcomingConfirmed) }}" class="btn btn-sm btn-light border rounded-pill w-100 text-muted">
                                Reschedule
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-calendar-x fs-1 text-secondary opacity-50 d-block mb-2"></i>
                    <p class="mb-2">You currently have no upcoming confirmed dental appointments.</p>
                    <a href="{{ route('patient.appointments.request') }}" class="btn btn-sm btn-teal rounded-pill px-3">
                        <i class="bi bi-plus-lg me-1"></i> Request An Appointment
                    </a>
                </div>
            @endif
        </div>

        {{-- Pending Requests Queue --}}
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-hourglass-top text-warning me-2"></i> Pending Requests Under Review</h5>
                <span class="badge bg-warning text-dark">{{ $pendingRequests->count() }} Pending</span>
            </div>

            @if($pendingRequests->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref #</th>
                                <th>Service</th>
                                <th>Dentist</th>
                                <th>Requested Slot</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingRequests as $req)
                                <tr>
                                    <td><code>{{ $req->appointment_number }}</code></td>
                                    <td class="fw-semibold">{{ $req->service_type }}</td>
                                    <td>{{ $req->dentist ? $req->dentist->name : 'Any Available' }}</td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($req->requested_date)->format('M d, Y') }} 
                                        at {{ \Carbon\Carbon::parse($req->requested_time)->format('g:i A') }}
                                    </td>
                                    <td>
                                        <a href="{{ route('patient.appointments.show', $req) }}" class="btn btn-sm btn-light border rounded-pill">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted small mb-0">No pending appointment requests at this moment.</p>
            @endif
        </div>

        {{-- Recent Past Appointments --}}
        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i> Past Appointment History</h5>
                <a href="{{ route('patient.appointments.index') }}" class="small text-teal fw-semibold">View All</a>
            </div>

            @if($appointmentHistory->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Service</th>
                                <th>Dentist</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($appointmentHistory as $hist)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($hist->effective_date)->format('M d, Y') }}</td>
                                    <td class="fw-semibold">{{ $hist->service_type }}</td>
                                    <td>{{ $hist->dentist ? $hist->dentist->name : 'Staff Assigned' }}</td>
                                    <td>
                                        <span class="badge {{ $hist->status_badge_class }}">{{ ucfirst($hist->status) }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('patient.appointments.show', $hist) }}" class="btn btn-sm btn-light border rounded-pill">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted small mb-0">No past appointment records found.</p>
            @endif
        </div>
    </div>

    <!-- Right Column: SMS Alerts Feed & Quick Contact -->
    <div class="col-lg-4">
        {{-- Recent SMS Alerts Box --}}
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-chat-dots-fill text-teal me-1" style="color: var(--secondary-teal);"></i> Recent SMS Alerts
                </h6>
                <a href="{{ route('patient.notifications') }}" class="small text-muted">All Logs</a>
            </div>

            <p class="small text-muted mb-3">Real-time text alerts dispatched to your mobile phone ({{ $patient->phone }}):</p>

            @forelse($recentNotifications as $sms)
                <div class="p-3 mb-2 rounded-3 border bg-light small">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge {{ $sms->status_badge_class }}">{{ ucfirst($sms->notification_type) }}</span>
                        <span class="text-muted" style="font-size: 0.72rem;">{{ $sms->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="mb-1 text-dark">{{ $sms->message }}</p>
                    <div class="text-muted" style="font-size: 0.7rem;">
                        <i class="bi bi-check2-circle text-success"></i> Status: {{ ucfirst($sms->status) }} 
                        @if($sms->gateway_reference) &bull; Ref: {{ $sms->gateway_reference }} @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-3 text-muted small">
                    <i class="bi bi-chat-square-text opacity-50 d-block fs-4 mb-1"></i>
                    No SMS notifications received yet.
                </div>
            @endforelse
        </div>

        {{-- Clinic Help Card --}}
        <div class="card card-custom p-4 bg-light">
            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-info-circle-fill text-primary me-1"></i> Appointment Guidelines</h6>
            <ul class="small text-muted ps-3 mb-3 d-flex flex-column gap-1">
                <li>Arrive at least 10 minutes prior to your confirmed time slot.</li>
                <li>To reschedule or cancel, submit requests at least 24 hours in advance.</li>
                <li>Keep your mobile number updated in your profile to receive automated reminder texts.</li>
            </ul>
            <div class="border-top pt-2">
                <div class="small fw-semibold text-dark">Need help? Front Desk Hotline:</div>
                <div class="text-teal fw-bold" style="color: var(--secondary-teal);">+1 (555) 123-CLINIC</div>
            </div>
        </div>
    </div>
</div>
@endsection
