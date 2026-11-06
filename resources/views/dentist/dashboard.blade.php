@extends('layouts.dashboard')

@section('title', 'Dentist Portal')
@section('page-title', "Dr. {$dentist->name} — Clinical Schedule")

@section('content')
<!-- Header Card -->
<div class="card card-custom p-4 mb-4 border-0 text-white shadow-sm" style="background: linear-gradient(135deg, #0f2744 0%, #1e3a8a 100%);">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <span class="badge bg-white bg-opacity-20 text-white mb-2">Dentist Portal</span>
            <h3 class="fw-bold mb-1">{{ $dentist->name }}</h3>
            <p class="mb-0 text-light opacity-75 small">
                Specialization: <strong>{{ $dentist->specialization }}</strong> &bull; License: <strong>{{ $dentist->license_number ?? 'Accredited' }}</strong>
            </p>
        </div>
        <div>
            <a href="{{ route('dentist.schedule') }}" class="btn btn-teal rounded-pill px-4 fw-bold">
                <i class="bi bi-calendar-week me-1"></i> View Full Schedule
            </a>
        </div>
    </div>
</div>

<!-- Metrics -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Today's Visits</div>
                <div class="h3 fw-bold text-primary mb-0">{{ $stats['today'] }}</div>
            </div>
            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-calendar-day"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Upcoming Confirmed</div>
                <div class="h3 fw-bold text-success mb-0">{{ $stats['upcoming_confirmed'] }}</div>
            </div>
            <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-calendar-check"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="stat-card">
            <div>
                <div class="text-muted small fw-semibold">Completed Patients</div>
                <div class="h3 fw-bold text-teal mb-0" style="color: var(--secondary-teal);">{{ $stats['completed'] }}</div>
            </div>
            <div class="stat-icon bg-teal bg-opacity-10 text-teal" style="color: var(--secondary-teal);">
                <i class="bi bi-clipboard2-check"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Today's Schedule -->
    <div class="col-lg-7">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-clock text-primary me-2"></i> Today's Schedule
                </h5>
                <span class="badge bg-primary">{{ \Carbon\Carbon::today()->format('l, M d, Y') }}</span>
            </div>

            @if($todayAppointments->isNotEmpty())
                <div class="d-flex flex-column gap-3">
                    @foreach($todayAppointments as $apt)
                        <div class="p-3 border rounded-3 bg-light d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-dark">{{ \Carbon\Carbon::parse($apt->effective_time)->format('g:i A') }}</span>
                                    <h6 class="fw-bold text-dark mb-0">{{ $apt->patient ? $apt->patient->name : 'Patient' }}</h6>
                                    <span class="badge {{ $apt->status_badge_class }}">{{ ucfirst($apt->status) }}</span>
                                </div>
                                <div class="text-muted small">
                                    <i class="bi bi-shield-check text-success me-1"></i> {{ $apt->service_type }}
                                </div>
                                @if($apt->patient_notes)
                                    <div class="small text-muted fst-italic mt-1">
                                        "{{ $apt->patient_notes }}"
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex gap-2">
                                <a href="{{ route('dentist.appointments.show', $apt) }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                                    Details
                                </a>
                                @if($apt->status === 'confirmed')
                                    <form action="{{ route('dentist.appointments.complete', $apt) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-teal rounded-pill" onclick="return confirm('Mark this dental procedure as completed?');">
                                            <i class="bi bi-check2"></i> Complete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5 text-muted small">
                    <i class="bi bi-calendar-check fs-1 text-success opacity-50 d-block mb-2"></i>
                    No appointments on your schedule for today.
                </div>
            @endif
        </div>
    </div>

    <!-- Upcoming this week -->
    <div class="col-lg-5">
        <div class="card card-custom p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-calendar-week-fill text-teal me-2" style="color: var(--secondary-teal);"></i> Week At A Glance
                </h5>
                <span class="badge bg-light text-muted border">Mon – Sun</span>
            </div>

            @if($weekAppointments->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Day & Time</th>
                                <th>Patient</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($weekAppointments as $apt)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ \Carbon\Carbon::parse($apt->effective_date)->format('D, M d') }}</div>
                                        <div class="text-muted">{{ \Carbon\Carbon::parse($apt->effective_time)->format('g:i A') }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $apt->patient ? $apt->patient->name : 'Patient' }}</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $apt->service_type }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $apt->status_badge_class }}">{{ ucfirst($apt->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 text-muted small">
                    <i class="bi bi-calendar2-x fs-1 opacity-50 d-block mb-2"></i>
                    No upcoming appointments scheduled for this week.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
