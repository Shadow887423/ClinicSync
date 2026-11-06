@extends('layouts.dashboard')

@section('title', 'My Dental Appointments')
@section('page-title', 'My Dental Appointments')

@section('content')
<div class="card card-custom p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold text-dark mb-1">Appointment Records</h5>
            <p class="text-muted small mb-0">Track the status, schedule changes, and details of all your dental bookings.</p>
        </div>
        <a href="{{ route('patient.appointments.request') }}" class="btn btn-teal rounded-pill px-4">
            <i class="bi bi-calendar-plus-fill me-1"></i> Request New Appointment
        </a>
    </div>

    <!-- Filter Pills -->
    <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom">
        <a href="{{ route('patient.appointments.index') }}" 
           class="btn btn-sm {{ empty($status) ? 'btn-navy text-white' : 'btn-light border' }} rounded-pill px-3">
            All Bookings
        </a>
        <a href="{{ route('patient.appointments.index', ['status' => 'pending']) }}" 
           class="btn btn-sm {{ $status === 'pending' ? 'btn-warning text-dark' : 'btn-light border' }} rounded-pill px-3">
            Pending
        </a>
        <a href="{{ route('patient.appointments.index', ['status' => 'confirmed']) }}" 
           class="btn btn-sm {{ $status === 'confirmed' ? 'btn-success text-white' : 'btn-light border' }} rounded-pill px-3">
            Confirmed
        </a>
        <a href="{{ route('patient.appointments.index', ['status' => 'reschedule_requested']) }}" 
           class="btn btn-sm {{ $status === 'reschedule_requested' ? 'btn-info text-dark' : 'btn-light border' }} rounded-pill px-3">
            Reschedule In Review
        </a>
        <a href="{{ route('patient.appointments.index', ['status' => 'completed']) }}" 
           class="btn btn-sm {{ $status === 'completed' ? 'btn-primary text-white' : 'btn-light border' }} rounded-pill px-3">
            Completed
        </a>
        <a href="{{ route('patient.appointments.index', ['status' => 'cancelled']) }}" 
           class="btn btn-sm {{ $status === 'cancelled' ? 'btn-secondary text-white' : 'btn-light border' }} rounded-pill px-3">
            Cancelled
        </a>
    </div>

    @if($appointments->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light small">
                    <tr>
                        <th>Appointment #</th>
                        <th>Dental Service</th>
                        <th>Assigned Dentist</th>
                        <th>Scheduled Date & Time</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($appointments as $apt)
                        <tr>
                            <td>
                                <a href="{{ route('patient.appointments.show', $apt) }}" class="fw-bold text-dark text-decoration-none">
                                    <code>{{ $apt->appointment_number }}</code>
                                </a>
                                <div class="text-muted" style="font-size: 0.72rem;">Booked {{ $apt->created_at->format('M d, Y') }}</div>
                            </td>
                            <td class="fw-semibold text-dark">{{ $apt->service_type }}</td>
                            <td>
                                @if($apt->dentist)
                                    <div class="fw-semibold">{{ $apt->dentist->name }}</div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">{{ $apt->dentist->specialization }}</div>
                                @else
                                    <span class="text-muted fst-italic">Pending Clinic Assignment</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold">
                                    {{ \Carbon\Carbon::parse($apt->effective_date)->format('M d, Y') }}
                                </div>
                                <div class="text-muted small">
                                    <i class="bi bi-clock me-1"></i>
                                    {{ \Carbon\Carbon::parse($apt->effective_time)->format('g:i A') }}
                                    @if($apt->status === 'pending')
                                        <span class="text-warning small">(Requested)</span>
                                    @else
                                        <span class="text-success small">(Confirmed)</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $apt->status_badge_class }} px-2 py-1">
                                    {{ ucfirst(str_replace('_', ' ', $apt->status)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('patient.appointments.show', $apt) }}" class="btn btn-sm btn-outline-teal rounded-pill px-3">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $appointments->links() }}
        </div>
    @else
        <div class="text-center py-5">
            <i class="bi bi-calendar-x fs-1 text-secondary opacity-50 d-block mb-3"></i>
            <h5 class="fw-bold text-muted">No appointments found matching this filter</h5>
            <p class="small text-muted mb-3">You don't have any appointments in this status category.</p>
            <a href="{{ route('patient.appointments.request') }}" class="btn btn-teal rounded-pill px-4">
                <i class="bi bi-plus-lg me-1"></i> Book An Appointment
            </a>
        </div>
    @endif
</div>
@endsection
