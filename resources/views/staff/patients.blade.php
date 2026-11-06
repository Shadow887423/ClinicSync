@extends('layouts.dashboard')

@section('title', 'Patient Directory')
@section('page-title', 'Patient Directory & Contact Registry')

@section('content')
<div class="card card-custom p-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold text-dark mb-1">Registered Dental Patients</h5>
            <p class="text-muted small mb-0">Authorized contact repository for SMS notifications and clinical appointment coordination.</p>
        </div>

        <form method="GET" action="{{ route('staff.patients') }}" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, phone, email..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-sm btn-navy">Search</button>
            @if(request('search'))
                <a href="{{ route('staff.patients') }}" class="btn btn-sm btn-light border">Reset</a>
            @endif
        </form>
    </div>

    @if($patients->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Patient Name</th>
                        <th>Mobile Number (SMS Target)</th>
                        <th>Email Address</th>
                        <th>Gender / Birth Date</th>
                        <th>Appointments</th>
                        <th>Account Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patients as $patient)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $patient->name }}</div>
                                <div class="text-muted" style="font-size: 0.72rem;">Joined {{ $patient->created_at->format('M d, Y') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><i class="bi bi-phone me-1"></i> {{ $patient->phone ?: 'None' }}</div>
                            </td>
                            <td>{{ $patient->email }}</td>
                            <td>
                                {{ $patient->gender ?: 'N/A' }} 
                                @if($patient->birth_date) &bull; {{ $patient->birth_date->format('M d, Y') }} @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $patient->appointments_count }} Visit(s)
                                </span>
                            </td>
                            <td>
                                @if($patient->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Deactivated</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $patients->links() }}
        </div>
    @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-people fs-1 opacity-50 d-block mb-3"></i>
            <h5>No patients found matching your search.</h5>
        </div>
    @endif
</div>
@endsection
