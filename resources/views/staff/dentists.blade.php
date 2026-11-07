@extends('layouts.dashboard')

@section('title', 'Dentist Management & Schedules')
@section('page-title', 'Dentist Profiles & Availability Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold text-dark mb-0">Clinic Dentists & Working Hours</h5>
        <div class="text-muted small">Configure specialist profiles, default slot durations, and weekly shifts.</div>
    </div>
    @if(Auth::user()->isAdmin())
    <button type="button" class="btn btn-teal rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addDentistModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add New Dentist
    </button>
    @else
    <span class="badge bg-light text-muted border p-2">
        <i class="bi bi-shield-lock me-1 text-primary"></i> Administrator Managed
    </span>
    @endif
</div>

<div class="row g-4">
    @foreach($dentists as $dentist)
        <div class="col-lg-6">
            <div class="card card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 50px; height: 50px; background-color: var(--primary-navy) !important;">
                            {{ substr($dentist->name, 4, 1) ?: 'D' }}
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">{{ $dentist->name }}</h5>
                            <div class="text-teal small fw-semibold" style="color: var(--secondary-teal);">{{ $dentist->specialization }}</div>
                            <div class="text-muted small">{{ $dentist->email }} &bull; {{ $dentist->phone }}</div>
                        </div>
                    </div>
                    <span class="badge {{ $dentist->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                        {{ ucfirst($dentist->status) }}
                    </span>
                </div>

                <p class="text-muted small mb-3">
                    {{ $dentist->bio ?? 'Practicing dentist in clinic staff.' }}
                </p>

                <!-- Weekly Shifts -->
                <div class="p-3 bg-light rounded-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-dark"><i class="bi bi-clock me-1 text-primary"></i> Active Weekly Working Hours:</span>
                        <span class="badge bg-secondary" style="font-size: 0.7rem;">{{ $dentist->slot_duration_minutes }} min slots</span>
                    </div>

                    <div class="d-flex flex-wrap gap-1">
                        @forelse($dentist->availabilities as $avail)
                            <div class="badge bg-white text-dark border p-2 small">
                                <span class="fw-bold text-teal" style="color: var(--secondary-teal);">{{ $avail->day_name }}:</span>
                                {{ \Carbon\Carbon::parse($avail->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($avail->end_time)->format('g:i A') }}
                            </div>
                        @empty
                            <span class="text-muted small">No weekly availability schedule configured.</span>
                        @endforelse
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-auto border-top pt-3">
                    @if(Auth::user()->isAdmin())
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#editModal{{ $dentist->id }}">
                        <i class="bi bi-pencil me-1"></i> Edit Profile
                    </button>
                    <button type="button" class="btn btn-sm btn-teal rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#shiftModal{{ $dentist->id }}">
                        <i class="bi bi-calendar2-week me-1"></i> Manage Working Days
                    </button>
                    @else
                    <span class="text-muted small py-1"><i class="bi bi-lock me-1"></i> Changes require Clinic Administrator role</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <div class="modal fade" id="editModal{{ $dentist->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('staff.dentists.update', $dentist) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Edit Dentist: {{ $dentist->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Display Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $dentist->name }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Specialization</label>
                                <input type="text" name="specialization" class="form-control" value="{{ $dentist->specialization }}" required>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Mobile Phone</label>
                                    <input type="text" name="phone" class="form-control" value="{{ $dentist->phone }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">License Number</label>
                                    <input type="text" name="license_number" class="form-control" value="{{ $dentist->license_number }}">
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Slot Duration</label>
                                    <select name="slot_duration_minutes" class="form-select">
                                        <option value="15" {{ $dentist->slot_duration_minutes == 15 ? 'selected' : '' }}>15 minutes</option>
                                        <option value="30" {{ $dentist->slot_duration_minutes == 30 ? 'selected' : '' }}>30 minutes</option>
                                        <option value="45" {{ $dentist->slot_duration_minutes == 45 ? 'selected' : '' }}>45 minutes</option>
                                        <option value="60" {{ $dentist->slot_duration_minutes == 60 ? 'selected' : '' }}>60 minutes</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" {{ $dentist->status === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $dentist->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Professional Biography</label>
                                <textarea name="bio" class="form-control" rows="3">{{ $dentist->bio }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-navy rounded-pill px-4">Update Dentist</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Shift Modal -->
        <div class="modal fade" id="shiftModal{{ $dentist->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('staff.dentists.availability', $dentist) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Configure Shifts: {{ $dentist->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted mb-3">Check the days of the week this dentist is on duty, and set their shift start and end times.</p>
                            @php
                                $daysList = [
                                    1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 0 => 'Sunday'
                                ];
                                $activeMap = $dentist->availabilities->keyBy('day_of_week');
                            @endphp

                            <div class="d-flex flex-column gap-2">
                                @foreach($daysList as $dayNum => $dayName)
                                    @php $currentAvail = $activeMap->get($dayNum); @endphp
                                    <div class="row align-items-center g-2 p-2 border rounded bg-light">
                                        <div class="col-md-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="days[{{ $dayNum }}][active]" value="1" id="day{{ $dentist->id }}_{{ $dayNum }}" {{ $currentAvail ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold small" for="day{{ $dentist->id }}_{{ $dayNum }}">{{ $dayName }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Start</span>
                                                <input type="time" name="days[{{ $dayNum }}][start_time]" class="form-control" value="{{ $currentAvail ? \Carbon\Carbon::parse($currentAvail->start_time)->format('H:i') : '09:00' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">End</span>
                                                <input type="time" name="days[{{ $dayNum }}][end_time]" class="form-control" value="{{ $currentAvail ? \Carbon\Carbon::parse($currentAvail->end_time)->format('H:i') : '17:00' }}">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-teal rounded-pill px-4">Save Working Shifts</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Add Dentist Modal -->
<div class="modal fade" id="addDentistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.dentists.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus-fill me-1 text-teal" style="color: var(--secondary-teal);"></i> Register New Dentist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Professional Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Emily Clarke, DMD" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Login Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="dr.clarke@clinicsync.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mobile Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" placeholder="+15551234567" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Specialization <span class="text-danger">*</span></label>
                            <input type="text" name="specialization" class="form-control" placeholder="e.g. Periodontics" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">License #</label>
                            <input type="text" name="license_number" class="form-control" placeholder="DEN-XXXX">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Slot Duration <span class="text-danger">*</span></label>
                            <select name="slot_duration_minutes" class="form-select" required>
                                <option value="30">30 minutes</option>
                                <option value="15">15 minutes</option>
                                <option value="45">45 minutes</option>
                                <option value="60">60 minutes</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Initial Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Professional Biography</label>
                        <textarea name="bio" class="form-control" rows="2" placeholder="Clinical specialties, university degrees, etc."></textarea>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Dentist will be created with default Mon-Fri (09:00 - 17:00) shifts, which can be customized at any time.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">Register Dentist Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
