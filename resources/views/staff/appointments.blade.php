@extends('layouts.dashboard')

@section('title', 'Appointments Management')
@section('page-title', 'Appointment Management & Triage')

@section('content')
<div class="card card-custom p-4">
    <!-- Filter Bar -->
    <form method="GET" action="{{ route('staff.appointments.index') }}" class="mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search patient, phone, ref #..." value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="reschedule_requested" {{ request('status') === 'reschedule_requested' ? 'selected' : '' }}>Reschedule In Review</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="col-md-3">
                <select name="dentist_id" class="form-select">
                    <option value="">All Dentists</option>
                    @foreach($dentists as $d)
                        <option value="{{ $d->id }}" {{ request('dentist_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-navy flex-grow-1">Filter</button>
                <a href="{{ route('staff.appointments.index') }}" class="btn btn-light border" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </div>
    </form>

    <!-- Table -->
    @if($appointments->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Ref Number</th>
                        <th>Patient Name & Mobile</th>
                        <th>Service</th>
                        <th>Assigned Dentist</th>
                        <th>Scheduled Slot</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($appointments as $apt)
                        <tr>
                            <td>
                                <a href="{{ route('staff.appointments.show', $apt) }}" class="fw-bold text-dark text-decoration-none">
                                    <code>{{ $apt->appointment_number }}</code>
                                </a>
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $apt->created_at->format('M d, Y') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $apt->patient ? $apt->patient->name : 'Unknown Patient' }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-phone me-1"></i> {{ $apt->patient ? $apt->patient->phone : 'N/A' }}
                                </div>
                            </td>
                            <td class="fw-semibold text-dark">{{ $apt->service_type }}</td>
                            <td>
                                @if($apt->dentist)
                                    <div>{{ $apt->dentist->name }}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $apt->dentist->specialization }}</div>
                                @else
                                    <span class="badge bg-warning text-dark">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold">{{ \Carbon\Carbon::parse($apt->effective_date)->format('M d, Y') }}</div>
                                <div class="text-muted">{{ \Carbon\Carbon::parse($apt->effective_time)->format('g:i A') }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $apt->status_badge_class }}">
                                    {{ ucfirst(str_replace('_', ' ', $apt->status)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('staff.appointments.show', $apt) }}" class="btn btn-sm btn-outline-teal rounded-pill px-3">
                                    Manage & Triage
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
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 opacity-50 d-block mb-3"></i>
            <h5>No appointment records found matching your filters.</h5>
            <p class="small mb-0">Try clearing filters or checking other dates.</p>
        </div>
    @endif
</div>
@endsection
