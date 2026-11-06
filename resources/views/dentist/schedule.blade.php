@extends('layouts.dashboard')

@section('title', 'My Assigned Schedule')
@section('page-title', 'My Assigned Patient Schedule')

@section('content')
<div class="card card-custom p-4">
    <!-- Filter -->
    <form method="GET" action="{{ route('dentist.schedule') }}" class="mb-4">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}" placeholder="Filter by date">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-navy px-3">Filter</button>
                <a href="{{ route('dentist.schedule') }}" class="btn btn-sm btn-light border">Reset</a>
            </div>
        </div>
    </form>

    @if($appointments->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Ref #</th>
                        <th>Patient Name</th>
                        <th>Dental Procedure</th>
                        <th>Patient Notes</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($appointments as $apt)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($apt->effective_date)->format('M d, Y') }}</div>
                                <div class="text-primary fw-semibold">{{ \Carbon\Carbon::parse($apt->effective_time)->format('g:i A') }}</div>
                            </td>
                            <td><code>{{ $apt->appointment_number }}</code></td>
                            <td class="fw-semibold text-dark">{{ $apt->patient ? $apt->patient->name : 'Patient' }}</td>
                            <td>{{ $apt->service_type }}</td>
                            <td>
                                <span class="text-muted text-truncate d-inline-block" style="max-width: 200px;">
                                    {{ $apt->patient_notes ?: 'None' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $apt->status_badge_class }}">
                                    {{ ucfirst(str_replace('_', ' ', $apt->status)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('dentist.appointments.show', $apt) }}" class="btn btn-sm btn-outline-teal rounded-pill">
                                    View Details
                                </a>
                                @if($apt->status === 'confirmed')
                                    <form action="{{ route('dentist.appointments.complete', $apt) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-teal rounded-pill" onclick="return confirm('Mark this appointment as completed?');">
                                            Complete
                                        </button>
                                    </form>
                                @endif
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
            <i class="bi bi-calendar2-check fs-1 opacity-50 d-block mb-3"></i>
            <h5>No appointments found for this filter.</h5>
        </div>
    @endif
</div>
@endsection
